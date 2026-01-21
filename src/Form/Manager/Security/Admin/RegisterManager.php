<?php

declare(strict_types=1);

namespace App\Form\Manager\Security\Admin;

use App\Entity\Core\Security;
use App\Entity\Core\Website;
use App\Entity\Security\Group;
use App\Entity\Security\User;
use App\Form\Model\Security\Admin\RegistrationFormModel;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * RegisterManager.
 *
 * Manage User security registration
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => RegisterManager::class, 'key' => 'security_admin_register_form_manager'],
])]
class RegisterManager
{
    private ?Request $request;

    /**
     * RegisterManager constructor.
     */
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordEncoder,
        private readonly UserAuthenticatorInterface $authenticator,
        private readonly LoginFormAuthenticator $formAuthenticator,
    ) {
        $this->request = $this->requestStack->getMainRequest();
    }

    /**
     * Registration.
     */
    public function register(RegistrationFormModel $userModel, Security $security, Website $website): ?string
    {
        $user = $this->createUser($userModel, $website);
        $session = new Session();

        if ($security->isAdminRegistrationValidation()) {
            $session->getFlashBag()->add('success', $this->translator->trans("Merci pour inscription. Votre compte dois être validé par l'administrateur", [], 'security_cms'));
            $user->setActive(false);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        if ($user->isActive()) {
            $response = $this->authenticator->authenticateUser($user, $this->formAuthenticator, $this->request);

            return $response instanceof RedirectResponse ? $response->getTargetUrl() : $this->request->getSchemeAndHttpHost();
        }

        return null;
    }

    /**
     * Generate User.
     */
    private function createUser(RegistrationFormModel $userModel, Website $website): User
    {
        $user = new User();
        $user->setLogin($userModel->getLogin());
        $user->setEmail($userModel->getEmail());
        $user->setPassword(
            $this->passwordEncoder->hashPassword($user, $userModel->getPlainPassword())
        );

        if (true === $userModel->getAgreeTerms()) {
            $user->agreeTerms();
        }

        $user->addWebsite($website);
        $this->setGroup($user);

        return $user;
    }

    /**
     * Set User group.
     */
    private function setGroup(User $user): void
    {
        $groupRepository = $this->entityManager->getRepository(Group::class);
        $group = $groupRepository->findOneBy(['slug' => 'administrator']);

        if (!$group) {
            $position = count($groupRepository->findAll()) + 1;

            $group = new Group();
            $group->setPosition($position);
            $group->setAdminName('Utilisateurs Front');
            $group->setSlug('administrator');

            $this->entityManager->persist($group);
            $this->entityManager->flush();
        }

        $user->setGroup($group);
    }
}
