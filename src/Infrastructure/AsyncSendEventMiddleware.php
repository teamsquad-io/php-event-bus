<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use Amp\Deferred;
use Amp\Promise;
use JsonException;
use League\Tactician\Middleware;
use PhpAmqpLib\Message\AMQPMessage;
use TeamSquad\EventBus\Domain\Command;
use TeamSquad\EventBus\Infrastructure\Exception\CouldNotCreateTemporalQueueException;

class AsyncSendEventMiddleware implements Middleware
{
    private string $routingKey;
    private string $exchangeName;
    private Rabbit $rabbit;

    public function __construct(string $exchangeName, string $routingKey, Rabbit $rabbit)
    {
        $this->exchangeName = $exchangeName;
        $this->routingKey = $routingKey;
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
        $deferred = new Deferred();
        if ($command instanceof Command) {
            if (method_exists($command, 'setQueueToReply')) {
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
            } else {
                $deferred->resolve();
                $next();
            }

            /**
             * @var array<array-key, mixed> $toArray
             */
            $toArray = $command->toArray();
            $this->rabbit->publish($this->exchangeName, $this->routingKey, $toArray);
            return $deferred->promise();
        }
        $deferred->resolve();
        $next();


        return $deferred->promise();
    }
}
