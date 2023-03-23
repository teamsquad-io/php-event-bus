<?php

namespace TeamSquad\Tests\Unit\Application\CommandHandler;

use League\Tactician\Middleware;

class SampleCommandHandler implements Middleware
{
    public function __construct()
    {
    }
    
    public function execute($command, callable $next)
    {
        return 'sample command handler was executed';
    }
}
