<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Core\Domain;
use App\Entity\Core\Website;
use App\Entity\Security\User;
use App\Entity\Security\UserFront;
use App\Service\Core\CronSchedulerService;
use App\Service\Core\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * PasswordExpire.
 *
 * Check if users passwords expire and send email
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PasswordExpire
{
    private int $adminDelay = 365;
    private array $users = [];
    private array $emails = [];
    private array $emailNames = [];
    private array $hosts = [];

    /**
     * PasswordExpire constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerService $mailer,
        private readonly TranslatorInterface $translator,
        private readonly CronSchedulerService $cronSchedulerService,
    ) {
    }

    /**
     * To execute service.
     *
     * @throws Exception
     */
    public function execute(?InputInterface $input = null, ?string $command = null): void
    {
        $websiteRepository = $this->entityManager->getRepository(Website::class);
        $websites = $websiteRepository->findAll();
        $website = $websites[0];
        $this->adminDelay = $website->getSecurity()->getAdminPasswordDelay();

        /* Users Back */
        $this->parseUsers(User::class, 15, 'password-info');
        $this->parseUsers(User::class, 0, 'password-alert');
        $this->cronSchedulerService->logger('[OK] '.User::class.' successfully executed', $input);

        /* Users Front */
        $this->parseUsers(UserFront::class, 15, 'password-info');
        $this->parseUsers(UserFront::class, 0, 'password-alert');
        $this->cronSchedulerService->logger('[OK] '.UserFront::class.' successfully executed', $input);

        $this->getEmails($websites);
        $this->sendEmails();
        $this->cronSchedulerService->logger('[OK] Email successfully sent.', $input);

        $this->cronSchedulerService->logger('[EXECUTED] '.$command, $input);
    }

    /**
     * Get Users.
     *
     * @throws Exception
     */
    private function parseUsers(string $classname, int $delta, string $alert): void
    {
        $users = $this->entityManager->getRepository($classname)->findAll();
        foreach ($users as $user) {
            /** @var User|UserFront $user */
            $findDate = $this->getDateTime($user, $delta);
            if ($user->getResetPasswordDate() < $findDate && $user->isActive()) {
                if (is_array($user->getAlerts()) && !in_array($alert, $user->getAlerts())) {
                    $this->setUser($user, $alert);
                    $userType = $user instanceof User ? 'back' : 'front';
                    $this->users[$userType][$alert][$user->getId()] = $user;
                    if ('password-alert' === $alert && !empty($this->users[$userType]['password-info'][$user->getId()])) {
                        unset($this->users[$userType]['password-info'][$user->getId()]);
                    }
                }
            }
        }
    }

    /**
     * Get DateTime.
     *
     * @throws Exception
     */
    private function getDateTime($user, int $delta): \DateTimeImmutable|bool
    {
        $userDelay = $user instanceof UserFront ? $user->getWebsite()->getSecurity()->getFrontPasswordDelay() : $this->adminDelay;
        $delay = $userDelay - $delta;
        $date = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $findDate = new \DateTimeImmutable($date->format('Y-m-d H:i:s'));

        return $findDate->modify('-'.$delay.' days');
    }

    /**
     * Set User.
     */
    private function setUser($user, string $alert): void
    {
        /** @var UserFront|User $user */
        $alerts = $user->getAlerts();
        $alerts[] = $alert;
        $user->setAlerts(array_unique($alerts));
        $user->setResetPassword(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * Get Websites senders emails.
     */
    private function getEmails(mixed $websites): void
    {
        foreach ($websites as $website) {
            /** @var Website $website */
            foreach ($website->getInformation()->getEmails() as $email) {
                if ('support' === $email->getSlug() || 'no-reply' === $email->getSlug()) {
                    $this->emails[$website->getId()][$email->getLocale()][$email->getSlug()] = $email->getEmail();
                }
            }
        }
    }

    /**
     * Send emails alerts.
     */
    private function sendEmails(): void
    {
        foreach ($this->users as $userType => $alerts) {
            foreach ($alerts as $alertType => $users) {
                foreach ($users as $user) {
                    /** @var User|UserFront $user */
                    $website = $this->getUserWebsite($user);
                    $defaultLocale = $website->getConfiguration()->getLocale();
                    $from = $this->getEmailBySlug($website, $user, 'support', $defaultLocale);
                    $reply = $this->getEmailBySlug($website, $user, 'no-reply', $defaultLocale);
                    $this->mailer->setSubject($this->getEmailSubject($alertType, $user));
                    $this->mailer->setTo([$user->getEmail()]);
                    $this->mailer->setName($this->getMailName($website, $user));
                    $this->mailer->setFrom($from);
                    $this->mailer->setReplyTo($reply);
                    $this->mailer->setTemplate('front/default/actions/security/email/password-expire.html.twig');
                    $this->mailer->setArguments(['expire' => 'password-info' !== $alertType, 'user' => $user, 'website' => $website, 'schemeAndHttpHost' => $this->getSchemeAndHttpHost($website)]);
                    $this->mailer->setLocale($user->getLocale());
                    $this->mailer->send();
                }
            }
        }
    }

    /**
     * Get Email subject.
     */
    private function getEmailSubject(string $alertType, User|UserFront $user): string
    {
        return 'password-info' === $alertType
            ? $this->translator->trans('Votre mot de passe arrive à expiration', [], 'security_cms', $user->getLocale())
            : $this->translator->trans('Votre mot de passe à expiré', [], 'security_cms', $user->getLocale());
    }

    /**
     * Get Email by slug.
     */
    private function getEmailBySlug(Website $website, User|UserFront $user, string $slug, string $defaultLocale): ?string
    {
        return !empty($this->emails[$website->getId()][$user->getLocale()][$slug])
            ? $this->emails[$website->getId()][$user->getLocale()][$slug]
            : $this->emails[$website->getId()][$defaultLocale][$slug];
    }

    /**
     * Get User WebsiteModel.
     */
    private function getUserWebsite(User|UserFront $user): ?Website
    {
        $website = $user instanceof UserFront ? $user->getWebsite() : $user->getWebsites()[0];

        if (!$website) {
            $websites = $this->entityManager->getRepository(Website::class)->findAll();
            foreach ($websites as $websiteConfiguration) {
                if ($websiteConfiguration->getConfiguration()->getDomains()->count() > 0) {
                    $website = $websiteConfiguration;
                    break;
                }
            }
        }

        return $website;
    }

    /**
     * Get User WebsiteModel.
     */
    private function getMailName(Website $website, User|UserFront $user): ?string
    {
        if (!empty($this->emailNames[$website->getId()])) {
            return $this->emailNames[$website->getId()];
        }

        $defaultLocale = $website->getConfiguration()->getLocale();

        foreach ($website->getInformation()->getIntls() as $intl) {
            if ($intl->getLocale() === $user->getLocale()) {
                $this->emailNames[$website->getId()] = $intl->getTitle();
            }
            if (empty($this->emailNames[$website->getId()]) && $intl->getLocale() === $defaultLocale) {
                $this->emailNames[$website->getId()] = $intl->getTitle();
            }
        }

        if (empty($this->emailNames[$website->getId()])) {
            $this->emailNames[$website->getId()] = 'Agence Félix';
        }

        return $this->emailNames[$website->getId()];
    }

    /**
     * Get WebsiteModel schemeAndHttpHost.
     */
    private function getSchemeAndHttpHost(Website $website): ?string
    {
        if (!empty($this->hosts[$website->getId()])) {
            return $this->hosts[$website->getId()];
        }

        $configuration = $website->getConfiguration();
        $domains = $this->entityManager->getRepository(Domain::class)->findBy([
            'configuration' => $configuration,
            'asDefault' => true,
        ]);

        $protocol = $_ENV['APP_PROTOCOL'].'://';
        $domain = $domains ? $protocol.$domains[0]->getName() : null;
        $this->hosts[$website->getId()] = $domain;

        return $domain;
    }
}
