<?php

declare(strict_types=1);

namespace TeamSquad\Tests\Integration\Application;

use Amp\Deferred;
use Amp\Promise;
use JsonException;
use League\Tactician\Middleware;
use TeamSquad\EventBus\Domain\Command;
use TeamSquad\EventBus\Infrastructure\Exception\CouldNotCreateTemporalQueueException;
use TeamSquad\EventBus\Infrastructure\Rabbit;

class AsyncSendEventMiddleware implements Middleware
{
    private string $channel;
    private string $queueName;
    private Rabbit $rabbit;

    public function __construct(string $channel, string $queueName, Rabbit $rabbit)
    {
        $this->channel = $channel;
        $this->queueName = $queueName;
        $this->rabbit = $rabbit;
    }

    /**
     * @param Command $command
     * @param callable $next
     *
     * @throws JsonException
     * @throws CouldNotCreateTemporalQueueException
     *
     * @return Promise
     */
    public function execute($command, callable $next)
    {
        $deferred = new Deferred();

        $this->rabbit->createTemporalQueue('');
        $this->rabbit->consume($this->queueName, '', false, true, false, false, static function ($msg) use ($deferred): void {
            $response = $msg->body;

            $deferred->resolve($response);
        });

        $this->rabbit->publish('', $this->channel, $command->toArray());

        return $deferred->promise();
    }
}
