<?php

declare(strict_types=1);

use TeamSquad\EventBus\Domain\Consumer;

require_once __DIR__ . '/../vendor/autoload.php';
$controllerMap = require __DIR__ . '/config/auto_controllerMap.php';
$routes = require __DIR__ . '/config/auto_routes.php';
$consumers = require __DIR__ . '/config/auto_consumerConf.php';

if (empty($controllerMap)) {
    throw new RuntimeException('No controllers found');
}

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if ($requestUri === '/amqpconf') {
    echo json_encode(
        [
            'amqp'      => [
                'url' => 'amqp://guest:guest@rabbitmq:5672/' . urlencode('/'),
                'qos' => [
                    'enabled'        => true,
                    'prefetch_count' => 1,
                    'prefetch_size'  => 0,
                    'global'         => false,
                ],
            ],
            'consumers' => $consumers,
        ],
        JSON_THROW_ON_ERROR
    );
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
