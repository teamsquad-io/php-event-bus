<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\SampleRepo;

use JsonException;
use TeamSquad\EventBus\SampleRepo\SampleEvent;
use TeamSquad\EventBus\SampleRepo\SampleSecureEvent;

class SampleController
{
    /**
     * @throws JsonException
     */
    public function listenSampleEvent(SampleEvent $event): string
    {
        // do something
        return json_encode($event->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * @throws JsonException
     */
    public function listenSampleSecureEvent(SampleSecureEvent $event): string
    {
        // do something
        return json_encode($event->toArray(), JSON_THROW_ON_ERROR);
    }
}
