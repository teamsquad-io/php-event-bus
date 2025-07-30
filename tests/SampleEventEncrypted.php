<?php

declare(strict_types=1);

namespace TeamSquad\Tests;

use RuntimeException;
use TeamSquad\EventBus\Domain\EncryptedEvent;

use function is_string;

class SampleEventEncrypted implements EncryptedEvent
{
    private string $userId;
    private string $password;

    public function __construct(string $userId, string $password)
    {
        $this->userId = $userId;
        $this->password = $password;
    }

    public static function protectedFields(): array
    {
        return [
            'password',
        ];
    }

    public function eventName(): string
    {
        return 'sample.encrypted.event';
    }

    public function toArray(): array
    {
        return [
            'userId' => $this->userId,
            'password' => $this->password,
        ];
    }

    /**
     * @param array<string, mixed> $array
     *
     * @return SampleEventEncrypted
     */
    public static function fromArray(array $array): self
    {
        if (!is_string($array['userId']) || !is_string($array['password'])) {
            throw new RuntimeException('Array must have string values');
        }

        return new self(
            $array['userId'],
            $array['password']
        );
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function password(): string
    {
        return $this->password;
    }
}
