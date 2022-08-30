<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use TeamSquad\EventBus\Domain\Clock;

class FakeClock implements Clock
{
    private int $timestamp;

    public function __construct(int $timestamp)
    {
        $this->timestamp = $timestamp;
    }

    public function timestamp(): int
    {
        return $this->timestamp;
    }
}
