<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use Closure;
use DomainException;
use Exception;
use JsonException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use RuntimeException;
use TeamSquad\EventBus\Domain\Secrets;
use TeamSquad\EventBus\Infrastructure\Exception\CouldNotCreateTemporalQueueException;
use Throwable;

use function array_key_exists;
use function is_string;

class Rabbit
{
    private ?AMQPChannel $channel;
    private ?AMQPStreamConnection $connection;
    private string $host;
    private int $port;
    private string $user;
    private string $pass;
    private string $vhost;
    private static ?self $instance = null;

    private function __construct(Secrets $secrets)
    {
        $this->host = $secrets->get('rabbit_host');
        $this->port = (int)$secrets->get('rabbit_port');
        $this->user = $secrets->get('rabbit_user');
        $this->pass = $secrets->get('rabbit_pass');
        $this->vhost = $secrets->findByKey('rabbit_vhost', '/');
        $this->channel = null;
        $this->connection = null;
    }

    public function getChannel(): ?AMQPChannel
    {
        if (!$this->channel) {
            $this->channel = $this->connect()->channel();
        }
        return $this->channel;
    }

    public static function getInstance(Secrets $secrets): self
    {
        if (!self::$instance) {
            self::$instance = new self($secrets);
        }
        return self::$instance;
    }

    /**
     * @param string $exchangeName
     * @param string $routingKey
     * @param array<array-key, mixed> $message
     * @param int|null $expiration
     * @param array<string, mixed> $applicationHeaders
     *
     * @throws JsonException
     *
     * @return void
     */
    public function publish(
        string $exchangeName,
        string $routingKey,
        array $message,
        int $expiration = null,
        array $applicationHeaders = []
    ): void {
        if ($expiration !== null && $expiration < 0) {
            throw new DomainException("RabbitWrapper publish called with invalid expiration: {$expiration}. Trying to publish message in " . $exchangeName . '(' . $routingKey . ') ');
        }

        $messageAsString = json_encode($message, JSON_THROW_ON_ERROR);
        if (!$messageAsString) {
            throw new DomainException(
                sprintf(
                    'RabbitWrapper publish called with empty message. Trying to publish message in %s(%s) backtrace: %s last old message: %s json last error: %s',
                    $exchangeName,
                    $routingKey,
                    json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20), JSON_THROW_ON_ERROR),
                    var_export($message, true),
                    json_last_error_msg()
                )
            );
        }

        $properties = [
            'content_type'     => 'application/json',
            'content_encoding' => 'utf-8',
            'app_id'           => '',
            'delivery_mode'    => 2,
        ];
        if ($expiration > 0) {
            $properties['expiration'] = $expiration;
        }

        $toSend = new AMQPMessage($messageAsString, $properties);
        if ($applicationHeaders) {
            $toSend->set('application_headers', new AMQPTable($applicationHeaders));
        }

        $this->connect()->channel()->basic_publish($toSend, $exchangeName, $routingKey);
    }

    /**
     * @throws Exception
     */
    public function closeConnection(): void
    {
        if ($this->channel !== null) {
            $this->channel->close();
        }
        if ($this->connection !== null) {
            $this->connection->close();
        }
    }

    /**
     * @param array<string, int> $qos
     *
     * @return void
     */
    public function basicQos(array $qos): void
    {
        if ($this->channel && $this->checkQosParams($qos)) {
            $this->channel->basic_qos($qos['qosSize'], $qos['qosCount'], (bool)$qos['qosGlobal']);
        }
    }

    /**
     * @throws CouldNotCreateTemporalQueueException
     */
    public function createTemporalQueue(string $exchange): string
    {
        $channel = $this->getChannel();
        if (!$channel) {
            throw new RuntimeException('No channel');
        }

        $queueCreatedResult = $channel->queue_declare('', false, false, true, true);
        if ($queueCreatedResult === null || !isset($queueCreatedResult[0])) {
            throw new CouldNotCreateTemporalQueueException('No queue created');
        }

        $queueName = $queueCreatedResult[0];
        if (!$queueName || !is_string($queueName)) {
            throw new CouldNotCreateTemporalQueueException('Invalid queue name in queue_declare response: ' . var_export($queueCreatedResult, true));
        }
        $channel->queue_bind($queueName, $exchange, $queueName);

        return $queueName;
    }

    public function consume(string $queueName, string $consumerTag, bool $noLocal, bool $noAck, bool $exclusive, bool $noWait, Closure $closure): void
    {
        $chan = $this->getChannel();
        if (!$chan) {
            throw new RuntimeException('No channel');
        }

        $chan->basic_consume($queueName, $consumerTag, $noLocal, $noAck, $exclusive, $noWait, $closure);
    }

    public function wait(): void
    {
        if ($this->channel === null) {
            throw new RuntimeException('No channel');
        }

        while ($this->channel->is_open()) {
            $this->channel->wait();
        }
    }

    /**
     * @param array<string, int|bool> $qos
     *
     * @return bool
     */
    private function checkQosParams(array $qos): bool
    {
        return (array_key_exists('qosSize', $qos) &&
                array_key_exists('qosCount', $qos) &&
                array_key_exists('qosGlobal', $qos));
    }

    private function connect(int $retries = 0): AMQPStreamConnection
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        try {
            $connection = new AMQPStreamConnection(
                $this->host,
                $this->port,
                $this->user,
                $this->pass,
                $this->vhost,
                false,
                'AMQPLAIN',
                null,
                'en_US',
                5.0,
                3.0,
                null,
                true
            );
            $this->connection = $connection;
            $this->channel = $connection->channel();
            $this->basicQos([
                'qosSize'   => 0,
                'qosCount'  => 1,
                'qosGlobal' => 0,
            ]);
            return $connection;
        } catch (Throwable $e) {
            if ($retries > 4) {
                throw new RuntimeException(
                    sprintf('No se ha podido conectar al rabbit después de %d intentos: %s', $retries, $e->getMessage())
                );
            }

            ++$retries;
            return $this->connect($retries);
        }
    }
}
