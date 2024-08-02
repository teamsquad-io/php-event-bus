<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use Doctrine\Common\Annotations\Annotation\Enum;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 *
 * @Target("METHOD")
 * Annotation to describe the manual configuration of the consumer.
 */
final class Manual
{
    /**
     * Describes the unserializer to use for the message.
     * Unserializer "event" will deserialize the message into an event object.
     */
    public const UNSERIALIZER_EVENT = 'event';

    /**
     * Describes the unserializer to use for the message.
     * Unserializer "raw" will deserialize the message into a raw array.
     */
    public const UNSERIALIZER_RAW = 'raw';

    /**
     * @Enum({"event","raw"})
     *
     * @var string
     */
    public string $unserializer = self::UNSERIALIZER_EVENT;
    public string $queue = '';

    /** @var array<string> */
    public array $routingKey = [];
    public string $exchange = '';
    public bool $passive = false;
    public bool $exclusive = false;
    public bool $durable = false;
    public bool $autoDelete = false;
    public bool $noWait = false;

    /** @var array<string, array{type: string, val: int|string}> */
    public array $args = [
        'x-expires'   => [
            'type' => 'int',
            'val'  => 300000,
        ],
        'x-ha-policy' => [
            'type' => 'string',
            'val'  => 'all',
        ],
    ];
    public bool $createQueue = true;
}
