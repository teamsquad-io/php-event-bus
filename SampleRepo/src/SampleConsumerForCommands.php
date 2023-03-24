<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\SampleRepo;

use TeamSquad\EventBus\Domain\Consumer;
use TeamSquad\EventBus\Domain\EventMapGenerator;
use TeamSquad\EventBus\Domain\StringEncrypt;
use TeamSquad\EventBus\Infrastructure\Rabbit;
use TeamSquad\EventBus\Interfaces\Consumer\GoAssistedConsumer;
use TeamSquad\EventBus\SampleRepo\SampleVideoPermissionChangeCommand;

/**
 * This is a sample command handler consumer
 * Notice that the method handleSampleVideoPermissionsChange is the one that will be called by the command bus
 * In order for a Controller to be recognized:
 * 1. The method starts with the word handle
 * 2. Has a parameter that is an instance of a Command
 */
class SampleConsumerForCommands implements Consumer
{
    use GoAssistedConsumer;

    private Rabbit $rabbit;

    public function __construct(EventMapGenerator $eventMap, StringEncrypt $dataEncrypt, Rabbit $rabbit)
    {
        $this->initializeConsumer($eventMap, $dataEncrypt);
        $this->rabbit = $rabbit;
    }

    public function handleSampleVideoPermissionChangeCommand(SampleVideoPermissionChangeCommand $event): string
    {
        $this->rabbit->publish(
            'teamsquad.eventBus',
            $event->getQueueToReply(),
            [
                'result' => 'OK',
            ]
        );
        return $event->commandName();
    }
}
