<?php

namespace TeamSquad\EventBus\Domain\Exception;

use Exception;
use Throwable;

class ConnectionException extends Exception
{
    private int $retries;

    public function __construct(int $retries, $message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct(
            sprintf('%s. Retries: %d', $message, $retries),
            $code,
            $previous
        );
        $this->retries = $retries;
    }

    public function getRetries(): int
    {
        return $this->retries;
    }
}
