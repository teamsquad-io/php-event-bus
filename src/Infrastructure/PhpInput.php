<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use TeamSquad\EventBus\Domain\Exception\InvalidArguments;
use TeamSquad\EventBus\Domain\Input;

use function gettype;
use function is_string;

class PhpInput implements Input
{
    /**
     * @throws InvalidArguments
     */
    public function get(): string
    {
        $result = file_get_contents('php://input');
        if (!is_string($result)) {
            throw new InvalidArguments(sprintf('Invalid body. Must be string. Got: %s', gettype($result)));
        }

        return $result;
    }
}
