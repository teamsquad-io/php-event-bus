<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Domain;

/**
 * Interface for retry policies
 */
interface RetryPolicy
{
    /**
     * Determine if a retry should be attempted
     * 
     * @param int $attemptNumber The current attempt number (1-based)
     * @param \Throwable $exception The exception that caused the failure
     * @return bool True if retry should be attempted
     */
    public function shouldRetry(int $attemptNumber, \Throwable $exception): bool;

    /**
     * Calculate delay before next retry attempt
     * 
     * @param int $attemptNumber The current attempt number (1-based)
     * @return int Delay in milliseconds
     */
    public function getDelayMs(int $attemptNumber): int;

    /**
     * Get maximum number of retry attempts
     * 
     * @return int Maximum retry attempts
     */
    public function getMaxAttempts(): int;
}