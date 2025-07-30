<?php

declare(strict_types=1);

namespace TeamSquad\Tests;

use TeamSquad\EventBus\Domain\Event;
use TeamSquad\EventBus\Domain\SecureEvent;

class SampleSecureEvent extends SecureEvent
{
    private string $encrypted_property;

    /**
     * @param array<string, string> $array
     */
    public function __construct(array $array)
    {
        $this->encrypted_property = $array['encrypted_property'];
    }

    public static function protectedFields(): array
    {
        return [
            'encrypted_property',
        ];
    }

    public function eventName(): string
    {
        return 'sample_secure_event';
    }

    public function toArray(): array
    {
        return [
            'encrypted_property' => $this->encrypted_property,
        ];
    }

    /**
     * @param array<string, mixed> $array
     *
     * @return Event
     */
    public static function fromArray(array $array): Event
    {
        return new self($array);
    }
}
