<?php

namespace TeamSquad\EventBus\Infrastructure\Amqp;

use JsonException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use TeamSquad\EventBus\Domain\Exception\ConnectionException;
use TeamSquad\EventBus\Domain\Exception\InvalidArguments;
use Throwable;
use function is_array;
use PhpAmqpLib\Wire\AMQPTable;

class RabbitWrapper
{
    protected static self $instance;
    protected ?AMQPChannel $channel;
    protected ?AMQPStreamConnection $connection;
    protected $host;
    protected $port;
    protected $user;
    protected $pass;
    protected $virtualHost;

    /**
     * RabbitWrapper constructor.
     * @param null $host
     * @param null $port
     * @param null $user
     * @param null $pass
     * @param null $vhost
     * @deprecated
     */
    public function __construct($host = null, $port = null, $user = null, $pass = null, $vhost = null)
    {
        if (!$host) {
            $host = getenv('rabbit.host');
        }
        if (!$port) {
            $port = getenv('rabbit.port');
        }
        if (!$user) {
            $user = getenv('rabbit.user');
        }
        if (!$pass) {
            $pass = getenv('rabbit.pass');
        }
        if (!$vhost) {
            $vhost = getenv('rabbit.vhost');
            if (!$vhost) {
                $vhost = "/";
            }
        }

        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
        $this->virtualHost = $vhost;
        $this->channel = null;
        $this->connection = null;
    }

