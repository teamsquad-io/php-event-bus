<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @psalm-suppress PossiblyInvalidArgument */
declare(strict_types=1);

namespace TeamSquad\Tests\Unit\Infrastructure;

use PHPUnit\Framework\TestCase;
use TeamSquad\EventBus\Domain\EncryptedEvent;
use TeamSquad\EventBus\Domain\Exception\InvalidArguments;
use TeamSquad\EventBus\Infrastructure\AutoloadConfig;
use TeamSquad\Tests\SampleEvent;

class AutoloadConfigTest extends TestCase
{
    /**
     * @return string[][][]
     */
    public function invalidConfigsProvider(): array
    {
        return [
            [
                [
                    AutoloadConfig::CONFIGURATION_PATH_KEY => 'some/path',
                ],
            ],
            [
                [
                    AutoloadConfig::CONFIGURATION_PATH_KEY         => 'some/path',
                    AutoloadConfig::CONSUMER_QUEUE_LISTEN_NAME_KEY => 'some/path',
                ],
            ],
            [
                [
                    AutoloadConfig::CONFIGURATION_PATH_KEY      => 'some/path',
                    AutoloadConfig::EVENT_BUS_EXCHANGE_NAME_KEY => 'exchange_name',
                ],
            ],
        ];
    }

    /**
     * @dataProvider invalidConfigsProvider
     */
    public function test_it_should_fail_if_mandatory_keys_are_not_in_the_config(array $input): void
    {
        $this->expectException(InvalidArguments::class);
        new AutoloadConfig($input);
    }

    public function test_is_included_in_white_list_when_white_list_is_empty(): void
    {
        $sut = new AutoloadConfig(
            [
                AutoloadConfig::CONFIGURATION_PATH_KEY         => 'some/path',
                AutoloadConfig::CONSUMER_QUEUE_LISTEN_NAME_KEY => 'some/path',
                AutoloadConfig::EVENT_BUS_EXCHANGE_NAME_KEY    => 'exchange_name',
                AutoloadConfig::WHITE_LIST_CONFIG_KEY          => [],
                AutoloadConfig::BLACK_LIST_CONFIG_KEY          => ['Tests'],
            ]
        );
        self::assertTrue($sut->isIncludedInWhiteList('TeamsquadIo'));
    }

    public function test_is_included_in_black_list_when_black_list_is_empty(): void
    {
        $sut = new AutoloadConfig(
            [
                AutoloadConfig::CONFIGURATION_PATH_KEY         => 'some/path',
                AutoloadConfig::CONSUMER_QUEUE_LISTEN_NAME_KEY => 'some/path',
                AutoloadConfig::EVENT_BUS_EXCHANGE_NAME_KEY    => 'exchange_name',
                AutoloadConfig::WHITE_LIST_CONFIG_KEY          => [],
                AutoloadConfig::BLACK_LIST_CONFIG_KEY          => [],
            ]
        );
        self::assertFalse($sut->isIncludedInBlackList('TeamsquadIo'));
    }

    public function test_is_included_with_full_namespace_should_return_correct_response(): void
    {
        $sut = new AutoloadConfig(
            [
                AutoloadConfig::CONFIGURATION_PATH_KEY         => 'some/path',
                AutoloadConfig::CONSUMER_QUEUE_LISTEN_NAME_KEY => 'some/path',
                AutoloadConfig::EVENT_BUS_EXCHANGE_NAME_KEY    => 'exchange_name',
                AutoloadConfig::WHITE_LIST_CONFIG_KEY          => [
                    'TeamSquad\\Tests',
                ],
                AutoloadConfig::BLACK_LIST_CONFIG_KEY          => [],
            ]
        );
        self::assertTrue($sut->isIncludedInWhiteList(SampleEvent::class));
    }

    public function test_is_included_as_string(): void
    {
        $sut = new AutoloadConfig(
            [
                AutoloadConfig::CONFIGURATION_PATH_KEY         => 'some/path',
                AutoloadConfig::CONSUMER_QUEUE_LISTEN_NAME_KEY => 'some/path',
                AutoloadConfig::EVENT_BUS_EXCHANGE_NAME_KEY    => 'exchange_name',
                AutoloadConfig::WHITE_LIST_CONFIG_KEY          => 'TeamSquad',
            ]
        );
        self::assertTrue($sut->isIncludedInWhiteList(SampleEvent::class));
    }

    public function test_is_included_with_full_namespace_should_but_not_correctly_escaped_return_correct_response(): void
    {
        $sut = new AutoloadConfig(
            [
                AutoloadConfig::CONFIGURATION_PATH_KEY         => 'some/path',
                AutoloadConfig::CONSUMER_QUEUE_LISTEN_NAME_KEY => 'some/path',
                AutoloadConfig::EVENT_BUS_EXCHANGE_NAME_KEY    => 'exchange_name',
                AutoloadConfig::WHITE_LIST_CONFIG_KEY          => [
                    'TeamSquad\Tests',
                ],
                AutoloadConfig::BLACK_LIST_CONFIG_KEY          => [],
            ]
        );
        self::assertTrue($sut->isIncludedInWhiteList(SampleEvent::class));
    }

    /**
     * @dataProvider classNamesProvider
     */
    public function test_is_included_in_white_list(string $className, bool $expected, bool $_): void
    {
        $sut = new AutoloadConfig(
            [
                AutoloadConfig::CONFIGURATION_PATH_KEY         => 'some/path',
                AutoloadConfig::CONSUMER_QUEUE_LISTEN_NAME_KEY => 'some/path',
                AutoloadConfig::EVENT_BUS_EXCHANGE_NAME_KEY    => 'exchange_name',
                AutoloadConfig::WHITE_LIST_CONFIG_KEY          => ['TeamsquadIo'],
                AutoloadConfig::BLACK_LIST_CONFIG_KEY          => ['Tests'],
            ]
        );
        $actual = $sut->isIncludedInWhiteList($className);
        self::assertSame($expected, $actual);
    }

    /**
     * @dataProvider classNamesProvider
     */
    public function test_is_excluded_in_black_list(string $className, bool $_, bool $expected): void
    {
        $sut = new AutoloadConfig(
            [
                AutoloadConfig::CONFIGURATION_PATH_KEY         => 'some/path',
                AutoloadConfig::CONSUMER_QUEUE_LISTEN_NAME_KEY => 'some/path',
                AutoloadConfig::EVENT_BUS_EXCHANGE_NAME_KEY    => 'exchange_name',
                AutoloadConfig::WHITE_LIST_CONFIG_KEY          => ['TeamsquadIo'],
                AutoloadConfig::BLACK_LIST_CONFIG_KEY          => ['Tests'],
            ]
        );
        $actual = $sut->isIncludedInBlackList($className);
        self::assertSame($expected, $actual);
    }

    /**
     * @return array<array-key, array<array-key, string|bool>>
     */
    public function classNamesProvider(): array
    {
        return [
            ['TeamsquadIo\SlackService\Domain\Events\AdminCall', true, false],
            [EncryptedEvent::class, false, false],
            ['TeamSquad\Tests\Domain\SecureEvent', false, true],
        ];
    }
}
