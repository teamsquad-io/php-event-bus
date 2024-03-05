<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use TeamSquad\EventBus\Domain\Exception\NotFoundException;
use TeamSquad\EventBus\Domain\Secrets;

class EnvironmentSecrets implements Secrets
{
    /**
     * @throws NotFoundException
     */
    public function get(string $key): string
    {
        $env = getenv($key);
        if ($env === false || $env === '') {
            throw new NotFoundException(sprintf('Environment variable %s not found', $key));
        }

        return $env;
    }

    public function findByKey(string $key, string $default): string
    {
        $env = getenv($key);
        if ($env === false || $env === '') {
            return $default;
        }

        return $env;
    }
}
