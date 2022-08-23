<?php

namespace TeamSquad\EventBus\Core;

use TeamSquad\EventBus\Core\Exception\UnknownEventException;

interface EventMap
{
    /**
     * @param string $routingKey
     * @return string
     * @throws UnknownEventException
     */
    public function get(string $routingKey):string;
    public function getAll():array;
    public function generate(bool $save = false):void;
    public function unserialize(string $routingKey, array $eventData):Event;
}
