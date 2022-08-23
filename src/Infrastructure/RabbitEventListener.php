<?php

namespace TeamSquad\EventBus\Infrastructure;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use TeamSquad\EventBus\Core\EventCollection;
use TeamSquad\EventBus\Core\EventListener;
use TeamSquad\EventBus\Core\EventMap;

class RabbitEventListener implements EventListener
{
    private EventMap $eventMap;
    /** @var RabbitWrapper */
    private $rabbitWrapper;
    /** @var ErrorHandler */
    private $errorHandler;
    /** @var DbConnectionHelper */
    private $database;
    /** @var Clock */
    private $clock;

    public function __construct(
        RabbitWrapper $rabbitWrapper,
        EventMap $eventMap,
        ErrorHandler $errorHandler,
        DbConnectionHelper $databaseConnection,
        IClock $clock
    ) {
        $this->rabbitWrapper = $rabbitWrapper;
        $this->errorHandler = $errorHandler;
        $this->eventMap = $eventMap;
        $this->database = $databaseConnection;
        $this->clock = $clock;
    }

    /**
     * @param string|Event|\TeamSquad\EventBus\Core\EventCollection $event
     * @param Callable $callback
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     */
    public function listen($event, callable $callback)
    {
        if ($event instanceof EventCollection) {
            /** @var \TeamSquad\EventBus\Core\EventCollection $event */
            foreach ($event as $e) {
                /** @var Event $e */
                $this->listen($e, $callback);
            }
            $this->waitForEvents();
            return;
        }
        $this->rabbitWrapper->declareExchange(AMQPConstants::VTS_EXCHANGE, AMQPConstants::VTS_EXCHANGE_TYPE, true);
        $uniqueId = "vts.event.listen." . $this->callerConsumer();
        if (is_string($event) && !class_exists($event)) {
            throw new InvalidArgumentException("class $event not found");
        }
        if (!is_string($event) && get_class($event) && $event instanceof Event) {
            $classInstance = $event;
            $event = get_class($event);
        } else {
            $reflectionClass = new \ReflectionClass($event);
            if ($reflectionClass->isInstantiable()) {
                /** @var Event $classInstance */
                $classInstance = $reflectionClass->newInstanceWithoutConstructor();
            } else {
                throw new InvalidArgumentException("${event} is not instanciable");
            }
        }
        $routingKey = $classInstance->eventName();
        $reflectionCallback = new ReflectionFunction($callback);
        $argv = $_SERVER['argv'];
        $queueSuffix = "";
        if ($argv && count($argv) > 2) {
            $queueSuffix = "." . $argv[2];
        }
        $queueName = $uniqueId . "." . $reflectionCallback->getName() . $queueSuffix;
        echo sprintf("[ST][%s] %s \n", $this->now(), $queueName);

        $this->rabbitWrapper->declareQueue($queueName, false, false, false, false, false, [
            'x-expires'   => ['I', 300000],
            "x-ha-policy" => ['S', "all"],
        ]);
        $this->rabbitWrapper->basicQos([
            'qosSize'   => null,
            'qosCount'  => 1,
            'qosGlobal' => false
        ]);
        $this->rabbitWrapper->bindQueue($queueName, AMQPConstants::VTS_EXCHANGE, $routingKey);
        $this->database->disableAllDbConnection();
        $this->rabbitWrapper->subscribe(
            $queueName,
            $routingKey,
            function ($msg) use ($callback, $event, $reflectionCallback) {
                /** @var \PhpAmqpLib\Message\AMQPMessage $msg */
                $routingKey = $msg->delivery_info['routing_key'];
                try {
                    $rawCommand = json_decode(strval($msg->body), true);
                    echo sprintf(
                        "[vv][%s] Received event %s %s \n",
                        $this->now(),
                        $routingKey,
                        strval($msg->body)
                    );
                    $params = [$this->eventMap->unserialize($routingKey, $rawCommand)];
                    if ($reflectionCallback->getNumberOfRequiredParameters() > 1) {
                        if ($msg->has('application_headers')) {
                            /** @var array $headers */
                            $headers = $msg->get('application_headers')->getNativeData();
                            if (extension_loaded('newrelic') && version_compare(phpversion('newrelic'), "9.8", ">=")) {
                                newrelic_accept_distributed_trace_headers($headers, "AMQP");
                            }
                            $params[] = $headers['published_at'] ?? $this->timestamp();
                        } else {
                            $params[] = $this->timestamp();
                        }
                    }
                    $this->database->resetAllDbConnections();
                    call_user_func_array($callback, $params);

                    echo sprintf(
                        "[OK][%s] %s %s",
                        $this->now(),
                        $routingKey,
                        strval($msg->body)
                    );
                } catch (\Throwable $e) {
                    echo sprintf(
                        "[KO][%s] Exception %s %s %s \n %s:%s \n %s \n",
                        $this->now(),
                        $routingKey,
                        strval($msg->body),
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getLine(),
                        $e->getTraceAsString()
                    );
                    $this->errorHandler->logException($e);
                } finally {
                    $this->database->disableAllDbConnection();
                    if (gc_enabled()) {
                        $cycles = gc_collect_cycles();
                    } else {
                        $cycles = -1;
                    }
                    echo sprintf(" {%d}\n", $cycles);
                }
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            }
        );
    }

