<?php declare(strict_types=1);

namespace TeamSquad\EventBus\Domain;

use TeamSquad\EventBus\Domain\Exception\UnknownEventException;

interface EventMap
{
    /**
     * @param string $routingKey
     * @throws UnknownEventException
     * @return string
     */
    public function get(string $routingKey): string;

    /**
     * @return array<array-key, mixed>
     */
    public function getAll(): array;

    /**
     * @param bool $save
     * @return void
     */
    public function generate(bool $save = false): void;

    /**
     * @param string $routingKey
     * @param array<array-key, mixed> $eventData
     * @return Event
     */
    public function unserialize(string $routingKey, array $eventData): Event;
}
