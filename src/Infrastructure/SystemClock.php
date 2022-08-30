<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use DateTimeImmutable;
use TeamSquad\EventBus\Domain\Clock;

class SystemClock implements Clock
{
    public function timestamp(): int
    {
        return (new DateTimeImmutable())->getTimestamp();
    }
}
