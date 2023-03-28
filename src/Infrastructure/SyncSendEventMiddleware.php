<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use InvalidArgumentException;
use JsonException;
use League\Tactician\Middleware;
use PhpAmqpLib\Message\AMQPMessage;
use TeamSquad\EventBus\Domain\Command;
use TeamSquad\EventBus\Infrastructure\Exception\CouldNotCreateTemporalQueueException;

class SyncSendEventMiddleware implements Middleware
{
    private string $exchangeName;
    private Rabbit $rabbit;

    public function __construct(string $exchangeName, Rabbit $rabbit)
    {
        $this->exchangeName = $exchangeName;
        $this->rabbit = $rabbit;
    }

    /**
     * @param Command|object $command
     * @param callable $next
     *
     * @throws CouldNotCreateTemporalQueueException
     * @throws JsonException
     *
     * @return ?string
     */
    public function execute($command, callable $next): ?string
    {
        if (!$command instanceof Command) {
            throw new InvalidArgumentException('Instance of Command expected at SyncSendEventMiddleware');
        }

        if (method_exists($command, 'setQueueToReply')) {
            $channel = $this->rabbit->getChannel();
            if (!$channel) {
                throw new CouldNotCreateTemporalQueueException('Could not create temporal queue because channel is not available');
            }
            $queueName = $this->rabbit->createTemporalQueue($this->exchangeName);
            /** @var array<array-key, string> $stack */
            $stack = [];
            $this->rabbit->consume(
                $queueName,
                $queueName,
                false,
                true,
                true,
                false,
                static function (AMQPMessage $msg) use ($next, &$stack): void {
                    $body = $msg->body;
                    echo "Received message: {$body}" . PHP_EOL;
                    /** @psalm-suppress MixedArrayAssignment  */
                    $stack[] = $body;
                    $next($body);
                }
            );
            $command->setQueueToReply($queueName);
            $this->rabbit->publish($this->exchangeName, $command->eventName(), $command->toArray());

            while ($channel->is_open() && empty($stack)) {
                $channel->wait();
            }

            /** @psalm-suppress RedundantCondition  */
            if (empty($stack)) {
                $result = '';
            } else {
                /**
                 * @psalm-suppress NoValue
                 *
                 * @var string $result
                 */
                $result = array_pop($stack);
            }
            $next($result);
            return $result;
        }

        $this->rabbit->publish($this->exchangeName, $command->eventName(), $command->toArray());
        $next();

        return '';
    }
}
