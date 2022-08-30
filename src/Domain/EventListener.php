<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Domain;

interface EventListener
{
    /**
     * Starts the event listener.
     * This method automatically search for all events inside your $consumerInstance class
     * that should be listened to and registers them.
     */
    public function start(object $consumerInstance, bool $isSlowConsumer = false): void;

    /**
     * Listens for a single event.
     *
     * @param string|Event|EventCollection $event Event Class Name
     * @param callable $callback
     */
    public function listen($event, callable $callback): void;

    /**
     * Blocking operation. Waits for all events to be processed.
     */
    public function waitForEvents(): void;
}
