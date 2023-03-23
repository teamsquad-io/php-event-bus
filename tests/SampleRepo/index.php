<?php

declare(strict_types=1);

use TeamSquad\EventBus\Domain\Consumer;

require_once __DIR__ . '/../../vendor/autoload.php';
$controllerMap = require __DIR__ . '/SampleConfigPath/auto_controllerMap.php';
$routes = require __DIR__ . '/SampleConfigPath/auto_routes.php';
$consumerConf = require __DIR__ . '/SampleConfigPath/auto_consumerConf.php';

die(var_export($consumerConf, true));

if (empty($controllerMap)) {
    throw new RuntimeException('No controllers found');
}

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if ($requestUri === '/') {
    echo json_encode($consumerConf, JSON_THROW_ON_ERROR);
} else {
    foreach ($routes as $route) {
        if ($route['pattern'] === $requestUri) {
            $controller = $controllerMap[$route['route']];
            if (!$controller || !\is_string($controller)) {
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
}
