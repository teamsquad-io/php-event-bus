<?php

declare(strict_types=1);

namespace TeamSquad\Tests\Unit\Interfaces;

use League\Tactician\CommandBus;
use TeamSquad\EventBus\Infrastructure\MemorySecrets;
use TeamSquad\EventBus\Infrastructure\Rabbit;
use TeamSquad\Tests\Integration\Application\AsyncSendEventMiddleware;
use TeamSquad\Tests\SampleVideoPermissionChangeCommand;

class SampleCommandHandlerController
{
    private CommandBus $commandBus;

    public function __construct()
    {
        $this->commandBus = new CommandBus([
            new AsyncSendEventMiddleware(
                'test',
                'test',
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
        ]);
    }

    public function handleSampleVideoPermissionsChange(SampleVideoPermissionChangeCommand $command): void
    {
        $this->commandBus->handle($command);
    }
}