    /**
     * @throws ConnectionException
     */
    private function connect($retries = 0): void
    {
        if ($this->connection !== null && $this->channel !== null) {
            return;
        }
        try {
            $this->connection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->pass, $this->virtualHost, false, 'AMQPLAIN', null, 'en_US', 5.0, 3.0, null, true);
            $this->channel = $this->connection->channel();
            $this->basicQos([
                'qosSize'   => null,
                'qosCount'  => 1,
                'qosGlobal' => false,
            ]);
        } catch (Throwable $e) {
            $shortClassName = substr(strrchr(get_class($this), '\\'), 1);
            if ($retries > 4) {
                Metrics::getInstance()->increment('dev.rabbitmq.error', 1, ['rabbit_type' => $shortClassName]);
                throw new ConnectionException($retries, "Cant connect to RabbitMQ server");
            }
            Metrics::getInstance()->increment('dev.rabbitmq.retries', 1, ['rabbit_type' => $shortClassName]);
            $this->connect(++$retries);
        }
    }

    /**
     * ONLY FOR TEST!!
     * @testOnly
     * @param $instance
     */
    public static function setInstance($instance): void
    {
        self::$instance = $instance;
    }

    /**
     * @throws ConnectionException
     */
    public function getChannel(): AMQPChannel
    {
        $this->connect();
        return $this->channel;
    }

    public static function getInstance(
        string $host = null,
        string $port = null,
        string $user = null,
        string $pass = null,
        string $vhost = null): RabbitWrapper
    {
        if (!static::$instance) {
            $ems = new DirectAirbrakeErrorHandler();
            $parent_exception_handler = set_exception_handler([$ems, 'manage']);
            static::$instance = new static($host, $port, $user, $pass, $vhost);
            set_exception_handler($parent_exception_handler);
        }
        return static::$instance;
    }

    /**
     * @throws ConnectionException
     * @throws JsonException
     * @throws InvalidArguments
     */
    public function publish(
        string $exchangeName,
        string $routingKey,
               $message,
        int    $expiration = null,
        array  $applicationHeaders = []): void
    {
        $this->connect();

        if (is_int($expiration) && $expiration < 0) {
            throw new InvalidArguments(
                sprintf(
                    'RabbitWrapper publish called with invalid expiration: %s. Tried to publish message at exchange %s (routing %s)',
                    $expiration, $exchangeName, $routingKey
                )
            );
        }

        if (!$this->channel) {
            throw new InvalidArguments(
                sprintf(
                    'AMQP channel is null. Tried to publish messages at exchange %s (routing %s)',
                    $exchangeName, $routingKey
                )
            );
        }
        $content_type = 'text/plain';
        if (is_array($message)) {
            $content_type = 'application/json';
            $oldMessage = $message;
            $message = json_encode($message, JSON_THROW_ON_ERROR);
            if (!$message) {
                $printableObject = [];
                foreach ($oldMessage as $key => $value) {
                    if (!is_object($value)) {
                        if (is_callable($value)) {
                            $printableObject[$key] = "callable";
                        } elseif (!is_array($value)) {
                            $printableObject[$key] = $value;
                        } else {
                            $printableObject[$key] = "array";
                        }
                    } else {
                        $printableObject[$key] = "class: " . get_class($value);
                    }
                }

                throw new InvalidArguments(
                    sprintf(
                        'RabbitWrapper publish method was called with empty message. Tried publishing a message at exchange %s (routing %s) backtrace: %s',
                        $exchangeName,
                        $routingKey,
                        json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20), JSON_THROW_ON_ERROR) .
                        " last old message: " . json_encode($printableObject, JSON_THROW_ON_ERROR) .
                        " json last error: " . json_last_error_msg()
                    )
                );
            }
        }

        if (!$message) {
            throw new InvalidArguments(
                sprintf
                ('RabbitWrapper publish called with empty message . Trying to publish message in %s(%s) backtrace: %s',
                    $exchangeName,
                    $routingKey,
                    json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20), JSON_THROW_ON_ERROR))
            );
        }

        $extraProperties = [];
        if ($expiration) {
            $extraProperties['expiration'] = $expiration;
        }
        $toSend = new AMQPMessage($message, array_merge([
            'content_type'     => $content_type,
            'content_encoding' => 'utf-8',
            'app_id'           => "",
            'delivery_mode'    => 2,
        ], $extraProperties));

        if ($applicationHeaders) {
            if (!($applicationHeaders instanceof AMQPTable)) {
                $applicationHeaders = new AMQPTable($applicationHeaders);
            }
            $toSend->set("application_headers", $applicationHeaders);
        }
        $this->channel->basic_publish($toSend, $exchangeName, $routingKey);
    }

    public function subscribe($queueName, $routingKey, $callback): void
    {
        $this->connect();

        $this->channel->basic_consume(
            $queueName,
            $routingKey,
            false,
            false,
            false,
            false,
            function ($msg) use ($callback, $queueName, $routingKey) {
                $commandName = "";
                if (isset($_SERVER["argv"]) && count($_SERVER["argv"]) > 1) {
                    $commandName = $_SERVER["argv"][1];
                }

                if (extension_loaded('newrelic')) {
                    newrelic_end_transaction(true);
                    newrelic_start_transaction(ini_get("newrelic . appname"));
                    newrelic_name_transaction($commandName . " / " . $queueName . " / " . $routingKey);
                }

                try {
                    call_user_func($callback, $msg);
                } finally {
                    if (extension_loaded('newrelic')) {
                        newrelic_end_transaction();
                    }
                }
            }
        );
    }


    public function loopAndBlock(): void
    {
        $this->connect();

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }


    public function closeConnection(): void
    {
        if ($this->channel != null) {
            $this->channel->close();
        }
        if ($this->connection != null) {
            $this->connection->close();
        }
    }


    public function declareQueue($name, $passive = false, $durable = true, $exclusive = false, $auto_delete = false, $nowait = false, $arguments = null, $ticket = null): ?array
    {
        $this->connect();
        return $this->channel->queue_declare($name, $passive, $durable, $exclusive, $auto_delete, $nowait, $arguments, $ticket);
    }

    /**
     * @param $exchangeName
     * @param $queueName
     * @param $routingKey
     * @param $type
     * @param array|null $arguments
     * @param bool $durable
     * @param null|array $qos
     *      qosSize prefetch size - prefetch window size in octets, null meaning "no specific limit"
     *      qosCount prefetch count - prefetch window in terms of whole messages
     *      qosGlobal global - global=null to mean that the QoS settings should apply per-consumer, global=true to mean that the QoS settings should apply per-channel
     * @param bool $internal
     */
    public function configureExchangeAndQueue($exchangeName, $queueName, $routingKey, $type, $arguments = null, $durable = true, $qos = null, $internal = false): void
    {
        $this->connect();
        $this->channel->exchange_declare($exchangeName, $type, false, $durable, false, $internal);
        $this->channel->queue_declare($queueName, false, $durable, false, false, false, $arguments);
        $this->basicQos($qos);
        $this->channel->queue_bind($queueName, $exchangeName, $routingKey);
    }

    public function declareQueueAndBindToExchange($exchangeName, $queueName, $routingKey, $arguments = null, $durable = true, $qos = null): void
    {
        $this->connect();
        [$queue_name,] = $this->channel->queue_declare($queueName, false, $durable, false, false, false, $arguments);
        $this->basicQos($qos);
        $this->channel->queue_bind($queue_name, $exchangeName, $routingKey);
    }


    public function basicQos($qos): void
    {
        $this->connect();
        if ($this->checkQosParams($qos)) {
            $this->channel->basic_qos($qos['qosSize'], $qos['qosCount'], $qos['qosGlobal']);
        }
    }


    protected function checkQosParams($qos): bool
    {
        $this->connect();
        if (!$qos || !is_array($qos)) {
            return false;
        }

        return (array_key_exists('qosSize', $qos) && array_key_exists('qosCount', $qos) && array_key_exists('qosGlobal', $qos));
    }

    public function bindQueue($queueName, $exchangeName, $routingKey): void
    {
        $this->connect();
        $this->channel->queue_bind($queueName, $exchangeName, $routingKey);
    }

    public function bindExchange($exchangeDestination, $exchangeSource, $routingKey): void
    {
        $this->connect();
        $this->channel->exchange_bind($exchangeDestination, $exchangeSource, $routingKey);
    }


    public function unbindQueue($queue, $exchangeSource, $routingKey): void
    {
        $this->connect();
        $this->channel->queue_unbind($queue, $exchangeSource, $routingKey);
    }

    public function unbindExchange($exchange, $exchangeSource, $routingKey = ''): void
    {
        $this->connect();
        $this->channel->exchange_unbind($exchange, $exchangeSource, $routingKey);
    }

    public function deleteExchange($exchange): void
    {
        $this->connect();
        $this->channel->exchange_delete($exchange);
    }


    public function declareExchange($exchangeName, $type, $durable = true): void
    {
        $this->connect();
        $this->channel->exchange_declare($exchangeName, $type, false, $durable, false);
    }

    /**
     * @param string $exchangeName
     * @param string $queueName
     */
    public function deleteExchangeAndQueue($exchangeName, $queueName): void
    {
        $this->connect();
        $this->deleteExchange($exchangeName);
        $this->deleteQueue($queueName);
    }

    public function deleteQueue($queueName): void
    {
        $this->connect();
        $this->channel->queue_delete($queueName);
    }

    public function purgeQueue($name)
    {
        $this->connect();
        return $this->channel->queue_purge($name);
    }


    public function wait(): void
    {
        $this->connect();
        $this->channel->wait();
    }

    public function messagesInQueue($queue, $curlWrapper = null): bool
    {
        return false;
    }
}
