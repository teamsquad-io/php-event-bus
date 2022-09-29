<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Domain;

interface Input
{
    public function get(): string;
}
