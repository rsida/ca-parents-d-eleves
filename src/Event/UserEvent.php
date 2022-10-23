<?php

namespace App\Event;

use Symfony\Component\Security\Core\User\UserInterface;

class UserEvent
{
    public const USER_CREATE = 'onUserCreate';
    public const SEND_NEW_VERIFICATION_MAIL = 'onSendNewVerificationMail';

    private UserInterface $user;
    private array $context;

    public function __construct(UserInterface $user, array $context = [])
    {
        $this->user = $user;
        $this->context = $context;
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
