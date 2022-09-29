<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use TeamSquad\EventBus\Domain\Input;

class FakeInput implements Input
{
    private string $input;

    public function __construct(string $input)
    {
        $this->input = $input;
    }

    public function get(): string
    {
        return $this->input;
    }
}
