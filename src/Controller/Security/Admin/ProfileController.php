<?php

declare(strict_types=1);

namespace App\Controller\Security\Admin;

use App\Controller\Admin\AdminController;
use App\Entity\Security\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ProfileController.
 *
 * Security User Profile management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/{website}/security/users', schemes: '%protocol%')]
class ProfileController extends AdminController
{
    /**
     * Profile.
     *
     * @throws \Exception
     */
    #[Route('/profile/{user}', name: 'admin_user_profile', defaults: ['user' => null], methods: 'GET', schemes: '%protocol%')]
    public function profile(Request $request, AuthorizationCheckerInterface $authChecker, ?User $user = null)
    {
        $user = empty($user) ? $this->getUser() : $user;

        if ($user->getId() !== $this->getUser()->getId() && !$authChecker->isGranted('ROLE_INTERNAL')) {
            throw $this->createAccessDeniedException($this->coreLocator->translator()->trans('Accès refusé.', [], 'security_cms'));
        }

        return $this->adminRender('admin/page/security/profile.html.twig', [
            'user' => $user,
        ]);
    }
}
