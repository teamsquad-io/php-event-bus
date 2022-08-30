<?php

declare(strict_types=1);

namespace TeamSquad\EventBus\Domain;

interface StringEncrypt
{
    /**
     * @param string $data
     *
     * @return string
     */
    public function encrypt(string $data): string;

    /**
     * @param string $data
     *
     * @return string
     */
    public function decrypt(string $data): string;
}
