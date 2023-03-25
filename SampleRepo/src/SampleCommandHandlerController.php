<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\SampleRepo;

use League\Tactician\CommandBus;
use TeamSquad\EventBus\Infrastructure\AsyncSendEventMiddleware;
use TeamSquad\EventBus\Infrastructure\MemorySecrets;
use TeamSquad\EventBus\Infrastructure\Rabbit;
use TeamSquad\EventBus\SampleRepo\SampleVideoPermissionChangeCommand;

class SampleCommandHandlerController
{
    private CommandBus $commandBus;

    public function __construct()
    {
        $this->commandBus = new CommandBus([
            new AsyncSendEventMiddleware(
                'teamsquad.eventBus',
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
        ]);
    }

    public function handleSampleVideoPermissionsChange(SampleVideoPermissionChangeCommand $command): void
    {
        $this->commandBus->handle($command);
    }
}
