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
use TeamSquad\EventBus\Infrastructure\SystemClock;

use function call_user_func_array;
use function get_class;
use function gettype;
use function is_string;

trait GoAssistedConsumer
{
    private EventMapGenerator $eventMap;
    private Clock $clock;
    private StringEncrypt $encrypt;

    public function init(EventMapGenerator $eventMap, StringEncrypt $dataEncrypt): void
    {
        $this->eventMap = $eventMap;
        $this->encrypt = $dataEncrypt;
        $this->clock = new SystemClock();
    }

    /**
     * @throws ReflectionException
     * @throws JsonException
     * @throws InvalidArguments
     * @throws UnknownEventException
     */
    public function actionIndex(): void
    {
        $methodName = $_SERVER['HTTP_FUNCTION'];
        if (!is_string($methodName)) {
            throw new InvalidArguments(sprintf('Invalid method name. Must be string. Got: %s', gettype($methodName)));
        }

        $routingKey = $_SERVER['HTTP_ROUTING_KEY'];
        if (!is_string($routingKey)) {
            throw new InvalidArguments(sprintf('Invalid routing key. Must be string. Got: %s', gettype($routingKey)));
        }

        $body = file_get_contents('php://input');
        if (!is_string($body)) {
            throw new InvalidArguments(sprintf('Invalid body. Must be string. Got: %s', gettype($body)));
        }

        $publishedAt = $_SERVER['HTTP_PUBLISHED_AT'] ?? null;

        echo $this->parseRequest(
            $this,
            $methodName,
            $routingKey,
            $body,
            $publishedAt,
        );
    }

    /**
     * @param object $controllerInstance
     * @param string $methodName
     * @param string $routingKey
     * @param string $body
     * @param string|null $publishedAt
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
        ?string $publishedAt = null
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
                $params[] = $this->clock->dateTimeWithMicroTime();
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
     * @throws UnknownEventException
     * @throws ReflectionException
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
