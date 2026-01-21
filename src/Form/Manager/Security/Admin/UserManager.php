<?php

declare(strict_types=1);

namespace App\Form\Manager\Security\Admin;

use App\Entity\Core\Website;
use App\Entity\Security\User;
use App\Form\Manager\Security\PictureManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\Form;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * UserManager.
 *
 * Manage User in admin
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => UserManager::class, 'key' => 'security_admin_user_form_manager'],
])]
class UserManager
{
    /**
     * UserManager constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordEncoder,
        private readonly PictureManager $pictureManager,
    ) {
    }

    /**
     * @prePersist
     *
     * @throws Exception
     */
    public function prePersist(User $user, Website $website): void
    {
        $user->setAgreeTerms(true);
        $user->setAgreesTermsAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
        $user->setPassword(
            $this->passwordEncoder->hashPassword($user, $user->getPlainPassword())
        );
        $this->entityManager->persist($user);
    }

    /**
     * @preUpdate
     *
     * @throws Exception
     */
    public function preUpdate(User $user, Website $website, array $interface, Form $form): void
    {
        if ($user->getPlainPassword()) {
            $user->setPassword($this->passwordEncoder->hashPassword($user, $user->getPlainPassword()));
            $user->setResetPasswordDate(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
            $user->setResetPassword(false);
        } else {
            $this->pictureManager->execute($user, $form);
        }
        $this->entityManager->persist($user);
    }
}
