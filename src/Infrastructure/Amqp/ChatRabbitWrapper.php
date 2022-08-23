<?php

namespace TeamSquad\EventBus\Infrastructure\Amqp;

class ChatRabbitWrapper extends RabbitWrapper
{
    protected static RabbitWrapper $instance;

    public function __construct()
    {
        parent::__construct(
            getenv('chat.rabbit.host'),
            getenv('chat.rabbit.port'),
            getenv('chat.rabbit.user'),
            getenv('chat.rabbit.pass'),
            getenv('chat.rabbit.vhost')
        );
    }

    /**
     * ONLY FOR TEST!!
     * @testOnly
     * @param $instance
     */
    public static function setInstance($instance): void
    {
        self::$instance = $instance;
    }
}
