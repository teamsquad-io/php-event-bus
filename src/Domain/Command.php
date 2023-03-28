<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Domain;

interface Command
{
    public function eventName(): string;

    /**
     * @param array<array-key, mixed> $array
     *
     * @return Command
     */
    public static function fromArray(array $array): self;

    /**
     * @return array<array-key, mixed>
     */
    public function toArray(): array;
}
