<?php

declare(strict_types=1);

namespace App\Form\Manager\Security\Admin;

use App\Entity\Core\Website;
use App\Entity\Security\Group;
use App\Entity\Security\User;
use App\Entity\Security\UserFront;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\Form;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * GroupPasswordManager.
 *
 * Manage User password by Group in admin
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => GroupPasswordManager::class, 'key' => 'security_admin_group_password_form_manager'],
])]
class GroupPasswordManager
{
    /**
     * GroupPasswordManager constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordEncoder,
    ) {
    }

    /**
     * @preUpdate
     */
    public function preUpdate(Group $group, Website $website, array $interface, Form $form): void
    {
        $plainPassword = $form->get('plainPassword')->getData();

        $usersBack = $this->entityManager->getRepository(User::class)->findBy(['group' => $group]);
        $this->setPasswords($usersBack, $plainPassword);

        $usersFront = $this->entityManager->getRepository(UserFront::class)->findBy(['group' => $group]);
        $this->setPasswords($usersFront, $plainPassword);

        $this->entityManager->persist($group);
    }

    /**
     * To set User[] password.
     */
    private function setPasswords(array $users, string $plainPassword): void
    {
        foreach ($users as $user) {
            $user->setPassword(
                $this->passwordEncoder->hashPassword($user, $plainPassword)
            );
            $this->entityManager->persist($user);
        }
    }
}
