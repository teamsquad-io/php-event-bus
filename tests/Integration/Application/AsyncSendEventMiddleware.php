<?php

declare(strict_types=1);

namespace TeamSquad\Tests\Integration\Application;

use Amp\Deferred;
use Amp\Promise;
use JsonException;
use League\Tactician\Middleware;
use PhpAmqpLib\Message\AMQPMessage;
use TeamSquad\EventBus\Domain\Command;
use TeamSquad\EventBus\Infrastructure\Exception\CouldNotCreateTemporalQueueException;
use TeamSquad\EventBus\Infrastructure\Rabbit;

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
     * @param Command $command
     * @param callable $next
     *
     * @throws JsonException
     * @throws CouldNotCreateTemporalQueueException
     *
     * @return Promise<string>
     */
    public function execute($command, callable $next): Promise
    {
        $deferred = new Deferred();
        $queueName = $this->rabbit->createTemporalQueue('');
        $this->rabbit->consume(
            $queueName,
            '',
            false,
            true,
            false,
            false,
            static function (AMQPMessage $msg) use ($deferred): void {
                $response = $msg->body;
                $deferred->resolve($response);
            }
        );

        $command->setQueueToReply($queueName);
        $this->rabbit->publish($this->exchangeName, $this->routingKey, $command->toArray());
        return $deferred->promise();
    }
}
