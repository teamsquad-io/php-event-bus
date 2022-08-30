<?php

declare(strict_types=1);

namespace TeamSquad\Tests;

use TeamSquad\EventBus\Domain\Event;

use function is_string;

class SampleEvent implements Event
{
    private string $property;

    /**
     * @param array<string, mixed> $array
     */
    public function __construct(array $array)
    {
        if (isset($array['property']) && is_string($array['property'])) {
            $this->property = $array['property'];
        } else {
            $this->property = '';
        }
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
     * @param array<string, mixed> $array
     *
     * @return Event
     */
    public static function fromArray(array $array): Event
    {
        return new self($array);
    }
}
