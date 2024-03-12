<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace TeamSquad\Tests\Unit\Infrastructure;

use PHPUnit\Framework\TestCase;
use TeamSquad\EventBus\Infrastructure\AutoloadConfig;
use TeamSquad\EventBus\Infrastructure\ConsumerConfigGenerator;

class ConsumerConfigGeneratorTest extends TestCase
{
    private const DIR_PATH_CONFIGURATION = __DIR__ . '/../config';

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Removes all contents from the directory
        $glob = glob(self::DIR_PATH_CONFIGURATION . '/*');
        if ($glob) {
            foreach ($glob as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        if (is_dir(self::DIR_PATH_CONFIGURATION)) {
            rmdir(self::DIR_PATH_CONFIGURATION);
        }
    }

    public function test_script_composer_run_generate_consumer_config(): void
    {
        $changeDirectoryToRoot = 'cd ' . __DIR__ . '/../../../';
        $composerScript = 'composer run generate-consumer-config';
        $expectedOutput = 'Generated 2 consumers successfully';
        $output = shell_exec($changeDirectoryToRoot . ' & ' . $composerScript);
        self::assertStringContainsString($expectedOutput, $output);
    }

    public function test_generate(): void
    {
        $sut = new ConsumerConfigGenerator(
            __DIR__ . '/../../../vendor',
            new AutoloadConfig([
                                   'consumer_queue_listen_name' => 'teamsquad.event.listen',
                                   'event_bus_exchange_name'    => 'teamsquad.eventBus',
                                   'configuration_path'         => self::DIR_PATH_CONFIGURATION,
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
                    'name'        => 'TeamSquad\EventBus\SampleRepo\SampleConsumer::listenSampleEvent',
                    'routing_key' => [
                        'sample_event',
                    ],
                    'unique'      => false,
                    'url'         => '/_/eventbus-sampleconsumer',
                    'queue'       => 'teamsquad.event.listen.EventBus.SampleConsumer.listenSampleEvent',
                    'exchange'    => 'teamsquad.eventBus',
                    'function'    => 'listenSampleEvent',
                    'params'      => [
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
                    'name'        => 'TeamSquad\EventBus\SampleRepo\SampleConsumerForCommands::handleSampleVideoPermissionChangeCommand',
                    'routing_key' => [
                        'video_permission_change',
                    ],
                    'unique'      => false,
                    'url'         => '/_/eventbus-sampleconsumerforcommands',
                    'queue'       => 'teamsquad.event.listen.EventBus.SampleConsumerForCommands.handleSampleVideoPermissionChangeCommand',
                    'exchange'    => 'teamsquad.eventBus',
                    'function'    => 'handleSampleVideoPermissionChangeCommand',
                    'params'      => [
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
                    'pattern' => '/_/eventbus-sampleconsumer',
                    'route'   => 'eventbus-sampleconsumer/index',
                ],
                [
                    'pattern' => '/_/eventbus-sampleconsumerforcommands',
                    'route'   => 'eventbus-sampleconsumerforcommands/index',
                ],
            ],
            'controllers' => [
                'eventbus-sampleconsumer'            => 'TeamSquad\EventBus\SampleRepo\SampleConsumer',
                'eventbus-sampleconsumerforcommands' => 'TeamSquad\EventBus\SampleRepo\SampleConsumerForCommands',
            ],
        ], $actual);
    }
}
