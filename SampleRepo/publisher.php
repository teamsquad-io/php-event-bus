<?php

require __DIR__ . '/../vendor/autoload.php';

use League\Tactician\CommandBus;
use TeamSquad\EventBus\Infrastructure\MemorySecrets;
use TeamSquad\EventBus\Infrastructure\Rabbit;
use TeamSquad\EventBus\Infrastructure\SynchronousSendEventMiddleware;
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
$commandBus = new CommandBus(
    [
        new SynchronousSendEventMiddleware(
            'teamsquad.eventBus',
            $rabbit
        ),
    ]
);

$value = $commandBus->handle(
    new SampleVideoPermissionChangeCommand(
        '123',
        false
    )
);

echo $value;
