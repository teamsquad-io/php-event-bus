<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Domain;

use Attribute;

#[Attribute]
class ConsumerConfig
{
    /**
     * @param string|null $amqp
     * @param string|null $name
     * @param array<string>|null $routingKey
     * @param bool|null $unique
     * @param string|null $url
     * @param string|null $queue
     * @param string|null $exchange
     * @param string|null $function
     * @param bool|null $createQueue
     * @param int|null $workers
     * @param bool $passive
     * @param bool $durable
     * @param bool $exclusive
     * @param bool $autoDelete
     * @param bool $nowait
     * @param array<array-key, mixed>|null $args
     */
    public function __construct(
        private ?string $amqp = null,
        private ?string $name = null,
        private ?array $routingKey = null,
        private ?bool $unique = null,
        private ?string $url = null,
        private ?string $queue = null,
        private ?string $exchange = null,
        private ?string $function = null,
        private ?bool $createQueue = null,
        private ?int $workers = null,
        private ?array $args = null,
        private bool $passive = false,
        private bool $durable = false,
        private bool $exclusive = false,
        private bool $autoDelete = false,
        private bool $nowait = false,
    ) {
    }

    public function amqp(): ?string
    {
        return $this->amqp;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    /**
     * @return array<string>|null
     */
    public function routingKey(): ?array
    {
        return $this->routingKey;
    }

    public function unique(): bool
    {
        return $this->unique ?? false;
    }

    public function url(): ?string
    {
        return $this->url;
    }

    public function queue(): ?string
    {
        return $this->queue;
    }

    public function exchange(): ?string
    {
        return $this->exchange;
    }

    public function function(): ?string
    {
        return $this->function;
    }

    public function createQueue(): bool
    {
        return $this->createQueue ?? true;
    }

    public function workers(): int
    {
        return $this->workers ?? 1;
    }

    public function passive(): bool
    {
        return $this->passive;
    }

    public function durable(): bool
    {
        return $this->durable;
    }

    public function exclusive(): bool
    {
        return $this->exclusive;
    }

    public function autoDelete(): bool
    {
        return $this->autoDelete;
    }

    public function nowait(): bool
    {
        return $this->nowait;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function args(): array
    {
        return $this->args ?? [
            'x-expires'   => [
                'type' => 'int',
                'val'  => 300000,
            ],
            'x-ha-policy' => [
                'type' => 'string',
                'val'  => 'all',
            ],
        ];
    }
}
