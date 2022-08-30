<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Domain;

interface Event
{
    /**
     * The event name, used for routing when publishing
     * Event name example: "vts.tag.saved" =>
     *          "vts"   -> company name or team
     *          "tag"   -> bounded context
     *          "saved" -> the event action that has been performed
     *
     * @return string
     */
    public function eventName(): string;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * @param array<string, mixed> $array
     *
     * @return Event
     */
    public static function fromArray(array $array): self;
}
