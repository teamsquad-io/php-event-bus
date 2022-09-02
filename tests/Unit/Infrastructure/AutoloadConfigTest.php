<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace TeamSquad\Tests\Unit\Infrastructure;

use PHPUnit\Framework\TestCase;
use TeamSquad\EventBus\Infrastructure\AutoloadConfig;

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
        $actual = $this->sut->isExcludedInBlackList($className);
        self::assertSame($expected, $actual);
    }

    /**
     * @return array<array-key, array<array-key, string|bool>>
     */
    public function classNamesProvider(): array
    {
        return [
            ['TeamsquadIo\SlackService\Domain\Events\AdminCall', true, false],
            ['TeamSquad\EventBus\Domain\SecureEvent', false, false],
            ['TeamSquad\Tests\Domain\SecureEvent', false, true],
            ['Symfony\Component\Yaml\Yaml', false, false],
        ];
    }
}
