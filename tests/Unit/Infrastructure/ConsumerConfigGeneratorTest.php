<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace TeamSquad\Tests\Unit\Infrastructure;

use PHPUnit\Framework\TestCase;
use TeamSquad\EventBus\Infrastructure\AutoloadConfig;
use TeamSquad\EventBus\Infrastructure\ConsumerConfigGenerator;
use TeamSquad\Tests\SampleConsumer;
use TeamSquad\Tests\SampleConsumerWithAttributes;
use TeamSquad\Tests\SampleConsumerWithWorkers;
use TeamSquad\Tests\SampleManualConsumer;
use function sprintf;

class ConsumerConfigGeneratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Removes all contents from the directory
        $glob = glob(__DIR__ . '/../config/*');
        if ($glob) {
            foreach ($glob as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        if (is_dir(__DIR__ . '/../config')) {
            rmdir(__DIR__ . '/../config');
        }
    }

    public function test_script_composer_run_generate_consumer_config(): void
    {
        $changeDirectoryToRoot = sprintf('cd %s/../../../', __DIR__);
        $composerScript = 'composer run generate-consumer-config';
        $expectedOutput = 'Generated 4 consumers successfully';
        $output = shell_exec($changeDirectoryToRoot . ' & ' . $composerScript);
        self::assertStringContainsString($expectedOutput, $output);
    }

    public function test_generate(): void
    {
        $sut = new ConsumerConfigGenerator(
            __DIR__ . '/../../../vendor',
            new AutoloadConfig([
                'consumer_queue_listen_name' => 'teamsquad.event.listen',
                'event_bus_exchange_name'    => 'teamsquad.event_bus',
                'configuration_path'         => __DIR__ . '/../config',
                'white_list'                 => [
                    'TeamSquad\\',
                ],
                'black_list'                 => [
                    'Composer\\Plugin\\',
                ],
            ])
        );
        $actual = $sut->generate();
        self::assertEquals([
            'consumers'   => [
                [
                    'amqp'         => 'default',
                    'name'         => 'TeamSquad\Tests\SampleConsumer::listenSampleEvent',
                    'routing_key'  => [
                        'sample_event',
                    ],
                    'unique'       => false,
                    'url'          => '/_/tests-sampleconsumer',
                    'queue'        => 'teamsquad.event.listen.Tests.SampleConsumer.listenSampleEvent',
                    'exchange'     => 'teamsquad.event_bus',
                    'function'     => 'listenSampleEvent',
                    'create_queue' => true,
                    'workers'      => 1,
                    'params'       => [
                        'passive'     => false,
                        'durable'     => false,
                        'exclusive'   => false,
                        'auto_delete' => false,
                        'nowait'      => false,
                        'args'        => [
                            'x-expires'   => [
                                'type' => 'int',
                                'val'  => 300000,
                            ],
                            'x-ha-policy' => [
                                'type' => 'string',
                                'val'  => 'all',
                            ],
                        ],
                    ],
                ],
                [
                    'amqp'         => 'chat',
                    'name'         => 'listenWithAttributesName',
                    'routing_key'  => [
                        'routing.key.1',
                        'routing.key.2',
                    ],
                    'unique'       => false,
                    'url'          => '/api/v1/sample/high/throughput',
                    'queue'        => 'high.throughput.queue.1',
                    'exchange'     => 'exchange.name',
                    'function'     => 'listenSampleHighThroughputEvent1',
                    'create_queue' => false,
                    'workers'      => 9,
                    'params'       => [
                        'passive'     => true,
                        'durable'     => true,
                        'exclusive'   => true,
                        'auto_delete' => true,
                        'nowait'      => true,
                        'args'        => [
                            'x-expires' => [
                                'type' => 'int',
                                'val'  => 100000,
                            ],
                        ],
                    ],
                ],
                [
                    'amqp'         => 'default',
                    'name'         => 'TeamSquad\Tests\SampleConsumerWithWorkers::listenSampleHighThroughputEvent',
                    'routing_key'  => [
                        'high.throughput.event',
                    ],
                    'unique'       => false,
                    'url'          => '/_/tests-sampleconsumerwithworkers',
                    'queue'        => 'high.throughput.queue',
                    'exchange'     => 'teamsquad.event_bus',
                    'function'     => 'listenSampleHighThroughputEvent',
                    'create_queue' => true,
                    'workers'      => 10,
                    'params'       => [
                        'passive'     => false,
                        'durable'     => false,
                        'exclusive'   => false,
                        'auto_delete' => false,
                        'nowait'      => false,
                        'args'        => [
                            'x-expires'   => [
                                'type' => 'int',
                                'val'  => 300000,
                            ],
                            'x-ha-policy' => [
                                'type' => 'string',
                                'val'  => 'all',
                            ],
                        ],
                    ],
                ],
                [
                    'amqp'         => 'users',
                    'name'         => 'TeamSquad\Tests\SampleManualConsumer::listen',
                    'routing_key'  => [
                        'user.online',
                    ],
                    'unique'       => false,
                    'url'          => '/_/tests-samplemanualconsumer',
                    'queue'        => 'user.online.queue',
                    'exchange'     => '',
                    'function'     => 'listen',
                    'create_queue' => true,
                    'workers'      => 1,
                    'params'       => [
                        'passive'     => false,
                        'durable'     => false,
                        'exclusive'   => false,
                        'auto_delete' => false,
                        'nowait'      => false,
                        'args'        => [
                            'x-expires'   => [
                                'type' => 'int',
                                'val'  => 300000,
                            ],
                            'x-ha-policy' => [
                                'type' => 'string',
                                'val'  => 'all',
                            ],
                        ],
                    ],
                ],
            ],
            'routes'      => [
                [
                    'pattern' => '/_/tests-sampleconsumer',
                    'route'   => 'tests-sampleconsumer/index',
                ],
                [
                    'pattern' => '/_/tests-sampleconsumerwithattributes',
                    'route'   => 'tests-sampleconsumerwithattributes/index',
                ],
                [
                    'pattern' => '/_/tests-sampleconsumerwithworkers',
                    'route'   => 'tests-sampleconsumerwithworkers/index',
                ],
                [
                    'pattern' => '/_/tests-samplemanualconsumer',
                    'route'   => 'tests-samplemanualconsumer/index',
                ],
            ],
            'controllers' => [
                'tests-sampleconsumer'               => SampleConsumer::class,
                'tests-samplemanualconsumer'         => SampleManualConsumer::class,
                'tests-sampleconsumerwithworkers'    => SampleConsumerWithWorkers::class,
                'tests-sampleconsumerwithattributes' => SampleConsumerWithAttributes::class,
            ],
        ], $actual);
    }
}
