<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Domain;

/**
 * This interface is to be implemented by all consumers that want to listen to events automatically.
 */
interface Consumer
{
    public function actionIndex(): void;
}
