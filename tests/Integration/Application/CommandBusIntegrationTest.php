<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace TeamSquad\Tests\Integration\Application;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Promise;
use League\Tactician\CommandBus;
use TeamSquad\EventBus\Infrastructure\AsyncSendEventMiddleware;
use TeamSquad\EventBus\Infrastructure\MemorySecrets;
use TeamSquad\EventBus\Infrastructure\Rabbit;
use TeamSquad\EventBus\Infrastructure\SynchronousSendEventMiddleware;
use TeamSquad\EventBus\SampleRepo\SampleVideoPermissionChangeCommand;

class CommandBusIntegrationTest extends AsyncTestCase
{
    public function test_synchronous_command_bus(): void
    {
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

        /** @var string $promise */
        $promise = $commandBus->handle(
            new SampleVideoPermissionChangeCommand(
                '123',
                false
            )
        );

        self::assertEquals(
            [
                'result' => 'OK',
            ],
            json_decode($promise, true, 512, JSON_THROW_ON_ERROR)
        );
    }

    public function test_asynchronous_command_bus(): void
    {
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
                new AsyncSendEventMiddleware(
                    'teamsquad.eventBus',
                    $rabbit
                ),
            ]
        );

        /**
         * @var Promise<mixed|string> $promise
         */
        $promise = $commandBus->handle(
            new SampleVideoPermissionChangeCommand(
                '123',
                false
            )
        );

        $value = Promise\wait(
            Promise\timeout(
                $promise,
                20000
            )
        );
    }
}
