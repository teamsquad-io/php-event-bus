<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Domain;

interface EncryptedEvent extends Event
{
    /**
     * @return array<array-key, string>
     */
    public static function protectedFields(): array;
}
