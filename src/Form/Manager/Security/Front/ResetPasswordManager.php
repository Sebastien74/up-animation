<?php

declare(strict_types=1);

namespace App\Form\Manager\Security\Front;

use App\Entity\Core\Website;
use App\Entity\Security\UserFront;
use App\Repository\Security\UserFrontRepository;
use App\Service\Core\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ResetPasswordManager.
 *
 * Manage User security reset password
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ResetPasswordManager::class, 'key' => 'security_front_reset_password_form_manager'],
])]
class ResetPasswordManager
{
    /**
     * ResetPasswordManager constructor.
     */
    public function __construct(
        private readonly MailerService $mailer,
        private readonly UserFrontRepository $repository,
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $entityManager,
        private readonly ConfirmPasswordManager $confirmPasswordManager,
    ) {
    }

    /**
     * Send request.
     *
     * @throws Exception
     */
    public function send(array $data, Website $website): array
    {
        $email = $data['email'];
        $user = $this->repository->findOneBy(['email' => $email, 'website' => $website]);

        if (!$user) {
            $session = new Session();
            $session->getFlashBag()->add('error', $this->translator->trans('Aucun compte trouvé pour cet email.', [], 'security_cms'));

            return ['valid' => false];
        }

        $this->confirmPasswordManager->checkUser($user->getToken());

        $resetToken = false;
        if ($user->getResetPasswordDate() instanceof \DateTime) {
            $today = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
            $tokenDate = $user->getResetPasswordDate();
            $interval = $today->diff($tokenDate);
            $hours = $interval->format('%a') > 0 ? $interval->format('%a') * 24 : 0;
            if ($hours >= 24) {
                $resetToken = true;
            }
        }

        if (!$user->getTokenRequest() || $resetToken) {
            $token = $this->setToken($user, $email);
            $this->sendEmail($user, $email, $token, $website);

            return ['valid' => true];
        }

        return [
            'valid' => false,
            'user' => $user,
        ];
    }

    /**
     * Set token.
     *
     * @throws Exception
     */
    private function setToken(UserFront $user, string $email): string
    {
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $token = $user->getEmail() ? bin2hex(random_bytes(45).md5($email)) : null;
        $user->setTokenRequest(str_replace('/', '', $token));
        $user->setTokenRequestDate($now);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $token;
    }

    /**
     * Send email.
     */
    public function sendEmail(UserFront $user, string $email, string $token, Website $website): void
    {
        $this->mailer->setSubject($this->translator->trans('Réinitialisation de votre mot de passe', [], 'security_cms'));
        $this->mailer->setTo([$email]);
        $this->mailer->setTemplate('front/'.$website->getConfiguration()->getTemplate().'/actions/security/email/password-request.html.twig');
        $this->mailer->setArguments(['user' => $user, 'token' => $token]);
        $this->mailer->send();
    }
}
