<?php

namespace TeamSquad\EventBus\Domain;

use DateTimeImmutable;

interface Clock
{
    public const DEFAULT_TIMEZONE_ETC_UTC = 'Etc/UTC';
    public const DEFAULT_DATETIME_FORMAT_YMD_HIS = 'Y-m-d H:i:s';

    public function now(): DateTimeImmutable;

    public function today(): DateTimeImmutable;

    public function lastWeek(): DateTimeImmutable;

    public function lastSixMonths(): DateTimeImmutable;

    public function setTime(string $time): void;

    public function nowFormatted(): string;

    public function todayFormatted(): string;

    public function timestamp(): int;

    public function __toString(): string;

    public function dateTimeWithMicroTime();

    public function microtime(): float;

    public function currentMilliseconds();
}
