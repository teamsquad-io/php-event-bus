<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Domain;

abstract class SecureEvent implements Event
{
    /**
     * @return array<string>
     */
    public static function protectedFields(): array
    {
        return [];
    }
}
