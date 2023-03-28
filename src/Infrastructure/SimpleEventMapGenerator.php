<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use InvalidArgumentException;
use TeamSquad\EventBus\Domain\Command;
use TeamSquad\EventBus\Domain\Event;
use TeamSquad\EventBus\Domain\EventMapGenerator;

class SimpleEventMapGenerator implements EventMapGenerator
{
    /** @var array<string, class-string<Event>> */
    private array $map;

    /**
     * @param array<string, class-string<Event>> $map
     */
    public function __construct(array $map)
    {
        $this->map = $map;
    }

    /**
     * @param string $routingKey
     *
     * @return class-string<Event|Command>
     */
    public function get(string $routingKey): string
    {
        if (!isset($this->map[$routingKey])) {
            throw new InvalidArgumentException(sprintf("Event '%s' not found", $routingKey));
        }

        return $this->map[$routingKey];
    }

    public function getAll(): array
    {
        return $this->map;
    }

    public function generate(bool $save = false): void
    {
    }
}
