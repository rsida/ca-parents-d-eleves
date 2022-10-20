<?php

namespace App\Event;

use App\Entity\User;

class UserEvent
{
    public const ON_USER_CREATE = 'onUserCreate';

    private User $user;
    private array $context;

    public function __construct(User $user, array $context = [])
    {
        $this->user = $user;
        $this->context = $context;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
