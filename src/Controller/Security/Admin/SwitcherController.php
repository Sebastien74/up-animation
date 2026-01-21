<?php

declare(strict_types=1);

namespace App\Controller\Security\Admin;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Website;
use App\Entity\Security\User;
use App\Entity\Security\UserFront;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * SwitcherController.
 *
 * Users switcher management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ALLOWED_TO_SWITCH')]
class SwitcherController extends AdminController
{
    /**
     * User switcher view.
     */
    #[Route('/admin-%security_token%/user-switcher/{website}/{type}', name: 'admin_user_switcher', options: ['expose' => true], methods: 'GET', schemes: '%protocol%')]
    public function switcher(Website $website, string $type): JsonResponse
    {
        if (!in_array('ROLE_ALLOWED_TO_SWITCH', $this->getUser()->getRoles())) {
            $this->denyAccessUnlessGranted('ROLE_ALLOWED_TO_SWITCH');
        }
        $userClassname = 'front' === $type ? UserFront::class : User::class;
        $users = $this->coreLocator->em()->getRepository($userClassname)->findForSwitcher();

        return new JsonResponse(['html' => $this->renderView('security/switcher.html.twig', [
            'website' => $website,
            'inAdmin' => 'admin' === $type,
            'users' => $users,
        ])]);
    }
}
