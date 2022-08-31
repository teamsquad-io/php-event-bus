<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Interfaces\Consumer;

use JsonException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use TeamSquad\EventBus\Domain\Clock;
use TeamSquad\EventBus\Domain\Event;
use TeamSquad\EventBus\Domain\EventMapGenerator;
use TeamSquad\EventBus\Domain\Exception\InvalidArguments;
use TeamSquad\EventBus\Domain\Exception\UnknownEventException;
use TeamSquad\EventBus\Domain\SecureEvent;
use TeamSquad\EventBus\Domain\StringEncrypt;

use function call_user_func_array;
use function get_class;
use function is_string;

class GoAssistedConsumer
{
    private EventMapGenerator $eventMap;
    private Clock $clock;
    private StringEncrypt $encrypt;

    public function __construct(EventMapGenerator $eventMap, StringEncrypt $dataEncrypt, Clock $clock)
    {
        $this->eventMap = $eventMap;
        $this->clock = $clock;
        $this->encrypt = $dataEncrypt;
    }

    /**
     * @param object $controllerInstance
     * @param string $methodName
     * @param string $routingKey
     * @param string $body
     * @param int|null $publishedAt
     *
     * @throws InvalidArguments
     * @throws JsonException
     * @throws ReflectionException
     * @throws UnknownEventException
     *
     * @return string
     */
    final public function parseRequest(
        object $controllerInstance,
        string $methodName,
        string $routingKey,
        string $body,
        ?int $publishedAt = null
    ): string {
        if (!$methodName) {
            return '';
        }
        if (!method_exists($controllerInstance, $methodName)) {
            throw new InvalidArguments(
                sprintf("%s::%s method doesn't exists", get_class($controllerInstance), $methodName)
            );
        }
        /** @var array<array-key, string> $rawCommand */
        $rawCommand = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        $params = [
            $this->unserialize($routingKey, $rawCommand),
        ];
        $method = new ReflectionMethod($controllerInstance, $methodName);
        if ($method->getNumberOfRequiredParameters() > 1) {
            if ($publishedAt !== null) {
                $params[] = $publishedAt;
            } else {
                $params[] = $this->clock->timestamp();
            }
        }

        /** @var callable $callback */
        $callback = $method->getClosure($controllerInstance);
        /** @var string|false $resultFunctionCall */
        $resultFunctionCall = call_user_func_array($callback, $params);
        if (!is_string($resultFunctionCall)) {
            return '';
        }

        return $resultFunctionCall;
    }

    /**
     * @param string $routingKey
     * @param array<array-key, string> $eventData
     *
     * @throws ReflectionException
     * @throws UnknownEventException
     *
     * @return Event
     */
    private function unserialize(string $routingKey, array $eventData): Event
    {
        $className = $this->eventMap->get($routingKey);
        $reflect = new ReflectionClass($className);
        $this->decryptProtectedFields($reflect, $eventData);
        /** @var Event $event */
        $event = $reflect->getMethod('fromArray')->invoke(null, $eventData);
        return $event;
    }

    /**
     * @param ReflectionClass<Event> $reflect
     * @param array<array-key, string> $eventData
     *
     * @throws ReflectionException
     *
     * @return void
     */
    private function decryptProtectedFields(ReflectionClass $reflect, array &$eventData): void
    {
        if ($reflect->isSubclassOf(SecureEvent::class)) {
            /** @var array<array-key, string> $protectedFields */
            $protectedFields = $reflect->getMethod('protectedFields')->invoke(null, null);
            foreach ($protectedFields as $protectedField) {
                if ($this->canBeSkipped($eventData, $protectedField)) {
                    continue;
                }
                $eventData[$protectedField] = $this->encrypt->decrypt($eventData[$protectedField]);
            }
        }
    }

    /**
     * @param array<array-key, mixed> $eventData
     * @param string $protectedField
     *
     * @return bool
     */
    private function canBeSkipped(array $eventData, string $protectedField): bool
    {
        return empty($eventData[$protectedField]);
    }
}
