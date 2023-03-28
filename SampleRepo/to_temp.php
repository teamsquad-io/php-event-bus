<?php


require __DIR__ . '/../vendor/autoload.php';

use League\Tactician\CommandBus;
use TeamSquad\EventBus\Infrastructure\MemorySecrets;
use TeamSquad\EventBus\Infrastructure\Rabbit;
use TeamSquad\EventBus\Infrastructure\SyncSendEventMiddleware;
use TeamSquad\EventBus\SampleRepo\SampleVideoPermissionChangeCommand;

$rabbit = Rabbit::getInstance(
    new MemorySecrets(
        [
            'rabbit_host'  => 'localhost',
            'rabbit_port'  => '5672',
            'rabbit_user'  => 'guest',
            'rabbit_pass'  => 'guest',
            'rabbit_vhost' => '/',
        ]
    )
);
$rabbit->publish(
    '',
    'amq.gen-UtmlJUxiNLsZmjwZP6kAXg',
    [
        'result' => 'OK'
    ]
);
