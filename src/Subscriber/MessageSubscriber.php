<?php

namespace App\Subscriber;

use App\Event\MessageEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class MessageSubscriber implements EventSubscriberInterface
{
    private MailerInterface $mailer;
    private Environment $twig;
    private LoggerInterface $logger;

    public function __construct(MailerInterface $mailer, Environment $twig , LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::SIMPLE_MESSAGE => 'sendDirectMessage',
        ];
    }

    public function sendDirectMessage(MessageEvent $event): void
    {
        $message = $event->getMessage();
        $to = explode(';', preg_replace('/ /', '', $message->getToEmails()));
        try {
            $mail = (new Email())
                ->from($message->getFromEmail())
                ->to(...$to)
                ->subject($message->getSubject())
                ->html($this->twig->render('mail/simple.html.twig', array_merge([
                    'subject' => $message->getSubject(),
                    'content' => $message->getContent(),
                ], $message->getData())));

            $this->mailer->send($mail);
        } catch (LoaderError|RuntimeError|SyntaxError|TransportExceptionInterface $e) {
            $this->logger->error('Not able to send mail. '.$e->getMessage(), $e->getTrace());
        }
    }
}
