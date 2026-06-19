<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Security\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:dev:screenshot-user', description: 'Dev-only ephemeral admin used to capture headless screenshots.')]
final class DevScreenshotUserCommand extends Command
{
    private const LOGIN = 'dev-shot';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly KernelInterface $kernel,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('remove', null, InputOption::VALUE_NONE, 'Delete the ephemeral user.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!in_array($this->kernel->getEnvironment(), ['dev', 'local'], true)) {
            $output->writeln('Refusing to run outside a non-production environment.');

            return Command::FAILURE;
        }

        $repository = $this->entityManager->getRepository(User::class);
        $existing = $repository->findOneBy(['login' => self::LOGIN]);

        if ($input->getOption('remove')) {
            if ($existing) {
                $this->entityManager->remove($existing);
                $this->entityManager->flush();
            }
            $output->writeln('REMOVED');

            return Command::SUCCESS;
        }

        if ($existing) {
            $this->entityManager->remove($existing);
            $this->entityManager->flush();
        }

        $source = $repository->find(1);
        if (!$source instanceof User) {
            $output->writeln('Source user #1 not found.');

            return Command::FAILURE;
        }

        $password = bin2hex(random_bytes(12));

        $user = new User();
        $user->setLogin(self::LOGIN);
        $user->setEmail('dev-shot@local.test');
        $user->setLastName('Shot');
        $user->setFirstName('Dev');
        $user->setActive(true);
        $user->setConfirmEmail(true);
        $user->setLocale($source->getLocale() ?: 'fr');
        $user->setGroup($source->getGroup());
        $user->setDisabledAuth(true);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        foreach ($source->getWebsites() as $website) {
            $user->addWebsite($website);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('LOGIN='.self::LOGIN);
        $output->writeln('PASSWORD='.$password);

        return Command::SUCCESS;
    }
}
