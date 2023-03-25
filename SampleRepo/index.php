<?php

declare(strict_types=1);

use TeamSquad\EventBus\Domain\Consumer;
use TeamSquad\EventBus\Domain\EventMapGenerator;
use TeamSquad\EventBus\Domain\StringEncrypt;
use TeamSquad\EventBus\Infrastructure\AutoloaderEventMapGenerator;
use TeamSquad\EventBus\Infrastructure\Rabbit;

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
            [$controller, $method] = explode('/', $route['route']);
            /** @var class-string<Consumer> $controller */
            $controller = $controllerMap[$controller];
            if (!$controller || !is_string($controller)) {
                throw new RuntimeException(sprintf('Controller not found for route %s', $route['route']));
            }
    
            $class = new $controller(
                AutoloaderEventMapGenerator::createAutomatically(),
                new StringEncrypt(),
                new Rabbit()
            );
            echo $class->$method();
            break;
        }
    }
}
