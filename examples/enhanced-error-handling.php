<?php

/**
 * Example: Enhanced Consumer with Error Handling
 * 
 * This example demonstrates how the proposed error handling improvements
 * could be integrated into a consumer to provide robust message processing.
 */

declare(strict_types=1);

namespace App\Examples;

use TeamSquad\EventBus\Domain\Consumer;
use TeamSquad\EventBus\Domain\RetryPolicy;
use TeamSquad\EventBus\Infrastructure\DeadLetterQueue;
use TeamSquad\EventBus\Infrastructure\ExponentialBackoffRetryPolicy;
use App\Events\UserRegistered;
use App\Services\EmailService;
use Psr\Log\LoggerInterface;

class EnhancedEmailConsumer implements Consumer
{
    private EmailService $emailService;
    private RetryPolicy $retryPolicy;
    private DeadLetterQueue $deadLetterQueue;
    private LoggerInterface $logger;
    private array $attemptCounts = [];

    public function __construct(
        EmailService $emailService,
        ?RetryPolicy $retryPolicy = null,
        ?DeadLetterQueue $deadLetterQueue = null,
        ?LoggerInterface $logger = null
    ) {
        $this->emailService = $emailService;
        $this->retryPolicy = $retryPolicy ?? new ExponentialBackoffRetryPolicy(
            maxAttempts: 3,
            initialDelayMs: 1000,
            multiplier: 2.0,
            maxDelayMs: 30000,
            useJitter: true,
            retryableExceptions: [
                \RuntimeException::class,
                \Exception::class
            ]
        );
        $this->deadLetterQueue = $deadLetterQueue;
        $this->logger = $logger;
    }

    public function actionIndex(): void
    {
        // Framework integration point
    }

    public function listenUserRegistered(UserRegistered $event): string
    {
        $messageId = $this->generateMessageId($event);
        $attemptNumber = $this->getAttemptNumber($messageId);

        try {
            $this->logger?->info('Processing user registration email', [
                'user_id' => $event->getUserId(),
                'email' => $event->getEmail(),
                'message_id' => $messageId,
                'attempt' => $attemptNumber
            ]);

            // Process the event
            $result = $this->emailService->sendWelcomeEmail(
                $event->getEmail(),
                $event->getUserId()
            );

            // Success - clear retry count
            unset($this->attemptCounts[$messageId]);

            $this->logger?->info('Successfully sent welcome email', [
                'user_id' => $event->getUserId(),
                'email' => $event->getEmail(),
                'message_id' => $messageId
            ]);

            return $result;

        } catch (\Throwable $exception) {
            return $this->handleError($event, $exception, $messageId, $attemptNumber);
        }
    }

    private function handleError(
        UserRegistered $event,
        \Throwable $exception,
        string $messageId,
        int $attemptNumber
    ): string {
        $this->logger?->error('Error processing user registration email', [
            'user_id' => $event->getUserId(),
            'email' => $event->getEmail(),
            'message_id' => $messageId,
            'attempt' => $attemptNumber,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Check if we should retry
        if ($this->retryPolicy->shouldRetry($attemptNumber, $exception)) {
            $this->scheduleRetry($event, $messageId, $attemptNumber, $exception);
            return 'RETRY_SCHEDULED';
        }

        // Max retries exceeded - send to dead letter queue if configured
        if ($this->deadLetterQueue) {
            $this->deadLetterQueue->sendToDeadLetter(
                $event,
                'user-registration-emails',
                $exception->getMessage(),
                $attemptNumber - 1,
                ['message_id' => $messageId]
            );

            $this->logger?->warning('Message sent to dead letter queue', [
                'user_id' => $event->getUserId(),
                'message_id' => $messageId,
                'retry_count' => $attemptNumber - 1
            ]);

            return 'SENT_TO_DLQ';
        }

        // No DLQ configured - log and acknowledge (message will be lost)
        $this->logger?->critical('Message processing failed and no DLQ configured', [
            'user_id' => $event->getUserId(),
            'message_id' => $messageId,
            'retry_count' => $attemptNumber - 1
        ]);

        return 'FAILED_NO_DLQ';
    }

    private function scheduleRetry(
        UserRegistered $event,
        string $messageId,
        int $attemptNumber,
        \Throwable $exception
    ): void {
        $delayMs = $this->retryPolicy->getDelayMs($attemptNumber);
        
        $this->logger?->info('Scheduling retry for failed message', [
            'user_id' => $event->getUserId(),
            'message_id' => $messageId,
            'attempt' => $attemptNumber,
            'delay_ms' => $delayMs,
            'next_attempt' => $attemptNumber + 1
        ]);

        // Increment attempt count
        $this->attemptCounts[$messageId] = $attemptNumber + 1;

        // In a real implementation, this would use the message broker's
        // delay/scheduling features or a separate retry queue
        // For this example, we'll simulate with sleep (NOT recommended for production)
        usleep($delayMs * 1000);
    }

    private function generateMessageId(UserRegistered $event): string
    {
        return md5($event->getUserId() . $event->getEmail() . $event->getRegisteredAt()->format('c'));
    }

    private function getAttemptNumber(string $messageId): int
    {
        return $this->attemptCounts[$messageId] ?? 1;
    }
}

/**
 * Example usage and configuration
 */
class ExampleUsage
{
    public static function demonstrateErrorHandling(): void
    {
        // This example shows how the enhanced consumer would be configured
        // with different retry policies and error handling strategies

        echo "=== Enhanced Error Handling Example ===\n";

        // 1. Conservative retry policy for critical emails
        $conservativePolicy = new ExponentialBackoffRetryPolicy(
            maxAttempts: 5,
            initialDelayMs: 2000,
            multiplier: 1.5,
            maxDelayMs: 60000,
            useJitter: true
        );

        // 2. Aggressive retry policy for non-critical notifications  
        $aggressivePolicy = new ExponentialBackoffRetryPolicy(
            maxAttempts: 2,
            initialDelayMs: 500,
            multiplier: 3.0,
            maxDelayMs: 5000,
            useJitter: false
        );

        echo "Conservative policy max attempts: " . $conservativePolicy->getMaxAttempts() . "\n";
        echo "Aggressive policy max attempts: " . $aggressivePolicy->getMaxAttempts() . "\n";

        // Demonstrate delay calculation
        echo "\n=== Delay Calculation Examples ===\n";
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $conservativeDelay = $conservativePolicy->getDelayMs($attempt);
            $aggressiveDelay = $aggressivePolicy->getDelayMs($attempt);
            
            echo "Attempt $attempt - Conservative: {$conservativeDelay}ms, Aggressive: {$aggressiveDelay}ms\n";
        }

        echo "\n=== Error Classification Examples ===\n";
        $exceptions = [
            new \RuntimeException('Temporary network error'),
            new \InvalidArgumentException('Invalid email format'),
            new \LogicException('Business logic error'),
            new \Exception('Generic error')
        ];

        foreach ($exceptions as $exception) {
            $shouldRetryConservative = $conservativePolicy->shouldRetry(1, $exception);
            $shouldRetryAggressive = $aggressivePolicy->shouldRetry(1, $exception);
            
            $exceptionClass = get_class($exception);
            echo "$exceptionClass - Conservative: " . ($shouldRetryConservative ? 'RETRY' : 'NO_RETRY') . 
                 ", Aggressive: " . ($shouldRetryAggressive ? 'RETRY' : 'NO_RETRY') . "\n";
        }
    }
}

// Run the example
if (php_sapi_name() === 'cli') {
    ExampleUsage::demonstrateErrorHandling();
}