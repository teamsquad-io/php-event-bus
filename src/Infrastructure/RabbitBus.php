<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use JsonException;
use TeamSquad\EventBus\Domain\Bus;
use TeamSquad\EventBus\Domain\Clock;
use TeamSquad\EventBus\Domain\Event;
use TeamSquad\EventBus\Domain\EventCollection;
use TeamSquad\EventBus\Domain\SecureEvent;
use TeamSquad\EventBus\Domain\StringEncrypt;

use function is_string;

class RabbitBus implements Bus
{
    private Rabbit $rabbit;
    private Clock $clock;
    private StringEncrypt $stringEncrypt;

    public function __construct(
        Rabbit $rabbit,
        StringEncrypt $stringEncrypt,
        Clock $clock
    ) {
        $this->rabbit = $rabbit;
        $this->clock = $clock;
        $this->stringEncrypt = $stringEncrypt;
    }

    /**
     * @param string $exchange
     * @param EventCollection $events
     * @param array<string, int|string> $headers
     *
     * @throws JsonException
     *
     * @return void
     */
    public function publish(string $exchange, EventCollection $events, array $headers = []): void
    {
        $headers['published_at'] = $this->clock->dateTimeWithMicroTime();

        foreach ($events as $event) {
            $eventDataToArray = $event->toArray();
            $this->encryptProtectedFields($event, $eventDataToArray);
            $this->rabbit->publish($exchange, $event->eventName(), $eventDataToArray, null, $headers);
        }
    }

    /**
     * @param Event $event
     * @param array<string, mixed> $eventDataToArray
     *
     * @return void
     */
    private function encryptProtectedFields(Event $event, array &$eventDataToArray): void
    {
        if ($event instanceof SecureEvent) {
            foreach ($event::protectedFields() as $protectedField) {
                if ($this->canBeSkipped($eventDataToArray, $protectedField)) {
                    continue;
                }

                if (is_string($eventDataToArray[$protectedField])) {
                    $eventDataToArray[$protectedField] = $this->stringEncrypt->encrypt($eventDataToArray[$protectedField]);
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $eventData
     * @param string $protectedField
     *
     * @return bool
     */
    private function canBeSkipped(array $eventData, string $protectedField): bool
    {
        return empty($eventData[$protectedField]);
    }
}
