<?php

use TeamSquad\EventBus\Domain\Consumer;

require_once __DIR__ . '/../../vendor/autoload.php';
$controllerMap = require __DIR__ . '/SampleConfigPath/auto_controllerMap.php';
$routes = require __DIR__ . '/SampleConfigPath/auto_routes.php';
if (empty($controllerMap)) {
    throw new RuntimeException('No controllers found');
}

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

foreach ($routes as $route) {
    if ($route['pattern'] === $requestUri) {
        $controller = $controllerMap[$route['route']];
        if (!$controller || !is_string($controller)) {
            throw new RuntimeException(sprintf('Controller not found for route %s', $route['route']));
        }
        
        $controller = explode('::', $controller);
        /** @var class-string<Consumer> $class */
        $class = $controller[0];
        $method = $controller[1];
        $class = new $class();
        echo $class->$method();
        break;
    }
}



