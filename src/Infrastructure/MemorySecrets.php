<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use TeamSquad\EventBus\Domain\Exception\NotFoundException;
use TeamSquad\EventBus\Domain\Secrets;

use function sprintf;

class MemorySecrets implements Secrets
{
    /** @var array<string, string> */
    private array $array;

    /**
     * @param array<string, string> $array
     */
    public function __construct(array $array)
    {
        $this->array = $array;
    }

    /**
     * @throws NotFoundException
     */
    public function get(string $key): string
    {
        if (!isset($this->array[$key])) {
            throw new NotFoundException(sprintf('Key %s not found', $key));
        }
        return $this->array[$key];
    }

    public function findByKey(string $key, string $default): string
    {
        return $this->array[$key] ?? $default;
    }
}
