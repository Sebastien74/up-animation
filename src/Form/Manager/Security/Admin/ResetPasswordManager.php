<?php

declare(strict_types=1);

namespace App\Form\Manager\Security\Admin;

use App\Entity\Security\User;
use App\Service\Core\MailerService;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * ResetPasswordManager.
 *
 * Manage User security reset password
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ResetPasswordManager::class, 'key' => 'security_admin_reset_password_form_manager'],
])]
class ResetPasswordManager
{
    /**
     * ResetPasswordManager constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly MailerService $mailer,
    ) {
    }

    /**
     * Send request.
     *
     * @throws \Exception
     */
    public function send(array $data): bool
    {
        $email = $data['email'];
        $user = $this->coreLocator->em()->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $session = $this->coreLocator->request()->getSession();
            $session->getFlashBag()->add('error', $this->coreLocator->translator()->trans('Aucun compte trouvé pour cet email.', [], 'security_cms'));

            return false;
        }

        $token = $this->setToken($user, $email);

        $this->sendEmail($user, $email, $token);

        return true;
    }

    /**
     * Set token.
     *
     * @throws \Exception
     */
    private function setToken(User $user, string $email): string
    {
        $token = base64_encode(uniqid().password_hash($email, PASSWORD_BCRYPT).random_bytes(10));
        $token = substr(str_shuffle($token), 0, 30);
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $token = str_replace(['%', '/'], '', $token);

        $user->setTokenRequest($token);
        $user->setTokenRequestDate($now);

        $this->coreLocator->em()->persist($user);
        $this->coreLocator->em()->flush();

        return $token;
    }

    /**
     * Send email.
     */
    private function sendEmail(User $user, string $email, string $token): void
    {
        $subject = $this->coreLocator->translator()->trans('Réinitialisation de votre mot de passe', [], 'security_cms');

        $this->mailer->setSubject($subject);
        $this->mailer->setTo([$email]);
        $this->mailer->setTemplate('security/email/password-request.html.twig');
        $this->mailer->setArguments(['user' => $user, 'token' => $token]);
        $this->mailer->send();

        $session = $this->coreLocator->request()->getSession();
        $session->getFlashBag()->add('success', $this->coreLocator->translator()->trans("Un email vous a été envoyé. Si vous ne l'avez pas reçu, pensez à vérifier dans vos spams.", [], 'security_cms'));
    }
}
