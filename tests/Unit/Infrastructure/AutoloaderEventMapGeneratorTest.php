<?php

/** @noinspection UnusedFunctionResultInspection */

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace TeamSquad\Tests\Unit\Infrastructure;

use PHPUnit\Framework\TestCase;
use TeamSquad\EventBus\Domain\Exception\InvalidArguments;
use TeamSquad\EventBus\Domain\Exception\UnknownEventException;
use TeamSquad\EventBus\Infrastructure\AutoloadConfig;
use TeamSquad\EventBus\Infrastructure\AutoloaderEventMapGenerator;
use TeamSquad\Tests\SampleEvent;
use TeamSquad\Tests\SampleSecureEvent;

class AutoloaderEventMapGeneratorTest extends TestCase
{
    private const EVENT_MAP_FILE_PATH = __DIR__ . '/eventMapFile.php';

    protected function tearDown(): void
    {
        parent::tearDown();
        if (file_exists(self::EVENT_MAP_FILE_PATH)) {
            unlink(self::EVENT_MAP_FILE_PATH);
        }
    }

    public function test_it_should_fail_if_event_map_file_is_empty(): void
    {
        $this->expectException(InvalidArguments::class);
        $this->expectExceptionMessage('No events found with whitelist "TeamSquad\NonExistent\" and blacklist ""');

        $this->getAutoloaderEventMapGenerator([
            AutoloadConfig::WHITE_LIST_CONFIG_KEY          => 'TeamSquad\\NonExistent\\',
        ]);

        self::assertFileDoesNotExist(self::EVENT_MAP_FILE_PATH);
    }

    public function test_it_should_fail_if_event_map_file_path_is_pointing_to_invalid_directory(): void
    {
        $wrongDir = __DIR__ . '/wrongDirectory';
        $this->expectException(InvalidArguments::class);
        $this->expectExceptionMessage('The directory where the event map file should be saved does not exist: ' . $wrongDir);

        new AutoloaderEventMapGenerator(
            __DIR__ . '/../../../vendor',
            $wrongDir . '/eventMapFile.php',
            [
                AutoloadConfig::CONFIGURATION_PATH_KEY         => __DIR__ . '/../../Wrong',
                AutoloadConfig::EVENT_BUS_EXCHANGE_NAME_KEY    => 'test',
                AutoloadConfig::CONSUMER_QUEUE_LISTEN_NAME_KEY => 'test',
                AutoloadConfig::WHITE_LIST_CONFIG_KEY          => 'TeamSquad\\',
            ]
        );
    }

    public function test_generate_event_map(): void
    {
        $sut = $this->getAutoloaderEventMapGenerator();

        self::assertFileExists(self::EVENT_MAP_FILE_PATH);
        self::assertEquals([
            'sample_event'        => SampleEvent::class,
            'sample_secure_event' => SampleSecureEvent::class,
        ], $sut->getAll());
    }

    public function test_generate_event_map_creates_file_with_correct_content(): void
    {
        $sut = $this->getAutoloaderEventMapGenerator();

        $sut->generate();

        if (file_exists(self::EVENT_MAP_FILE_PATH)) {
            /** @var array<string, string> $eventMap */
            $eventMap = require self::EVENT_MAP_FILE_PATH;
            self::assertEquals([
                'sample_event'        => SampleEvent::class,
                'sample_secure_event' => SampleSecureEvent::class,
            ], $eventMap);
        } else {
            self::fail('File does not exist');
        }
    }

    public function test_generate_event_map_with_excluding_some_events(): void
    {
        $sut = $this->getAutoloaderEventMapGenerator([
            AutoloadConfig::WHITE_LIST_CONFIG_KEY => 'TeamSquad',
            AutoloadConfig::BLACK_LIST_CONFIG_KEY => 'Secure',
        ]);

        self::assertFileExists(self::EVENT_MAP_FILE_PATH);
        self::assertEquals([
            'sample_event' => SampleEvent::class,
        ], $sut->getAll());
    }

    public function test_get_by_routing_key_returns_correct_class(): void
    {
        $sut = $this->getAutoloaderEventMapGenerator();

        self::assertEquals(SampleEvent::class, $sut->get('sample_event'));
    }

    public function test_get_by_routing_key_event_that_doesnt_exists_throws(): void
    {
        $this->expectException(UnknownEventException::class);

        $sut = $this->getAutoloaderEventMapGenerator([
            AutoloadConfig::WHITE_LIST_CONFIG_KEY => 'TeamSquad',
            AutoloadConfig::BLACK_LIST_CONFIG_KEY => 'Secure',
        ]);

        self::assertEquals(SampleEvent::class, $sut->get('sample_secure_event'));
    }

    private function getAutoloaderEventMapGenerator(array $configuration = []): AutoloaderEventMapGenerator
    {
        return new AutoloaderEventMapGenerator(
            __DIR__ . '/../../../vendor',
            self::EVENT_MAP_FILE_PATH,
            array_merge([
                            AutoloadConfig::CONFIGURATION_PATH_KEY    => __DIR__ . '/../../SampleRepo/config',
                            AutoloadConfig::EVENT_BUS_EXCHANGE_NAME_KEY    => 'vts.eventBus',
                            AutoloadConfig::CONSUMER_QUEUE_LISTEN_NAME_KEY => 'consumer.queue.listen',
                            AutoloadConfig::WHITE_LIST_CONFIG_KEY          => 'TeamSquad',
            ], $configuration)
        );
    }
}
