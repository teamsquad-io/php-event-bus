<?php

declare(strict_types=1);

namespace TeamSquad\Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use TeamSquad\EventBus\Domain\Clock;
use TeamSquad\EventBus\Domain\Consumer;
use TeamSquad\EventBus\Domain\EventMapGenerator;
use TeamSquad\EventBus\Domain\Input;
use TeamSquad\EventBus\Domain\StringEncrypt;
use TeamSquad\EventBus\Infrastructure\Manual;
use TeamSquad\EventBus\Infrastructure\PhpInput;
use TeamSquad\EventBus\Infrastructure\SystemClock;
use TeamSquad\EventBus\Interfaces\Consumer\GoAssistedConsumer;

/**
 * @\TeamSquad\EventBus\Infrastructure\Bus(bus="users")
 */
class SampleManualConsumer implements Consumer
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

    /**
     * @Manual(unserializer="raw", queue="user.online.queue", exchange="", routingKey="user.online")
     */
    public function listen(array $event): string
    {
        return 'User online event received';
    }
}
