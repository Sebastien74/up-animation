<?php

declare(strict_types=1);

namespace App\Controller\Security\Front;

use App\Controller\Front\FrontController;
use App\Entity\Security\UserFront;
use App\Security\TwoFactor\FrontTwoFactorToggleService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * TwoFactorController.
 *
 * UserFront email-based 2FA opt-in toggle.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_USER_FRONT')]
class TwoFactorController extends FrontController
{
    #[Route([
        'fr' => '/mon-espace-personnel/mon-profil/2fa/toggle',
        'en' => '/my-personal-space/my-profile/2fa/toggle',
        'es' => '/mi-espacio-personal/mi-perfil/2fa/toggle',
        'it' => '/mio-spazio-personale/il-mio-profilo/2fa/toggle',
    ], name: 'security_front_2fa_toggle', methods: 'POST', schemes: '%protocol%', priority: 1)]
    public function toggle(Request $request, FrontTwoFactorToggleService $toggleService): RedirectResponse
    {
        $user = $this->getUser();
        if (!$user instanceof UserFront) {
            return $this->redirectToRoute('security_front_forms');
        }

        if (!$this->isCsrfTokenValid('front_2fa_toggle', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $toggleService->toggle($user);

        return $this->redirectToRoute('security_front_profile');
    }
}
