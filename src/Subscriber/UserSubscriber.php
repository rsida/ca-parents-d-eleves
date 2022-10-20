<?php

namespace App\Subscriber;

use App\Event\UserEvent;
use App\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserSubscriber implements EventSubscriberInterface
{
    private EmailVerifier $emailVerifier;
    private TranslatorInterface $translator;

    public function __construct(EmailVerifier $emailVerifier, TranslatorInterface $translator)
    {
        $this->emailVerifier = $emailVerifier;
        $this->translator = $translator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserEvent::ON_USER_CREATE => 'sendVerificationEmailOnNewUser',
        ];
    }

    public function sendVerificationEmailOnNewUser(UserEvent $event): void
    {
        $user = $event->getUser();
        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
            (new TemplatedEmail())
                ->from(new Address('romain.sida@gmail.com', $this->translator->trans('mail.registration.from.title')))
                ->to($user->getEmail())
                ->subject($this->translator->trans('mail.registration.verify.subject'))
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );
    }
}
