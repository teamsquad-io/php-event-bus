<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Domain;

use Attribute;

#[Attribute]
class ConsumerConfig
{
    /**
     * @param string|null $amqp Name of the AMQP connection profile to use (e.g., default, users).
     * @param string|null $name human/unique identifier for the consumer, typically FQCN::method
     * @param array<string>|null $routingKey List of routing keys the queue is bound to (e.g., sample_event).
     * @param bool $unique whether this consumer definition should be treated as non-duplicated across generation/deployment
     * @param string|null $url HTTP route that maps to a controller endpoint for this consumer
     * @param string|null $queue name of the queue to consume from (created/bound according to params when create_queue is true)
     * @param string|null $exchange Exchange name to bind the queue to (e.g., teamsquad.event_bus).
     * @param string|null $function method on the consumer class that will be invoked for each message
     * @param bool|null $createQueue if true, the queue will be declared/created automatically when setting up the consumer
     * @param int|null $workers number of worker processes/consumers to spawn for this consumer (parallelism level)
     * @param bool $passive RabbitMQ queue declaration parameters used when creating/declaring the queue:
     * @param bool $durable if true, do not create; only check that the queue exists
     * @param bool $exclusive if true, the queue will survive a broker restart
     * @param bool $autoDelete if true, the queue is restricted to this connection and will be deleted when the connection closes
     * @param bool $nowait if true, the queue will be deleted when the last consumer unsubscribes
     * @param array<array-key, mixed>|null $args
     */
    public function __construct(
        public ?string $amqp = null,
        public ?string $name = null,
        public ?array $routingKey = null,
        public bool $unique = false,
        public ?string $url = null,
        public ?string $queue = null,
        public ?string $exchange = null,
        public ?string $function = null,
        public ?bool $createQueue = null,
        public ?int $workers = null,
        public ?array $args = null,
        public bool $passive = false,
        public bool $durable = false,
        public bool $exclusive = false,
        public bool $autoDelete = false,
        public bool $nowait = false,
    ) {
    }
}