    /**
     * Devuelve el nombre del consumer que ha llamado al RabbitEventListener y su primer namespace
     * Ejemplo:
     * VtsMedia\Interfaces\Consumers\Video\AudioUpdaterConsumer -> Video.AudioUpdaterConsumer
     * @return string
     */
    private function callerConsumer(): string
    {
        foreach (debug_backtrace() as $back) {
            if ($back['function'] === 'actionIndex') {
                return $this->generateName(get_class($back['object']));
            }
        }
        $fullyQualifiedName = debug_backtrace()[2]['class'];
        return $this->generateName($fullyQualifiedName);
    }

    /**
     * Devuelve el nombre del consumer que ha llamado al RabbitEventListener y su primer namespace
     * Ejemplo:
     * VtsMedia\Interfaces\Consumers\Video\AudioUpdaterConsumer -> Video.AudioUpdaterConsumer
     * @param string $fullyQualifiedName
     * @return string
     */
    private function generateName(string $fullyQualifiedName)
    {
        return RabbitConsumerName::generateName($fullyQualifiedName);
    }

    private function now()
    {
        $microTimePrecise = \DateTimeImmutable::createFromFormat(
            'U.u',
            sprintf('%.6F', microtime(true))
        );
        return $microTimePrecise->format('Y-m-d H:i:s.u');
    }

    private function timestamp(): int
    {
        return $this->clock->timestamp();
    }

    public function waitForEvents()
    {
        $this->rabbitWrapper->loopAndBlock();
    }

    public function start($consumerInstance, bool $isSlowConsumer = false)
    {
        foreach ((new \ReflectionClass($consumerInstance))->getMethods() as $method) {
            if (strpos($method->getName(), 'listen') === 0) {
                if ($method->getNumberOfRequiredParameters() > 0) {
                    $reflectionClass = $method->getParameters()[0]->getClass();
                    if ($reflectionClass->implementsInterface(Event::class) &&
                        $reflectionClass->isInstantiable()) {
                        $this->listen(
                            $reflectionClass->name,
                            $method->getClosure($this)
                        );
                    } else {
                        foreach (spl_autoload_functions() as $autoload_function) {
                            AnnotationRegistry::registerLoader($autoload_function);
                        }
                        $listenAnnotation = (new AnnotationReader())->getMethodAnnotation($method, Listen::class);
                        if (!$listenAnnotation) {
                            throw new AnnotationException("Listening to a non Event class and missing " . Listen::class . " Annotation");
                        }
                        $this->listen($listenAnnotation, $method->getClosure($this));
                    }
                }
            }
        }
        if ($this->slowConsumer) {
            $this->listen(SystemTick::class, function (SystemTick $tick) {
                // THIS IS FOR SLOW CONSUMERS.
                // It enables a tick listener to continually receive messages and not close the connection
            });
        }
        $this->waitForEvents();
    }
}
