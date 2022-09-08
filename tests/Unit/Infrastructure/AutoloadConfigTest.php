<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace TeamSquad\Tests\Unit\Infrastructure;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use TeamSquad\EventBus\Domain\SecureEvent;
use TeamSquad\EventBus\Infrastructure\AutoloadConfig;
use TeamSquad\Tests\SampleEvent;

class AutoloadConfigTest extends TestCase
{
    private AutoloadConfig $sut;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sut = new AutoloadConfig(
            [
                AutoloadConfig::WHITE_LIST_CONFIG_KEY => ['TeamsquadIo'],
                AutoloadConfig::BLACK_LIST_CONFIG_KEY => ['Tests'],
            ]
        );
    }

    public function test_is_included_in_white_list_when_white_list_is_empty(): void
    {
        $this->sut = new AutoloadConfig(
            [
                AutoloadConfig::WHITE_LIST_CONFIG_KEY => [],
                AutoloadConfig::BLACK_LIST_CONFIG_KEY => ['Tests'],
            ]
        );
        self::assertTrue($this->sut->isIncludedInWhiteList('TeamsquadIo'));
    }

    public function test_is_included_in_black_list_when_black_list_is_empty(): void
    {
        $this->sut = new AutoloadConfig(
            [
                AutoloadConfig::WHITE_LIST_CONFIG_KEY => [],
                AutoloadConfig::BLACK_LIST_CONFIG_KEY => [],
            ]
        );
        self::assertFalse($this->sut->isIncludedInBlackList('TeamsquadIo'));
    }

    public function test_is_included_with_full_namespace_should_return_correct_response(): void
    {
        $this->sut = new AutoloadConfig(
            [
                AutoloadConfig::WHITE_LIST_CONFIG_KEY => [
                    'TeamSquad\\Tests',
                ],
                AutoloadConfig::BLACK_LIST_CONFIG_KEY => [],
            ]
        );
        self::assertTrue($this->sut->isIncludedInWhiteList(SampleEvent::class));
    }

    public function test_is_included_as_string(): void
    {
        $this->sut = new AutoloadConfig(
            [
                AutoloadConfig::WHITE_LIST_CONFIG_KEY => 'TeamSquad',
            ]
        );
        self::assertTrue($this->sut->isIncludedInWhiteList(SampleEvent::class));
    }

    public function test_is_included_with_full_namespace_should_but_not_correctly_escaped_return_correct_response(): void
    {
        $this->sut = new AutoloadConfig(
            [
                AutoloadConfig::WHITE_LIST_CONFIG_KEY => [
                    'TeamSquad\Tests',
                ],
                AutoloadConfig::BLACK_LIST_CONFIG_KEY => [],
            ]
        );
        self::assertTrue($this->sut->isIncludedInWhiteList(SampleEvent::class));
    }

    /**
     * @dataProvider classNamesProvider
     */
    public function test_is_included_in_white_list(string $className, bool $expected, bool $_): void
    {
        $actual = $this->sut->isIncludedInWhiteList($className);
        self::assertSame($expected, $actual);
    }

    /**
     * @dataProvider classNamesProvider
     */
    public function test_is_excluded_in_black_list(string $className, bool $_, bool $expected): void
    {
        $actual = $this->sut->isIncludedInBlackList($className);
        self::assertSame($expected, $actual);
    }

    /**
     * @return array<array-key, array<array-key, string|bool>>
     */
    public function classNamesProvider(): array
    {
        return [
            ['TeamsquadIo\SlackService\Domain\Events\AdminCall', true, false],
            [SecureEvent::class, false, false],
            ['TeamSquad\Tests\Domain\SecureEvent', false, true],
            [Yaml::class, false, false],
        ];
    }
}
