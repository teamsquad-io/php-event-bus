<?php

declare(strict_types=1);

namespace TeamSquad\Tests\Integration\Application;

use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use TeamSquad\EventBus\Infrastructure\AsyncSendEventMiddleware;
use TeamSquad\EventBus\Infrastructure\MemorySecrets;
use TeamSquad\EventBus\Infrastructure\Rabbit;
use TeamSquad\EventBus\SampleRepo\SampleVideoPermissionChangeCommand;

class CommandBusIntegrationTest extends TestCase
{
    public function test_command_bus(): void
    {
        $channel = 'test';
        $queueName = 'test';
        $commandBus = new CommandBus(
            [
                new AsyncSendEventMiddleware(
                    $channel,
                    $queueName,
                    Rabbit::getInstance(
                        new MemorySecrets(
                            [
                                'rabbit_host'  => 'localhost',
                                'rabbit_port'  => '5672',
                                'rabbit_user'  => 'guest',
                                'rabbit_pass'  => 'guest',
                                'rabbit_vhost' => '/',
                            ]
                        )
                    )
                ),
            ]
        );

        $commandBus->handle(
            new SampleVideoPermissionChangeCommand(
                '123',
                false
            )
        );
    }
}
