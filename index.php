<?php

require 'router.php';
require 'controllers.php';

/**
 * ------------------------ Dispatcher ------------------------
 */
echo (new Router())->dispatch();