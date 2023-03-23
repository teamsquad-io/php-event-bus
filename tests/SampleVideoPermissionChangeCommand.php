<?php

declare(strict_types=1);

namespace TeamSquad\Tests;

use TeamSquad\EventBus\Domain\Command;

class SampleVideoPermissionChangeCommand implements Command
{
    private bool $canView;
    private string $userId;

    public function __construct(
        string $userId,
        bool $canView = true
    ) {
        $this->canView = $canView;
        $this->userId = $userId;
    }

    public function commandName(): string
    {
        return 'video_permission_change';
    }

    /**
     * @param array<string, mixed> $array
     *
     * @return Command
     */
    public static function fromArray(array $array): Command
    {
        return new self(
            (string)$array['userId'],
            (bool)$array['canView']
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'userId' => $this->userId,
            'canView' => $this->canView,
        ];
    }
}
