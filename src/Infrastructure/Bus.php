<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 *
 * @Target("CLASS")
 */
final class Bus
{
    public const DEFAULT = 'default';

    /** @var string */
    public string $bus = self::DEFAULT;
}
