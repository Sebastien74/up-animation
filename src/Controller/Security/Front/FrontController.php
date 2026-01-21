<?php

declare(strict_types=1);

namespace App\Controller\Security\Front;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * FrontController.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_SECURE_PAGE')]
class FrontController extends \App\Controller\Front\FrontController
{
    /**
     * To display dashboard.
     */
    #[Route([
        'fr' => '/mon-espace-personnel/tableau-de-bord',
        'en' => '/my-personal-space/dashboard',
        'es' => '/mi-espacio-personal/tablero-de-mandos',
        'it' => '/mio-spazio-personale/cruscotto',
    ], name: 'security_front_dashboard', methods: 'GET', schemes: '%protocol%', priority: 1)]
    public function dashboardView(): Response
    {
        $arguments = array_merge([
            'view' => 'dashboard-view',
        ], $this->defaultArgs($this->coreLocator->website())
        );

        return $this->forward('App\Controller\Security\Front\FrontController::dashboard', [
            'arguments' => $arguments,
        ]);
    }

    /**
     * To display dashboard action.
     */
    public function dashboard(?array $arguments = []): Response
    {
        $website = $this->getWebsite();
        $configuration = $website->configuration;
        $websiteTemplate = $configuration->template;
        $view = !empty($arguments['view']) ? $arguments['view'] : 'dashboard';
        $arguments = array_merge($arguments, [
            'user' => $this->coreLocator->user(),
        ]);

        return $this->render('front/'.$websiteTemplate.'/actions/security/back/'.$view.'.html.twig', $arguments);
    }
}
