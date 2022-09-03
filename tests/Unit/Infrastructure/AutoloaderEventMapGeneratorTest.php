<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace TeamSquad\Tests\Unit\Infrastructure;

use PHPUnit\Framework\TestCase;
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
        unlink(self::EVENT_MAP_FILE_PATH);
    }

    public function test_generate_event_map(): void
    {
        $sut = new AutoloaderEventMapGenerator(
            __DIR__ . '/../../../vendor',
            self::EVENT_MAP_FILE_PATH,
            [
                AutoloadConfig::WHITE_LIST_CONFIG_KEY => 'TeamSquad',
            ]
        );

        self::assertFileExists(self::EVENT_MAP_FILE_PATH);
        self::assertEquals([
            'sample_event'        => SampleEvent::class,
            'sample_secure_event' => SampleSecureEvent::class,
        ], $sut->getAll());
    }

    public function test_generate_event_map_creates_file_with_correct_content(): void
    {
        $sut = new AutoloaderEventMapGenerator(
            __DIR__ . '/../../../vendor',
            self::EVENT_MAP_FILE_PATH,
            [
                AutoloadConfig::WHITE_LIST_CONFIG_KEY => 'TeamSquad',
            ]
        );

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
        $sut = new AutoloaderEventMapGenerator(
            __DIR__ . '/../../../vendor',
            self::EVENT_MAP_FILE_PATH,
            [
                AutoloadConfig::WHITE_LIST_CONFIG_KEY => 'TeamSquad',
                AutoloadConfig::BLACK_LIST_CONFIG_KEY => 'Secure',
            ]
        );

        self::assertFileExists(self::EVENT_MAP_FILE_PATH);
        self::assertEquals([
            'sample_event'        => SampleEvent::class,
        ], $sut->getAll());
    }

    public function test_get_by_routing_key_returns_correct_class(): void
    {
        $sut = new AutoloaderEventMapGenerator(
            __DIR__ . '/../../../vendor',
            self::EVENT_MAP_FILE_PATH,
            [
                AutoloadConfig::WHITE_LIST_CONFIG_KEY => 'TeamSquad',
                AutoloadConfig::BLACK_LIST_CONFIG_KEY => 'Secure',
            ]
        );

        self::assertEquals(SampleEvent::class, $sut->get('sample_event'));
    }

    public function test_get_by_routing_key_event_that_doesnt_exists_throws(): void
    {
        $this->expectException(UnknownEventException::class);

        $sut = new AutoloaderEventMapGenerator(
            __DIR__ . '/../../../vendor',
            self::EVENT_MAP_FILE_PATH,
            [
                AutoloadConfig::WHITE_LIST_CONFIG_KEY => 'TeamSquad',
                AutoloadConfig::BLACK_LIST_CONFIG_KEY => 'Secure',
            ]
        );

        self::assertEquals(SampleEvent::class, $sut->get('sample_secure_event'));
    }
}
