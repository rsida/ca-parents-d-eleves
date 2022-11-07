<?php

namespace App\Event;

use App\Entity\Message;

class MessageEvent
{
    public const SIMPLE_MESSAGE = 'simple';

    private Message $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }
}
