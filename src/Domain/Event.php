<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Domain;

interface Event
{
    /**
     * The event name, used for routing when publishing
     * Event name example: "company.context.action" =>
     *          "my_company"    -> company name or team
     *          "users"         -> bounded context
     *          "registered"    -> the event action that has been performed
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
