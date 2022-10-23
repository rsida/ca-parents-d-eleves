<?php

namespace App\Command;

use App\Entity\Article;
use App\Entity\ArticleMedia;
use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Faker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:article:create:fake',
    description: 'Create multiple fake article',
)]
class ArticleCreateFakeCommand extends Command
{
    private ArticleRepository $articleRepository;
    private UserRepository $userRepository;
    private ObjectRepository $mediaRepository;
    private string $env;

    public function __construct(
        ArticleRepository $articleRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        string $env,
        string $name = null
    ) {
        $this->articleRepository = $articleRepository;
        $this->userRepository = $userRepository;
        $this->mediaRepository = $entityManager->getRepository(ArticleMedia::class);
        $this->env = $env;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'Number of Article you want to create')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if ($this->env !== 'dev') {
            $io->warning('Command must be executed only in DEV mode.');
            return Command::SUCCESS;
        }

        $countOpt = (int) $input->getOption('count');
        $count = $countOpt === 0 ? 1 : $countOpt;

        $faker = Faker\Factory::create('fr_FR');
        $this->createArticles($faker, $this->createUsers($faker, $count));

        return Command::SUCCESS;
    }

    protected function createUsers(Faker\Generator $faker, int $count): array
    {
        $users = [];
        for ($i = 0; $i < $count; $i++) {
            $gender = User::GENDERS[array_rand(User::GENDERS)];
            $user = new User();
            $user
                ->addRole('ROLE_ARTICLE')
                ->setGender($gender)
                ->setFirstName($gender === User::GENDER_MALE ? $faker->firstNameMale : $faker->firstNameFemale)
                ->setLastName($faker->lastName)
                ->setEmail($faker->email)
                ->setIsActive(false)
                ->setIsVerified(true)
                ->setPassword($faker->password);
            $this->userRepository->save($user, true);
            $users[] = $user;
        }

        return $users;
    }

    protected function createArticles(Faker\Generator $faker, array $users): void
    {
        $medias = $this->mediaRepository->findAll();

        foreach ($users as $user) {
            /** @var ArticleMedia $originalMedia */
            $originalMedia = $medias[array_rand($medias)];
            $newMedia = new ArticleMedia();
            $newMedia
                ->setPath($originalMedia->getPath())
                ->setMimeType($originalMedia->getMimeType())
                ->setExtension($originalMedia->getExtension())
                ->setExtra($originalMedia->getExtra())
                ->setOriginalName($originalMedia->getOriginalName())
                ->setName($originalMedia->getName());

            $date = new \DateTimeImmutable($faker->date('Y-m-d H:i:s'), new \DateTimeZone('Europe/Paris'));
            $article = new Article();
            $article
                ->addMedia($newMedia)
                ->setTitle('Consectetur adipiscing elit')
                ->setAuthor($user)
                ->setCreatedAt($date)
                ->setUpdatedAt($date)
                ->setDescription('<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Tristique senectus et netus et. At augue eget arcu dictum varius duis at consectetur lorem. Risus commodo viverra maecenas accumsan lacus vel facilisis volutpat est. Felis bibendum ut tristique et egestas quis ipsum suspendisse ultrices. Cursus vitae congue mauris rhoncus aenean vel. Consectetur adipiscing elit duis tristique sollicitudin nibh sit. Erat pellentesque adipiscing commodo elit at imperdiet dui. Amet venenatis urna cursus eget nunc scelerisque viverra. Aliquam sem fringilla ut morbi tincidunt augue. Cras pulvinar mattis nunc sed blandit libero volutpat sed. Aliquet bibendum enim facilisis gravida neque convallis a. Rhoncus est pellentesque elit ullamcorper dignissim cras tincidunt lobortis feugiat. A lacus vestibulum sed arcu non odio euismod lacinia at.</p>');

            $this->articleRepository->save($article, true);
        }
    }
}
