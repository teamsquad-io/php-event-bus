<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use JsonException;
use ReflectionMethod;
use TeamSquad\EventBus\Domain\Clock;
use TeamSquad\EventBus\Domain\EventMap;
use TeamSquad\EventBus\Domain\Exception\InvalidArguments;

use function call_user_func_array;
use function get_class;

class GoAssistedConsumer
{
    private EventMap $eventMap;
    private Clock $clock;

    public function __construct(EventMap $eventMap, Clock $clock)
    {
        $this->eventMap = $eventMap;
        $this->clock = $clock;
    }

    public function consumerName(): string
    {
        return get_class($this);
    }

    /**
     * @throws InvalidArguments
     * @throws JsonException
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
        /** @var array<string,mixed> $rawCommand */
        $rawCommand = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        $params = [
            $this->eventMap->unserialize($routingKey, $rawCommand),
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
}
