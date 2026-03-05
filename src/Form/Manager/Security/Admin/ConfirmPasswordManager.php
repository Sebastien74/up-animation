<?php

declare(strict_types=1);

namespace App\Form\Manager\Security\Admin;

use App\Entity\Security\User;
use App\Form\Model\Security\Admin\PasswordResetModel;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
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
    ['name' => ConfirmPasswordManager::class, 'key' => 'security_admin_confirm_password_form_manager'],
])]
class ConfirmPasswordManager
{
    /**
     * ConfirmPasswordManager constructor.
     */
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordEncoder,
        private readonly EntityManagerInterface      $entityManager,
    )
    {
    }

    /**
     * Set user password.
     *
     * @throws Exception
     */
    public function confirm(PasswordResetModel $passwordResetModel, User $user): void
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

        $user->setResetPasswordDate(new DateTimeImmutable('now', new DateTimeZone('Europe/Paris')));
        $user->setTokenRequest(null);
        $user->setTokenRequestDate(null);
        $user->setAlerts($alerts);
        $user->setResetPassword(false);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
