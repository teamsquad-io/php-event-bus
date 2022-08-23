<?php

namespace TeamSquad\EventBus\Domain;

interface Event
{
    /**
     * The event name, used for routing when publishing
     * Example: vts.tag.saved =>
     *          "vts"   -> company name or team
     *          "tag"   -> bounded context
     *          "saved" -> the event action that has been performed
     * @return string
     */
    public function eventName(): string;

    public function toArray(): array;

    public static function fromArray(array $array): self;
}
