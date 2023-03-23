<?php

declare(strict_types=1);

namespace TeamSquad\Tests\Unit\Application\CommandHandler;

use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use TeamSquad\Tests\SampleVideoPermissionChangeCommand;

class CommandBusTest extends TestCase
{
    public function test_command_bus(): void
    {
        $collection = new CommandBus([
            new SampleCommandHandler(),

                                     ]);

        $result = $collection->handle(new SampleVideoPermissionChangeCommand('123', true));
        self::assertEquals('sample command handler was executed', $result);
    }
}
