<?php

declare(strict_types=1);

namespace TeamSquad\Tests;

use TeamSquad\EventBus\Domain\Event;

class SampleEvent implements Event
{
    private string $property;

    /**
     * @param array<string, string> $array
     */
    public function __construct(array $array)
    {
        $this->property = $array['property'];
    }

    public function eventName(): string
    {
        return 'sample_event';
    }

    public function toArray(): array
    {
        return [
            'property' => $this->property,
        ];
    }

    /**
     * @param array<string, string> $array
     *
     * @return Event
     */
    public static function fromArray(array $array): Event
    {
        return new self($array);
    }
}
