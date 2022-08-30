<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Domain;

use TeamSquad\EventBus\Domain\Exception\UnknownEventException;

interface EventMapResolver
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
     * @param bool $save
     *
     * @return void
     */
    public function generate(bool $save = false): void;
}
