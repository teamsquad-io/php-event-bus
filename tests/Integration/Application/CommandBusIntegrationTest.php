<?php

declare(strict_types=1);

namespace TeamSquad\Tests\Integration\Application;

use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use TeamSquad\EventBus\Infrastructure\MemorySecrets;
use TeamSquad\EventBus\Infrastructure\Rabbit;

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
                                'rabbit_port'  => '',
                                'rabbit_user'  => '',
                                'rabbit_pass'  => '',
                                'rabbit_vhost' => '/',
                            ]
                        )
                    )
                ),
            ]
        );

        $commandBus->handle();
    }
}
