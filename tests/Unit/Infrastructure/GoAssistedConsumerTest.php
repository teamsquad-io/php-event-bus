<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace TeamSquad\Tests\Unit\Infrastructure;

use PHPUnit\Framework\TestCase;
use TeamSquad\EventBus\Infrastructure\Consumer\GoAssistedConsumer;
use TeamSquad\EventBus\Infrastructure\FakeClock;
use TeamSquad\EventBus\Infrastructure\SimpleEncrypt;
use TeamSquad\EventBus\Infrastructure\SimpleEventMapGenerator;
use TeamSquad\Tests\SampleEvent;
use TeamSquad\Tests\SampleSecureEvent;
use TeamSquad\Tests\Unit\SampleController;

final class GoAssistedConsumerTest extends TestCase
{
    public function test_parse_request(): void
    {
        $goAssistedConsumer = new GoAssistedConsumer(
            new SimpleEventMapGenerator([
                'routing_key' => SampleEvent::class,
            ]),
            new SimpleEncrypt(),
            new FakeClock(0)
        );
        $parseRequest = $goAssistedConsumer->parseRequest(
            new SampleController(),
            'listenSampleEvent',
            'routing_key',
            json_encode([
                'property' => 'value',
            ], JSON_THROW_ON_ERROR)
        );
        self::assertSame('{"property":"value"}', $parseRequest);
    }

    public function test_parse_request_encrypted_fields(): void
    {
        $goAssistedConsumer = new GoAssistedConsumer(
            new SimpleEventMapGenerator([
                'sample_secure_event' => SampleSecureEvent::class,
            ]),
            new SimpleEncrypt(),
            new FakeClock(0)
        );
        $parseRequest = $goAssistedConsumer->parseRequest(
            new SampleController(),
            'listenSampleSecureEvent',
            'sample_secure_event',
            json_encode([
                'property' => 'value',
                'encrypted_property' => base64_encode('encrypted_value'),
            ], JSON_THROW_ON_ERROR)
        );
        self::assertSame('{"encrypted_property":"encrypted_value"}', $parseRequest);
    }
}
