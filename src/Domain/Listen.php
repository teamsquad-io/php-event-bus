<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Domain;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 *
 * @Target("METHOD")
 */
final class Listen implements Event
{
    public string $routingKey;

    public function __construct()
    {
        $this->routingKey = '';
    }

    public function eventName(): string
    {
        return $this->routingKey;
    }

    public function toArray(): array
    {
        return [];
    }

    /**
     * @param array<array-key, mixed> $array
     *
     * @return Listen
     */
    public static function fromArray(array $array): self
    {
        return new self();
    }
}
