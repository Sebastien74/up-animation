<?php

declare(strict_types=1);

namespace App\Controller\Security\Front;

use App\Controller\Front\FrontController;
use App\Entity\Security\UserFront;
use App\Repository\Security\UserFrontRepository;
use App\Security\AdminToFrontSwitcher;
use App\Security\BackUserSessionDetector;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * SwitcherController.
 *
 * Users switcher management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SwitcherController extends FrontController
{
    private const string ADMIN_SWITCH_CSRF_ID = 'admin_switch_front';

    /**
     * Users switcher.
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/secure/user/switcher', name: 'security_switcher', methods: 'GET', schemes: '%protocol%')]
    public function switcher(): Response
    {
        $website = $this->getWebsite();
        $websiteTemplate = $website->configuration->template;

        return $this->render('front/'.$websiteTemplate.'/actions/security/front/user-switcher.html.twig', array_merge([
            'templateName' => 'security-front',
            'security' => $website->security,
            'users' => $this->coreLocator->em()->getRepository(UserFront::class)->findAll(),
        ], $this->defaultArgs($website)));
    }

    /**
     * Admin to UserFront switch from front identification page.
     */
    #[Route(
        [
            'fr' => '/espace-personnel/admin-switch',
            'en' => '/personal-space/admin-switch',
            'es' => '/espacio-personal/admin-switch',
            'it' => '/spazio-personale/admin-switch',
        ],
        name: 'security_front_admin_switch',
        methods: 'POST',
        schemes: '%protocol%'
    )]
    public function switchToFront(
        Request $request,
        BackUserSessionDetector $backDetector,
        AdminToFrontSwitcher $switcher,
        UserFrontRepository $userFrontRepository,
    ): RedirectResponse {
        if (null === $backDetector->getEligibleBackUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid(self::ADMIN_SWITCH_CSRF_ID, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $userFrontId = (int) $request->request->get('userFront');
        $userFront = $userFrontId > 0 ? $userFrontRepository->find($userFrontId) : null;

        $website = $this->getWebsite();
        if (!$userFront instanceof UserFront || !$userFront->isActive() || $userFront->getWebsite() !== $website->entity) {
            throw $this->createAccessDeniedException();
        }

        $switcher->switchTo($userFront);

        return $this->redirect($website->securityDashboardUrl);
    }
}
