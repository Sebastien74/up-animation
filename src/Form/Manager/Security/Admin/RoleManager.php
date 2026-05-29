<?php

declare(strict_types=1);

namespace App\Form\Manager\Security\Admin;

use App\Entity\Core\Website;
use App\Entity\Security\Role;
use App\Service\Core\Urlizer;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Form;

/**
 * RoleManager.
 *
 * Manage Role User in admin
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => RoleManager::class, 'key' => 'security_admin_role_form_manager'],
])]
class RoleManager
{
    /**
     * RoleManager constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CoreLocatorInterface $coreLocator,
    ) {
    }

    /**
     * @prePersist
     */
    public function prePersist(Role $role, Website $website, array $interface, Form $form): void
    {
        $this->setName($role);
        $this->entityManager->persist($role);
    }

    /**
     * @preUpdate
     */
    public function preUpdate(Role $role, Website $website, array $interface, Form $form): void
    {
        $this->setName($role);
        $this->entityManager->persist($role);
    }

    /**
     * To clear cache.
     */
    public function clearRolesCache(): void
    {
        $filesystem = new Filesystem();
        $dirname = $this->coreLocator->projectDir().DIRECTORY_SEPARATOR.'var/cache/security';
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
        if ($filesystem->exists($dirname)) {
            $filesystem->remove($dirname);
        }
    }

    /**
     * Set name & slug.
     */
    private function setName(Role $role): void
    {
        $role->setName(strtoupper($role->getName()));
        $role->setSlug(Urlizer::urlize($role->getName()));
    }
}
