<?php

declare(strict_types=1);

namespace App\Form\Manager\Security\Front;

use App\Entity\Security\UserFront;
use App\Form\Model\Security\Front\PasswordResetModel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * ConfirmPasswordManager.
 *
 * Manage User security password
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ConfirmPasswordManager::class, 'key' => 'security_front_confirm_password_form_manager'],
])]
class ConfirmPasswordManager
{
    private const int TOKEN_LIMIT = 60 * 24; /** minutes */

    /**
     * ConfirmPasswordManager constructor.
     */
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordEncoder,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Check if user token is not too old.
     *
     * @throws \Exception
     */
    public function checkUser(?string $token = null): ?UserFront
    {
        $user = null;
        if ($token) {
            $user = $this->entityManager->getRepository(UserFront::class)->findOneBy(['tokenRequest' => $token]);
            if ($user instanceof UserFront && $this->isInvalidToken($user)) {
                $user->setTokenRequest(null);
                $user->setTokenRequestDate(null);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                $user = null;
            }
        }

        return $user;
    }

    /**
     * Check if is an invalid token.
     *
     * @throws \Exception
     */
    private function isInvalidToken(UserFront $user): ?bool
    {
        if (!$user->getTokenRequest() || !$user->getToken()) {
            return null;
        }

        $time = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $userTokenRequest = $user->getTokenRequestDate()->format('Y-m-d H:i:s');
        $userTokenRequest = new \DateTime($userTokenRequest, new \DateTimeZone('Europe/Paris'));
        $diff = $userTokenRequest->diff($time);

        return intval($diff->format('%i')) >= self::TOKEN_LIMIT;
    }

    /**
     * Set user password.
     *
     * @throws \Exception
     */
    public function confirm(PasswordResetModel $passwordResetModel, UserFront $user): bool
    {
        $user->setPassword(
            $this->passwordEncoder->hashPassword($user, $passwordResetModel->getPlainPassword())
        );

        $slugsAlert = ['password-info', 'password-alert'];
        $alerts = $user->getAlerts();
        foreach ($slugsAlert as $key => $slug) {
            if (in_array($slug, $alerts)) {
                unset($alerts[$key]);
            }
        }

        $user->setResetPasswordDate(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
        $user->setTokenRequest(null);
        $user->setTokenRequestDate(null);
        $user->setAlerts($alerts);
        $user->setResetPassword(false);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return true;
    }
}
