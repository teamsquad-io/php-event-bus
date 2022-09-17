<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Domain;

interface Bus
{
    public function publish(string $exchange, EventCollection $events): void;
}
