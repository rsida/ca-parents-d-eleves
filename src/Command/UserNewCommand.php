<?php

namespace App\Command;

use App\Entity\User;
use App\Event\UserEvent;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'app:user:new',
    description: 'This command allow you to create a new User',
)]
class UserNewCommand extends Command
{
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $userPasswordHasher;
    private EventDispatcherInterface $dispatcher;
    private array $hierarchyRoles;

    public function __construct(
        UserRepository $userRepository,
        UserPasswordHasherInterface $userPasswordHasher,
        EventDispatcherInterface $dispatcher,
        array $hierarchyRoles,
        string $name = null
    ) {
        parent::__construct($name);
        $this->userRepository = $userRepository;
        $this->userPasswordHasher = $userPasswordHasher;
        $this->dispatcher = $dispatcher;
        $this->hierarchyRoles = $hierarchyRoles;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $user = new User();

        $user->setGender($io->choice('What is the User gender?', User::GENDERS));
        $user->setFirstName($io->ask('What is the User firstname?', null, function ($inputFirstname) {
            if (empty($inputFirstname)) {
                throw new \InvalidArgumentException('Given firstname is not valid!');
            }

            return $inputFirstname;
        }));

        $user->setLastName($io->ask('What is the User lastname?', null, function ($inputLastname) {
            if (empty($inputLastname)) {
                throw new \InvalidArgumentException('Given lastname is not valid!');
            }

            return $inputLastname;
        }));

        $user->setEmail($io->ask('What is the User email address?', null, function ($inputEmail) {
            if (!preg_match('/^[a-z0-9._\-]+@[a-z0-9._\-]+\.[a-z]+$/', strtolower($inputEmail))) {
                throw new \InvalidArgumentException('Given email is not valid!');
            }

            return $inputEmail;
        }));

        $password = $io->ask('What is the User password? (Min 6 digits)', null, function ($inputPassword) {
            if (!is_string($inputPassword) || strlen($inputPassword) < 6) {
                throw new \InvalidArgumentException('Given password is not valid!');
            }

            return $inputPassword;
        });

        $io->ask('Repeat password', null, function ($inputPassword) use ($password) {
            if ($inputPassword !== $password) {
                throw new \InvalidArgumentException('Password does not match the previous one!');
            }

            return $inputPassword;
        });

        $user->setPassword($this->userPasswordHasher->hashPassword(
            $user,
            $password
        ));

        do {
            $user->addRole($io->choice('Which role do you want to add?', array_keys($this->hierarchyRoles)));
        } while ($io->confirm('Add another role?'));

        $this->userRepository->save($user, true);
        $this->dispatcher->dispatch(new UserEvent($user), UserEvent::USER_CREATE);

        return Command::SUCCESS;
    }
}
