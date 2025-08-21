<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Domain;

use ArrayObject;
use InvalidArgumentException;

use function sprintf;

/**
 * @extends ArrayObject<int, Event>
 */
class EventCollection extends ArrayObject
{
    /**
     * @param array<int, Event> $events
     */
    public function __construct(array $events = [])
    {
        foreach ($events as $event) {
            if (!is_a($event, Event::class, true)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Only %s are allowed in a %s (%s given)',
                        Event::class,
                        self::class,
                        var_export($event, true)
                    )
                );
            }
        }
        parent::__construct($events);
    }
}
