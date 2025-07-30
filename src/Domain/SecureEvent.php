<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Domain;

/**
 * @deprecated Use TeamSquad\EventBus\Domain\EncryptedEvent::class instead
 */
abstract class SecureEvent implements EncryptedEvent
{
    /**
     * @return array<string>
     */
    public static function protectedFields(): array
    {
        return [];
    }
}
