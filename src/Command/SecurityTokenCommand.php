<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Security\User;
use App\Entity\Security\UserFront;
use App\Service\Core\CronSchedulerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * SecurityTokenCommand.
 *
 * To set user reset password token on NULL after 24H
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(name: 'security:reset:token')]
class SecurityTokenCommand extends Command
{
    private SymfonyStyle $io;

    /**
     * SecurityTokenCommand constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CronSchedulerService $cronSchedulerService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('To set user reset password token on NULL after 24H.')
            ->addArgument('cronLogger', InputArgument::OPTIONAL, 'Cron scheduler Logger')
            ->addArgument('commandLogger', InputArgument::OPTIONAL, 'Command Logger');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->checkTokens(User::class, $input);
        $this->checkTokens(UserFront::class, $input);

        $command = $input->getArgument('command');
        $this->cronSchedulerService->logger('[EXECUTED] '.$command, $input);

        return Command::SUCCESS;
    }

    /**
     * Check & set token.
     *
     * @throws \Exception
     */
    private function checkTokens(string $classname, InputInterface $input): void
    {
        $users = $this->getUsers($classname);
        $tokenProperties = ['token', 'tokenRequest', 'tokenRemoveRequest'];

        foreach ($users as $user) {
            /** @var User|UserFront $user */
            $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
            foreach ($tokenProperties as $property) {
                $getter = 'get'.ucfirst($property);
                $setter = 'set'.ucfirst($property);
                if (method_exists($user, $getter) && method_exists($user, $setter)) {
                    if ($user->$getter()) {
                        $tokenDate = $user->$getter();
                        $tokenDate = new \DateTime($tokenDate->format('Y-m-d H:i:s'), new \DateTimeZone('Europe/Paris'));
                        $tokenDate->add(new \DateInterval('PT2H'));
                        if ($now > $tokenDate) {
                            $user->setToken(null);
                            $this->entityManager->persist($user);
                            $this->entityManager->flush();
                        }
                    }
                }
            }
        }

        $message = '[OK] '.$classname.' tokens successfully reset.';
        $this->io->block($message, 'OK', 'fg=black;bg=green', ' ', true);
        $this->cronSchedulerService->logger($message, $input);
    }

    /**
     * Get Users with token.
     */
    private function getUsers(string $classname): array
    {
        return $this->entityManager->createQueryBuilder()->select('u')
            ->from($classname, 'u')
            ->andWhere('u.token IS NOT NULL')
            ->andWhere('u.tokenRequest IS NOT NULL')
            ->getQuery()
            ->getResult();
    }
}
