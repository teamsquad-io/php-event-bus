<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use TeamSquad\EventBus\Domain\RetryPolicy;

/**
 * Exponential backoff retry policy with jitter
 * 
 * This retry policy implements exponential backoff with optional jitter
 * to prevent thundering herd problems when many consumers retry simultaneously.
 */
class ExponentialBackoffRetryPolicy implements RetryPolicy
{
    private int $maxAttempts;
    private int $initialDelayMs;
    private float $multiplier;
    private int $maxDelayMs;
    private bool $useJitter;
    private array $retryableExceptions;

    /**
     * @param int $maxAttempts Maximum number of retry attempts
     * @param int $initialDelayMs Initial delay in milliseconds
     * @param float $multiplier Multiplier for exponential backoff
     * @param int $maxDelayMs Maximum delay cap in milliseconds
     * @param bool $useJitter Whether to add random jitter to delays
     * @param array<class-string<\Throwable>> $retryableExceptions Exceptions that should trigger retries
     */
    public function __construct(
        int $maxAttempts = 3,
        int $initialDelayMs = 1000,
        float $multiplier = 2.0,
        int $maxDelayMs = 30000,
        bool $useJitter = true,
        array $retryableExceptions = []
    ) {
        $this->maxAttempts = $maxAttempts;
        $this->initialDelayMs = $initialDelayMs;
        $this->multiplier = $multiplier;
        $this->maxDelayMs = $maxDelayMs;
        $this->useJitter = $useJitter;
        $this->retryableExceptions = $retryableExceptions;
    }

    public function shouldRetry(int $attemptNumber, \Throwable $exception): bool
    {
        // Don't retry if we've exceeded max attempts
        if ($attemptNumber >= $this->maxAttempts) {
            return false;
        }

        // If specific retryable exceptions are configured, only retry for those
        if (!empty($this->retryableExceptions)) {
            foreach ($this->retryableExceptions as $retryableException) {
                if ($exception instanceof $retryableException) {
                    return true;
                }
            }
            return false;
        }

        // By default, retry for most exceptions except specific non-retryable ones
        return !$this->isNonRetryableException($exception);
    }

    public function getDelayMs(int $attemptNumber): int
    {
        // Calculate exponential delay
        $delay = $this->initialDelayMs * pow($this->multiplier, $attemptNumber - 1);
        
        // Apply maximum delay cap
        $delay = min($delay, $this->maxDelayMs);
        
        // Add jitter if enabled (random value between 50% and 100% of calculated delay)
        if ($this->useJitter) {
            $jitterRange = $delay * 0.5;
            $delay = $delay - $jitterRange + (random_int(0, (int)$jitterRange * 2));
        }
        
        return (int)$delay;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * Check if an exception should not be retried
     */
    private function isNonRetryableException(\Throwable $exception): bool
    {
        // Don't retry for these types of exceptions as they're unlikely to succeed on retry
        $nonRetryableExceptions = [
            \InvalidArgumentException::class,
            \LogicException::class,
            \BadMethodCallException::class,
            \TypeError::class,
            \ParseError::class,
        ];

        foreach ($nonRetryableExceptions as $nonRetryableException) {
            if ($exception instanceof $nonRetryableException) {
                return true;
            }
        }

        return false;
    }
}

/**
 * Fixed delay retry policy
 * 
 * Simple retry policy with a fixed delay between attempts.
 */
class FixedDelayRetryPolicy implements RetryPolicy
{
    private int $maxAttempts;
    private int $delayMs;

    public function __construct(int $maxAttempts = 3, int $delayMs = 5000)
    {
        $this->maxAttempts = $maxAttempts;
        $this->delayMs = $delayMs;
    }

    public function shouldRetry(int $attemptNumber, \Throwable $exception): bool
    {
        return $attemptNumber < $this->maxAttempts;
    }

    public function getDelayMs(int $attemptNumber): int
    {
        return $this->delayMs;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }
}

/**
 * No retry policy
 * 
 * Policy that never retries - useful for critical operations or testing.
 */
class NoRetryPolicy implements RetryPolicy
{
    public function shouldRetry(int $attemptNumber, \Throwable $exception): bool
    {
        return false;
    }

    public function getDelayMs(int $attemptNumber): int
    {
        return 0;
    }

    public function getMaxAttempts(): int
    {
        return 1;
    }
}