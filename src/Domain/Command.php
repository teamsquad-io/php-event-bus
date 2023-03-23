<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Domain;

abstract class Command
{
    protected string $queueToReply;

    public function setQueueToReply(string $queueName): void
    {
        $this->queueToReply = $queueName;
    }

    abstract public function commandName(): string;

    /**
     * @param array<string, mixed> $array
     *
     * @return Command
     */
    abstract public static function fromArray(array $array): self;

    /**
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;
}
