<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\SampleRepo;

use TeamSquad\EventBus\Domain\Consumer;
use TeamSquad\EventBus\Domain\EventMapGenerator;
use TeamSquad\EventBus\Domain\StringEncrypt;
use TeamSquad\EventBus\Interfaces\Consumer\GoAssistedConsumer;
use TeamSquad\EventBus\SampleRepo\SampleEvent;

/**
 * This is a sample consumer class that uses the GoAssistedConsumer trait.
 */
class SampleConsumer implements Consumer
{
    use GoAssistedConsumer;

    public function __construct(EventMapGenerator $eventMap, StringEncrypt $dataEncrypt)
    {
        $this->initializeConsumer($eventMap, $dataEncrypt);
    }

    public function listenSampleEvent(SampleEvent $event): string
    {
        return $event->eventName();
    }
}
