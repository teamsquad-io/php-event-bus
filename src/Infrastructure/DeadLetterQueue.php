<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use TeamSquad\EventBus\Domain\Event;

/**
 * Dead Letter Queue implementation for handling failed messages
 * 
 * This class provides functionality to route failed messages to a dead letter queue
 * after exhausting retry attempts, allowing for manual inspection and reprocessing.
 */
class DeadLetterQueue
{
    private Rabbit $rabbit;
    private string $deadLetterExchange;
    private string $deadLetterQueue;

    public function __construct(
        Rabbit $rabbit,
        string $deadLetterExchange = 'dlx',
        string $deadLetterQueue = 'dead_letter_queue'
    ) {
        $this->rabbit = $rabbit;
        $this->deadLetterExchange = $deadLetterExchange;
        $this->deadLetterQueue = $deadLetterQueue;
    }

    /**
     * Send a failed message to the dead letter queue
     * 
     * @param Event $event The original event that failed
     * @param string $originalQueue The queue where the message originally failed
     * @param string $errorMessage The error that caused the failure
     * @param int $retryCount Number of retry attempts made
     * @param array<string, mixed> $originalHeaders Original message headers
     */
    public function sendToDeadLetter(
        Event $event,
        string $originalQueue,
        string $errorMessage,
        int $retryCount,
        array $originalHeaders = []
    ): void {
        $deadLetterHeaders = array_merge($originalHeaders, [
            'x-dead-letter-reason' => 'max-retries-exceeded',
            'x-original-queue' => $originalQueue,
            'x-error-message' => $errorMessage,
            'x-retry-count' => $retryCount,
            'x-failed-at' => (new \DateTimeImmutable())->format('c'),
            'x-original-routing-key' => $event->eventName(),
        ]);

        $eventData = $event->toArray();
        
        $this->rabbit->publish(
            $this->deadLetterExchange,
            $this->deadLetterQueue,
            $eventData,
            null,
            $deadLetterHeaders
        );
    }

    /**
     * Setup dead letter queue infrastructure
     * 
     * Creates the necessary exchanges and queues for dead letter handling
     */
    public function setupInfrastructure(): void
    {
        // Declare dead letter exchange
        $this->rabbit->declareExchange(
            $this->deadLetterExchange,
            'direct',
            false, // passive
            true,  // durable
            false  // auto_delete
        );

        // Declare dead letter queue
        $this->rabbit->declareQueue(
            $this->deadLetterQueue,
            false, // passive
            true,  // durable
            false, // exclusive
            false, // auto_delete
            false, // nowait
            [
                'x-message-ttl' => [
                    'type' => 'int',
                    'val' => 86400000, // 24 hours in milliseconds
                ],
                'x-max-length' => [
                    'type' => 'int',
                    'val' => 10000, // Maximum 10k messages in DLQ
                ]
            ]
        );

        // Bind queue to exchange
        $this->rabbit->bindQueue(
            $this->deadLetterQueue,
            $this->deadLetterExchange,
            $this->deadLetterQueue
        );
    }

    /**
     * Get messages from dead letter queue for inspection
     * 
     * @param int $limit Maximum number of messages to retrieve
     * @return array<array{event_data: array, headers: array}>
     */
    public function getDeadLetterMessages(int $limit = 10): array
    {
        $messages = [];
        $count = 0;

        while ($count < $limit) {
            $message = $this->rabbit->basicGet($this->deadLetterQueue, false);
            if ($message === null) {
                break;
            }

            $messages[] = [
                'event_data' => json_decode($message->getBody(), true),
                'headers' => $message->get_properties(),
                'delivery_tag' => $message->getDeliveryTag(),
            ];

            $count++;
        }

        return $messages;
    }

    /**
     * Requeue a message from dead letter queue back to original queue
     * 
     * @param string $deliveryTag The delivery tag of the message to requeue
     * @param bool $resetRetryCount Whether to reset the retry count
     */
    public function requeueMessage(string $deliveryTag, bool $resetRetryCount = true): void
    {
        // Implementation would depend on storing message details for requeuing
        // This is a simplified version - full implementation would need message storage
        $this->rabbit->basicAck($deliveryTag);
    }

    /**
     * Acknowledge and remove a message from dead letter queue
     * 
     * @param string $deliveryTag The delivery tag of the message to acknowledge
     */
    public function acknowledgeMessage(string $deliveryTag): void
    {
        $this->rabbit->basicAck($deliveryTag);
    }

    /**
     * Get statistics about the dead letter queue
     * 
     * @return array{message_count: int, consumer_count: int}
     */
    public function getQueueStats(): array
    {
        return $this->rabbit->getQueueStats($this->deadLetterQueue);
    }

    /**
     * Purge all messages from the dead letter queue
     * 
     * WARNING: This will permanently delete all messages in the DLQ
     */
    public function purgeQueue(): void
    {
        $this->rabbit->purgeQueue($this->deadLetterQueue);
    }
}