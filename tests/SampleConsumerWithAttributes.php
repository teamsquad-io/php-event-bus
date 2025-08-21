<?php

declare(strict_types=1);

namespace TeamSquad\Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use TeamSquad\EventBus\Domain\Clock;
use TeamSquad\EventBus\Domain\Consumer;
use TeamSquad\EventBus\Domain\ConsumerConfig;
use TeamSquad\EventBus\Domain\EventMapGenerator;
use TeamSquad\EventBus\Domain\Input;
use TeamSquad\EventBus\Domain\StringEncrypt;
use TeamSquad\EventBus\Infrastructure\PhpInput;
use TeamSquad\EventBus\Infrastructure\SystemClock;
use TeamSquad\EventBus\Interfaces\Consumer\GoAssistedConsumer;

class SampleConsumerWithAttributes implements Consumer
{
    use GoAssistedConsumer;

    public function __construct(
        EventMapGenerator $eventMap,
        StringEncrypt $dataEncrypt,
        ?AnnotationReader $annotationReader = null,
        ?Input $input = null,
        ?Clock $clock = null
    ) {
        $this->eventMap = $eventMap;
        $this->encrypt = $dataEncrypt;
        $this->input = $input ?: new PhpInput();
        $this->clock = $clock ?: new SystemClock();
        $this->annotationReader = $annotationReader ?: new AnnotationReader();

        $this->initializeConsumer($eventMap, $dataEncrypt, $this->annotationReader, $this->input, $this->clock);
    }

    #[ConsumerConfig(
        amqp: 'chat',
        name: 'listenWithAttributesName',
        routingKey: ['routing.key.1', 'routing.key.2'],
        unique: false,
        url: '/api/v1/sample/high/throughput',
        queue: 'high.throughput.queue.1',
        exchange: 'exchange.name',
        function: 'listenSampleHighThroughputEvent1',
        createQueue: false,
        workers: 9,
        args: [
            'x-expires' => [
                'type' => 'int',
                'val' => 100000,
            ],
        ],
        passive: true,
        durable: true,
        exclusive: true,
        autoDelete: true,
        nowait: true,
    )]
    public function listenWithAttributes(HighThroughputEvent $event): string
    {
        return $event->eventName();
    }
}
