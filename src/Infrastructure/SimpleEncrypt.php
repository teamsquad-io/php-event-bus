<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Infrastructure;

use TeamSquad\EventBus\Domain\StringEncrypt;

class SimpleEncrypt implements StringEncrypt
{
    public function encrypt(string $data): string
    {
        return base64_encode($data);
    }

    public function decrypt(string $data): string
    {
        return base64_decode($data);
    }
}
