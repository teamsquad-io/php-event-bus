<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Domain;

interface Secrets
{
    public function get(string $key): string;
    public function findByKey(string $key, string $default): string;
}
