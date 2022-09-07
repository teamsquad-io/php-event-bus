<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Domain;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 *
 * @Target("CLASS")
 */
class RenamedEvent
{
    public ?string $old;

    public function __construct()
    {
        $this->old = null;
    }
}
