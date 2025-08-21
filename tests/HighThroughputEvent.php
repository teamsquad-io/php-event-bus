<?php

declare(strict_types=1);

namespace TeamSquad\Tests;

use TeamSquad\EventBus\Domain\Event;

use function is_string;

class HighThroughputEvent implements Event
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function eventName(): string
    {
        return 'high.throughput.event';
    }

    public function toArray(): array
    {
        return [
            'property' => 'value',
        ];
    }

    /**
     * @param array<string, mixed> $array
     *
     * @return Event
     */
    public static function fromArray(array $array): Event
    {
        if (isset($array['property']) && is_string($array['property'])) {
            $property = $array['property'];
        } else {
            // Default value if 'property' is not set or not a string
            $property = 'default_value';
        }

        return new self($property);
    }

    public function value(): string
    {
        return $this->value;
    }
}
