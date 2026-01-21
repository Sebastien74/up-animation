<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Security\Group;
use App\Entity\Security\Picture;
use App\Entity\Security\Role;
use App\Entity\Security\User;
use App\Service\Core\Urlizer;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

/**
 * SecurityFixtures.
 *
 * Security Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SecurityFixtures extends BaseFixtures
{
    private const array CUSTOMER_ROLES = [
        'ROLE_USER',
        'ROLE_ADMIN',
        'ROLE_ADD',
        'ROLE_EDIT',
        'ROLE_DELETE',
        'ROLE_EXPORT',
        'ROLE_PAGE',
        'ROLE_MEDIA',
        'ROLE_NEWSCAST',
        'ROLE_SEO',
        'ROLE_INFORMATION',
        'ROLE_SLIDER',
        'ROLE_TRANSLATION',
        'ROLE_NAVIGATION',
        'ROLE_CHATBOT',
    ];
    private const array TRANSLATOR_ROLES = [
        'ROLE_USER',
        'ROLE_ADMIN',
        'ROLE_TRANSLATION',
        'ROLE_TRANSLATOR',
    ];

    private int $position = 1;
    private ?User $createdBy = null;

    protected function loadData(ObjectManager $manager): void
    {
        $this->manager = $manager;
        $this->addRoles();
        foreach ($this->getUsers() as $userConfig) {
            $user = $this->addUser($userConfig);
            $this->setPicture($user, $userConfig);
        }
        $this->manager->flush();
    }

    /**
     * Add Roles.
     */
    private function addRoles(): void
    {
        $yamlRoles = $this->getYamlRoles();
        $position = 1;
        foreach ($yamlRoles as $roleName => $config) {
            $adminName = !empty($config['fr']) ? $config['fr'] : $roleName;
            $role = new Role();
            $role->setAdminName($adminName);
            $role->setName($roleName);
            $role->setSlug(Urlizer::urlize($roleName));
            $role->setPosition($position);
            $this->addReference($roleName, $role);
            $this->manager->persist($role);
            ++$position;
        }
    }

    /**
     * Get Yaml Roles.
     */
    private function getYamlRoles(bool $onlyName = false): array
    {
        $securityDirname = $this->projectDir.'/bin/data/fixtures/security.yaml';
        $securityDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $securityDirname);
        $yamlRoles = Yaml::parseFile($securityDirname);

        if ($onlyName) {
            $roles = [];
            foreach ($yamlRoles as $roleName => $config) {
                $roles[] = $roleName;
            }
            return $roles;
        }

        return $yamlRoles;
    }

    /**
     * Get Users configuration.
     */
    private function getUsers(): array
    {
        $users[] = [
            'markup' => '232',
            'email' => 'support@agence-felix.fr',
            'login' => 'webmaster',
            'roles' => $this->getYamlRoles(true),
            'lastname' => 'Agence Félix',
            'group' => $this->translator->trans('Interne', [], 'security'),
            'password' => '$2y$10$yzsckDg/ad8P/MiLzuOPCehisJDkLKfO45LB4u9KtUd.T.LDjFVTq',
            'code' => 'internal',
            'active' => true,
            'picture' => 'webmaster.svg',
        ];

        $users[] = [
            'markup' => '233',
            'email' => 'customer@agence-felix.fr',
            'login' => 'customer',
            'roles' => self::CUSTOMER_ROLES,
            'lastname' => $this->translator->trans('Administrateur', [], 'security'),
            'group' => $this->translator->trans('Administrateur', [], 'security'),
            'password' => '$2y$10$d7fMNRs1DspZZ9KYML4UQuGirin.2N1pgkxFG/tHNmP4e3pLAIlt2',
            'code' => 'administrator',
            'active' => true,
            'picture' => 'customer.png',
        ];

        $users[] = [
            'markup' => '234',
            'email' => 'translator@agence-felix.fr',
            'login' => 'translator',
            'roles' => self::TRANSLATOR_ROLES,
            'lastname' => $this->translator->trans('Traducteur', [], 'security'),
            'group' => $this->translator->trans('Traducteur', [], 'security'),
            'password' => '$2y$10$VGz4ZdbQLjT4gKzT7U3TOeJObWMl3WUjGQZi137HpiaqRcdYwzmbG',
            'code' => 'translator',
            'active' => false,
            'picture' => 'translator.png',
        ];

        return $users;
    }

    /**
     * Add User.
     */
    private function addUser(array $userConfig): User
    {
        $userConfig = (object) $userConfig;

        $user = new User();
        $user->setEmail($userConfig->email);
        $user->setLogin($userConfig->login);
        $user->setLastname($userConfig->lastname);
        $user->setPassword($userConfig->password);
        $user->setActive($userConfig->active);
        $user->setLocale($this->locale);
        $user->setActive(true);
        $user->agreeTerms();

        if (property_exists($userConfig, 'firstname')) {
            $user->setFirstName($userConfig->firstname);
        }

        if (property_exists($userConfig, 'theme')) {
            $user->setTheme($userConfig->theme);
        }

        if ('webmaster' === $user->getLogin()) {
            $this->createdBy = $user;
        }

        $this->addReference($userConfig->login, $user);
        $this->addGroup((array) $userConfig, $user);

        $this->manager->persist($user);

        return $user;
    }

    /**
     * Set User Picture.
     */
    private function setPicture(User $user, array $userConfig): void
    {
        $userConfig = (object) $userConfig;
        $picture = new Picture();
        $picture->setFilename($userConfig->picture);
        $picture->setDirname('/uploads/users/'.$userConfig->picture);
        $picture->setUser($user);
        $user->setPicture($picture);
        $this->manager->persist($user);
    }

    /**
     * Add Group.
     */
    private function addGroup(array $userConfig, User $user): void
    {
        $userConfig = (object) $userConfig;
        $group = new Group();
        $group->setAdminName($userConfig->group);
        $group->setSlug($userConfig->code);
        $group->setCreatedBy($this->createdBy);
        $group->setPosition($this->position);
        foreach ($userConfig->roles as $role) {
            /** @var Role $roleReference */
            $roleReference = $this->getReference($role, Role::class);
            $group->addRole($roleReference);
        }
        $user->setGroup($group);
        $this->manager->persist($group);
        ++$this->position;
    }
}
