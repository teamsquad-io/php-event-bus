<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use Amp\Deferred;
use Amp\Promise;
use InvalidArgumentException;
use JsonException;
use League\Tactician\Middleware;
use PhpAmqpLib\Message\AMQPMessage;
use TeamSquad\EventBus\Domain\Command;
use TeamSquad\EventBus\Infrastructure\Exception\CouldNotCreateTemporalQueueException;

class AsyncSendEventMiddleware implements Middleware
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
     * @throws JsonException
     * @throws CouldNotCreateTemporalQueueException
     *
     * @return Promise<mixed|string>
     */
    public function execute($command, callable $next): Promise
    {
        if (!$command instanceof Command) {
            throw new InvalidArgumentException('Instance of Command expected at AsyncSendEventMiddleware');
        }

        $deferred = new Deferred();
        if (!method_exists($command, 'setQueueToReply')) {
            $this->rabbit->publish($this->exchangeName, $command->eventName(), $command->toArray());
            $deferred->resolve();
            $next();
            return $deferred->promise();
        }

        $queueName = $this->rabbit->createTemporalQueue($this->exchangeName);
        $this->rabbit->consume(
            $queueName,
            '',
            false,
            true,
            false,
            false,
            static function (AMQPMessage $msg) use ($deferred, $next): void {
                $response = $msg->body;
                $deferred->resolve($response);
                $next($response);
            }
        );
        $command->setQueueToReply($queueName);
        $this->rabbit->publish($this->exchangeName, $command->eventName(), $command->toArray());
        return $deferred->promise();
    }
}
