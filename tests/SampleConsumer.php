<?php

declare(strict_types=1);

namespace TeamSquad\Tests;

use TeamSquad\EventBus\Domain\Consumer;
use TeamSquad\EventBus\Domain\EventMapGenerator;
use TeamSquad\EventBus\Domain\StringEncrypt;
use TeamSquad\EventBus\Interfaces\Consumer\GoAssistedConsumer;

class SampleConsumer implements Consumer
{
    use GoAssistedConsumer;

    public function __construct(EventMapGenerator $eventMap, StringEncrypt $dataEncrypt)
    {
        $this->init($eventMap, $dataEncrypt);
    }

    public function listenSampleEvent(SampleEvent $event): string
    {
        return $event->eventName();
    }
}
