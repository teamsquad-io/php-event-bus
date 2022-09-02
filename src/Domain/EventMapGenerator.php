<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Domain;

use TeamSquad\EventBus\Domain\Exception\UnknownEventException;

/**
 * EventMapGenerator is responsible for generating a hash table with the routing keys (eventName property)
 * mapping to its events classes.
 */
interface EventMapGenerator
{
    /**
     * @param string $routingKey
     *
     * @throws UnknownEventException
     *
     * @return class-string<Event>
     */
    public function get(string $routingKey): string;

    /**
     * @return array<array-key, mixed>
     */
    public function getAll(): array;

    /**
     * @return void
     */
    public function generate(): void;
}
