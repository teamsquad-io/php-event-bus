<?php

declare(strict_types=1);

namespace TeamSquad\Tests\Unit\Interfaces;

use Doctrine\Common\Annotations\AnnotationException;
use ReflectionException;
use TeamSquad\EventBus\Domain\Exception\FileNotFound;
use TeamSquad\EventBus\Domain\Exception\InvalidArguments;
use TeamSquad\EventBus\Infrastructure\AutoloadConfig;
use TeamSquad\EventBus\Infrastructure\ConsumerConfigGenerator;

class SampleConfigGeneratorController
{
    /**
     * @throws FileNotFound
     * @throws InvalidArguments
     * @throws ReflectionException
     * @throws AnnotationException
     *
     * @return array<array-key, mixed>
     */
    public static function generateConsumerConfig(): array
    {
        $sut = new ConsumerConfigGenerator(
            __DIR__ . '/../../../vendor',
            new AutoloadConfig(
                [
                    'consumer_queue_listen_name'          => 'teamsquad.event.listen',
                    'event_bus_exchange_name'             => 'teamsquad.eventBus',
                    'configuration_path'                  => __DIR__ . '/../../SampleRepo/config',
                    AutoloadConfig::WHITE_LIST_CONFIG_KEY => [
                        'TeamSquad\\',
                    ],
                    AutoloadConfig::BLACK_LIST_CONFIG_KEY => [
                        'Composer\\Plugin\\',
                    ],
                ]
            )
        );
        return $sut->generate();
    }
}
