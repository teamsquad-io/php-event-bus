<?php

namespace TeamSquad\EventBus\Domain;

interface DatabaseConnection
{
    public function activate(): void;
    public function disable(): void;
    public function reset(): void;

    public function beginTransaction(): void;
    public function commitTransaction(): void;
    public function rollbackTransaction(): void;
}
