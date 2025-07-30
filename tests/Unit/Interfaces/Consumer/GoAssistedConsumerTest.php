<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace TeamSquad\Tests\Unit\Interfaces\Consumer;

use PHPUnit\Framework\TestCase;
use TeamSquad\EventBus\Infrastructure\SimpleEncrypt;
use TeamSquad\EventBus\Infrastructure\SimpleEventMapGenerator;
use TeamSquad\Tests\SampleConsumer;
use TeamSquad\Tests\SampleEvent;
use TeamSquad\Tests\SampleEventEncrypted;
use TeamSquad\Tests\SampleSecureEvent;
use TeamSquad\Tests\Unit\Interfaces\SampleController;

final class GoAssistedConsumerTest extends TestCase
{
    public function test_parse_request(): void
    {
        $goAssistedConsumer = new SampleConsumer(
            new SimpleEventMapGenerator([
                'routing_key' => SampleEvent::class,
            ]),
            new SimpleEncrypt(),
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
        $goAssistedConsumer = new SampleConsumer(
            new SimpleEventMapGenerator([
                'sample_secure_event' => SampleSecureEvent::class,
            ]),
            new SimpleEncrypt(),
        );
        $parseRequest = $goAssistedConsumer->parseRequest(
            new SampleController(),
            'listenSampleSecureEvent',
            'sample_secure_event',
            json_encode([
                'property'           => 'value',
                'encrypted_property' => base64_encode('encrypted_value'),
            ], JSON_THROW_ON_ERROR)
        );
        self::assertSame('{"encrypted_property":"encrypted_value"}', $parseRequest);
    }

    public function test_parse_request_encrypted_fields_with_encrypted_event(): void
    {
        $goAssistedConsumer = new SampleConsumer(
            new SimpleEventMapGenerator([
                'sample.encrypted.event' => SampleEventEncrypted::class,
            ]),
            new SimpleEncrypt(),
        );
        $parseRequest = $goAssistedConsumer->parseRequest(
            new SampleController(),
            'listenSampleEventEncrypted',
            'sample.encrypted.event',
            json_encode([
                'userId'   => '4a0bd957-214e-4faa-adb9-7c518ca8af80',
                'password' => base64_encode('encrypted password value'),
            ], JSON_THROW_ON_ERROR)
        );
        self::assertSame('{"userId":"4a0bd957-214e-4faa-adb9-7c518ca8af80","password":"encrypted password value"}', $parseRequest);
    }
}
