<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Domain;

interface Clock
{
    public function timestamp(): int;
    public function dateTimeWithMicroTime(): string;
}
