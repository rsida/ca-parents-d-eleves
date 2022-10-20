<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:mail:send',
    description: 'This command allow you to send mail',
)]
class MailSendCommand extends Command
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer, string $name = null)
    {
        $this->mailer = $mailer;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $from = $io->ask('What is the sender email address?', null, function ($inputEmail) {
            if (!preg_match('/^[a-z0-9._\-]+@[a-z0-9._\-]+\.[a-z]+$/', strtolower($inputEmail))) {
                throw new \InvalidArgumentException('Given email is not valid!');
            }

            return $inputEmail;
        });

        $to = $io->ask('What is the receiver email address?', null, function ($inputEmail) {
            if (!preg_match('/^[a-z0-9._\-]+@[a-z0-9._\-]+\.[a-z]+$/', strtolower($inputEmail))) {
                throw new \InvalidArgumentException('Given email is not valid!');
            }

            return $inputEmail;
        });

        $subject = $io->ask('What is the subject?');
        $body = $io->ask('What is the mail body content?');

        $email = (new Email())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->html($body);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $io->error(sprintf('Unable to send the mail: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        $io->success('Mail has been successfully sent!');
        return Command::SUCCESS;
    }
}
