<?php

declare(strict_types=1);

namespace App\Form\Manager\Security\Front;

use App\Entity\Core\Website;
use App\Entity\Security\Group;
use App\Entity\Security\Role;
use App\Entity\Security\UserFront;
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
    ['name' => UserManager::class, 'key' => 'security_front_user_form_manager'],
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
    public function prePersist(UserFront $user, Website $website): void
    {
        $user->setAgreeTerms(true);
        $user->setAgreesTermsAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
        $user->setPassword(
            $this->passwordEncoder->hashPassword($user, $user->getPlainPassword())
        );

        $this->setGroup($user);

        $this->entityManager->persist($user);
    }

    /**
     * @preUpdate
     */
    public function preUpdate(UserFront $user, Website $website, array $interface, Form $form): void
    {
        if ($user->getPlainPassword()) {
            $user->setPassword(
                $this->passwordEncoder->hashPassword($user, $user->getPlainPassword())
            );
        } else {
            $this->pictureManager->execute($user, $form);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * Set User group.
     */
    private function setGroup(UserFront $user): void
    {
        $groupRepository = $this->entityManager->getRepository(Group::class);
        $group = $groupRepository->findOneBy(['slug' => 'front']);

        if (!$group) {
            $position = count($groupRepository->findAll()) + 1;

            $group = new Group();
            $group->setPosition($position);
            $group->setAdminName('Utilisateurs Front');
            $group->setSlug('front');

            $rolesNames = ['ROLE_USER', 'ROLE_SECURE_PAGE', 'ROLE_USER_FRONT'];
            foreach ($rolesNames as $roleName) {
                $role = $this->entityManager->getRepository(Role::class)->findOneBy(['name' => $roleName]);
                $group->addRole($role);
            }

            $this->entityManager->persist($group);
            $this->entityManager->flush();
        }

        $user->setGroup($group);
    }
}
