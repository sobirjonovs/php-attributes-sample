<?php

/**
 * ------------------------ Router attribute ------------------------
 */

#[Attribute(Attribute::TARGET_METHOD)]
class Router
{
    public const GET = 0b0001;
    public const POST = 0b0010;
    public const PUT = 0b0100;
    public const PATCH = 0b1000;
    public const DELETE = 0b10000;

    public array $routes = [];

    /**
     * @param string|null $path
     * @param int|null $method
     */
    public function __construct(
        public ?string $path = null,
        public ?int $method = null
    ) {}

    /**
     * @throws ReflectionException
     */
    public function dispatch(): mixed
    {
        $uri = parse_url($_SERVER['REQUEST_URI']);

        $raw_data = json_decode(file_get_contents('php://input'), true) ?: [];

        parse_str($uri['query'] ?? '', $query);

        $data = array_merge($_REQUEST, $raw_data, $query);

        $method = $_SERVER['REQUEST_METHOD'];

        $route = $this->addRoutes()->filterRoute($method, rtrim($uri['path'], '/'));

        if (empty($route)) {
            echo 'Page not found';

            http_response_code(404);

            die;
        }

        $result = call_user_func([new $route['class'], $route['method']], $data);

        return $this->castValue($result);
    }

    /**
     * @param string $method
     * @param string $class
     * @return void
     */
    private function register(string $method, string $class): void
    {
        foreach ($this->explainBitmaskMethods() as $request_method) {
            $this->routes[$request_method] = [
                'class' => $class,
                'method' => $method,
                'path' => $this->path
            ];
        }
    }

    /**
     * @throws ReflectionException
     */
    private function addRoutes(): static
    {
        $controllers = array_filter(get_declared_classes(), function ($class) {
            return strcasecmp('controller', substr($class, -10)) === 0;
        });

        foreach ($controllers as $controller) {
            $methods = (new ReflectionClass($controller))->getMethods();

            foreach ($methods as $method) {
                foreach ($method->getAttributes(Router::class) as $attribute) {
                    /**
                     * @var Router $router
                     */
                    $router = $attribute->newInstance();

                    $this->path = $router->path;
                    $this->method = $router->method;

                    $this->register(
                        method: $method->getName(),
                        class: $method->getDeclaringClass()->getName()
                    );
                }
            }
        }

        return $this;
    }

    /**
     * @param string|null $name
     * @return array|int
     */
    private function getMethod(string $name = null): array|int
    {
        $methods = [
            'GET' => self::GET,
            'POST' => self::POST,
            'PUT' => self::PUT,
            'PATCH' => self::PATCH,
            'DELETE' => self::DELETE
        ];

        if ($name && isset($methods[$name])) {
            return $methods[$name];
        }

        return $methods;
    }

    /**
     * @return array
     */
    private function explainBitmaskMethods(): array
    {
        $methods = [];

        foreach ($this->getMethod() as $method => $bit) {
            if ($bit & $this->method) {
                $methods[] = $method;
            }
        }

        return $methods;
    }

    /**
     * @param string $method
     * @param string $path
     * @return array
     */
    private function filterRoute(string $method, string $path): array
    {
        return current(array_filter($this->routes, function ($route, $route_method) use ($method, $path) {
            return strcasecmp($route_method, $method) === 0 && strcasecmp($route['path'], $path) === 0;
        }, ARRAY_FILTER_USE_BOTH)) ?: [];
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    private function castValue(mixed $data): mixed
    {
        $casts = [
            'string' => function ($data) {
                header('Content-Type: application/json; charset=utf-8');

                echo $data;
            },
            'array' => function ($data) {
                header('Content-Type: application/json; charset=utf-8');

                echo json_encode($data);
            },
            'object' => function ($data) {
                echo serialize($data);
            },
            'default' => function ($data) {
                echo $data;
            }
        ];

        $type = gettype($data);
        $cast = $casts[$type] ?? $casts['default'];

        return $cast($data);
    }
}