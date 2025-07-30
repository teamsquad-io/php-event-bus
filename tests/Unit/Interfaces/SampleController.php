<?php

declare(strict_types=1);

namespace TeamSquad\Tests\Unit\Interfaces;

use JsonException;
use TeamSquad\Tests\SampleEvent;
use TeamSquad\Tests\SampleEventEncrypted;
use TeamSquad\Tests\SampleSecureEvent;

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

    /**
     * @throws JsonException
     */
    public function listenSampleEventEncrypted(SampleEventEncrypted $event): string
    {
        // do something
        return json_encode($event->toArray(), JSON_THROW_ON_ERROR);
    }
}
