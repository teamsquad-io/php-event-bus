<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Domain;

trait AsyncCommand
{
    protected ?string $queueToReply = null;

    public function setQueueToReply(string $queueName): void
    {
        $this->queueToReply = $queueName;
    }

    public function getQueueToReply(): ?string
    {
        return $this->queueToReply;
    }
}
