<?php

namespace TeamSquad\EventBus\Domain;

interface EventListener
{
    /**
     * Starts the event listener.
     * This method automatically search for all events inside your $consumerInstance class
     * that should be listened to and registers them.
     * @param $consumerInstance
     * @param bool $isSlowConsumer
     * @return mixed
     */
    public function start($consumerInstance, bool $isSlowConsumer = false);

    /**
     * Listens for a single event.
     * @param string|Event|EventCollection $event Event Class Name
     * @param callable $callback
     */
    public function listen($event, callable $callback);

    /**
     * Blocking operation. Waits for all events to be processed.
     */
    public function waitForEvents();
}
