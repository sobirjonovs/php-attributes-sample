<?php

/**
 * ------------------------ Controllers ------------------------
 */

class HomeController
{
    /**
     * @var array|string[][]
     */
    public array $menus = [
        'menus' => [
            'home' => 'Home',
            'about_us' => 'About us',
            'contact' => 'Contact'
        ]
    ];

    /**
     * @param array $data
     * @return string[]
     */
    #[Router(path: '/menus', method: Router::GET)]
    public function index(array $data): array
    {
        return $this->menus;
    }

    #[Router(path: '/menus/update', method: Router::PUT | Router::PATCH)]
    public function update(array $data): array
    {
        if (! isset($data['name'])) {
            return ['message' => 'Menu was not found'];
        }

        $this->menus['menus'][$data['name']] = $data['value'];

        return $this->menus;
    }
}