<?php

declare(strict_types=1);

namespace App\Service\Development\Zend;

use App\Entity\Core\Website;
use App\Entity\Security\Group;
use App\Entity\Security\User;
use App\Service\Doctrine\SqlService;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * UserBackImport.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UserBackImport
{
    /**
     * UserBackImport constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SqlService $sqlService,
        private readonly string $logDir,
    ) {
    }

    /**
     * Import Users Back.
     *
     * @throws Exception|\Exception
     */
    public function import(Website $website, ?Group $group = null, ?SymfonyStyle $io = null): void
    {
        $usersBack = $this->sqlService->findAll('fxc_users_back');
        $repository = $this->entityManager->getRepository(User::class);
        $locale = $website->getConfiguration()->getLocale();
        $group = $group ?: $this->entityManager->getRepository(Group::class)->findOneBy(['slug' => 'administrator']);

        if ($io instanceof SymfonyStyle && count($usersBack) > 0) {
            $io->write('<comment>Users Back extraction progressing...</comment>');
            $io->newLine();
            $io->progressStart(count($usersBack));
        } elseif ($io instanceof SymfonyStyle) {
            $io->write('<comment>No Users admin were found.</comment>');
        }

        foreach ($usersBack as $userBack) {
            $userBack = (object) $userBack;

            /** @var User $existing */
            $existing = $repository->findExisting($userBack->user_login, $userBack->user_email);

            if (!$existing) {
                $user = new User();
                $user->setLogin($userBack->user_login);
                $user->setEmail($userBack->user_email);
                $user->setFirstName($userBack->user_first_name);
                $user->setLastName($userBack->user_last_name);
                $user->setPassword($userBack->user_password);
                $user->setAgreesTermsAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                $user->setLocale($locale);
                $user->setResetPassword(true);
                $user->setGroup($group);

                $this->entityManager->persist($user);
                $this->entityManager->flush();
            } else {
                $logger = new Logger('EXISTING_USER_BACK');
                $logger->pushHandler(new RotatingFileHandler($this->logDir.'/zend-import.log', 10, Level::Warning));
                $logger->warning('Login :'.$userBack->user_login.' Email :'.$userBack->user_email);
            }

            if ($io instanceof SymfonyStyle) {
                $io->progressAdvance();
            }
        }

        if ($io instanceof SymfonyStyle && count($usersBack) > 0) {
            $io->progressFinish();
            $io->write('Users Back successfully extracted.');
        }

        if ($io instanceof SymfonyStyle) {
            $io->newLine(2);
        }
    }
}
