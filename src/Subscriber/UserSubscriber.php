<?php

namespace App\Subscriber;

use App\Entity\User;
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
    private string $from;

    public function __construct(EmailVerifier $emailVerifier, TranslatorInterface $translator, string $from)
    {
        $this->emailVerifier = $emailVerifier;
        $this->translator = $translator;
        $this->from = $from;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserEvent::USER_CREATE => 'sendVerificationEmailOnNewUser',
            UserEvent::SEND_NEW_VERIFICATION_MAIL => 'sendVerificationEmailOnNewUser',
        ];
    }

    public function sendVerificationEmailOnNewUser(UserEvent $event): void
    {
        /** @var User $user */
        $user = $event->getUser();
        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
            (new TemplatedEmail())
                ->from(new Address($this->from, $this->translator->trans('mail.registration.from.title')))
                ->to($user->getEmail())
                ->subject($this->translator->trans('mail.registration.verify.subject'))
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );
    }
}
