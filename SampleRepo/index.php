<?php

declare(strict_types=1);

use TeamSquad\EventBus\Domain\Consumer;
use TeamSquad\EventBus\Infrastructure\AutoloadConfig;
use TeamSquad\EventBus\Infrastructure\AutoloaderEventMapGenerator;
use TeamSquad\EventBus\Infrastructure\EnvironmentSecrets;
use TeamSquad\EventBus\Infrastructure\Rabbit;
use TeamSquad\EventBus\Infrastructure\SimpleEncrypt;

require_once __DIR__ . '/../vendor/autoload.php';
/** @var array<string, class-string<Consumer>> $controllerMap */
$controllerMap = require __DIR__ . '/config/auto_controllerMap.php';
/** @var array<string, array<string, string>> $routes */
$routes = require __DIR__ . '/config/auto_routes.php';
/** @var array<string, array<string, string>> $consumers */
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
    $secrets = new EnvironmentSecrets();
    foreach ($routes as $route) {
        if ($route['pattern'] === $requestUri) {
            /**
             * @var class-string<Consumer> $controller
             * @see Consumer::actionIndex()
             */
            [$controller, $method] = explode('/', $route['route']);
            $controller = $controllerMap[$controller];
            $class = new $controller(
                AutoloaderEventMapGenerator::createAutomatically(
                    AutoloadConfig::create(
                        $secrets->get('rabbit_event_listen'),
                        $secrets->get('rabbit_exchange'),
                        __DIR__ . '/config',
                    )
                ),
                new SimpleEncrypt(),
                Rabbit::getInstance($secrets)
            );
            $class->$method();
            break;
        }
    }
}
