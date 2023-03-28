<?php

declare(strict_types=1);

namespace TeamSquad\Tests\Integration\Application;

use Amp\Promise;
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

        /** @var Promise<string> $promise */
        $promise = $commandBus->handle(
            new SampleVideoPermissionChangeCommand(
                '123',
                false
            )
        );

        $promise->onResolve(
            static function ($error, $value): void {
                if ($error) {
                    echo $error->getMessage();
                }
                echo $value;
            }
        );

        /** @var string $value */
        $value = Promise\wait(Promise\timeout($promise, 10000));

        self::assertEquals('123', $value);
    }
}
