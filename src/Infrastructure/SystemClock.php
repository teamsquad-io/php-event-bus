<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use DateTimeImmutable;
use TeamSquad\EventBus\Domain\Clock;

use function sprintf;

class SystemClock implements Clock
{
    public function timestamp(): int
    {
        return (new DateTimeImmutable())->getTimestamp();
    }

    public function dateTimeWithMicroTime(): string
    {
        $microTimeAsFloat = microtime(true);
        $microTime = sprintf('%06d', ($microTimeAsFloat - floor($microTimeAsFloat)) * 1000000);
        return date(sprintf('Y-m-d\TH:i:s.%s', $microTime), (int)$microTimeAsFloat);
    }
}
