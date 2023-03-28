<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\SampleRepo;

use TeamSquad\EventBus\Domain\AsyncCommand;
use TeamSquad\EventBus\Domain\Command;

class SampleVideoPermissionChangeCommand implements Command
{
    use AsyncCommand;

    private bool $canView;
    private string $userId;

    public function __construct(
        string $userId,
        bool $canView = true
    ) {
        $this->canView = $canView;
        $this->userId = $userId;
    }

    public function eventName(): string
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
        $command = new self(
            (string)$array['userId'],
            (bool)$array['canView']
        );
        if (isset($array['queueToReply'])) {
            $command->setQueueToReply((string)$array['queueToReply']);
        }
        
        return $command;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'userId' => $this->userId,
            'canView' => $this->canView,
        ];
        if ($this->queueToReply !== null) {
            $result['queueToReply'] = $this->queueToReply;
        }
        
        return $result;
    }
}
