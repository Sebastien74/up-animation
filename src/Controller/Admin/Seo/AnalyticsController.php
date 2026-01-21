<?php

declare(strict_types=1);

namespace App\Controller\Admin\Seo;

use App\Controller\Admin\AdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * AnalyticsController.
 *
 * Google Analytics charts views
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Route('/admin-%security_token%/{website}/seo/analytics', schemes: '%protocol%')]
class AnalyticsController extends AdminController
{
    /**
     * Google Analytics render.
     */
    #[IsGranted('ROLE_GOOGLE_ANALYTICS')]
    #[Route('/google/{page}/{locale}', name: 'admin_google_analytics', methods: 'GET')]
    public function googleAnalytics(Request $request, string $page, string $locale): Response
    {
        $website = $this->getWebsite();

        return $this->render('admin/page/seo/google-analytics/'.$page.'.html.twig', [
            'page' => $page,
            'locale' => $locale,
            'website' => $website,
            'api' => $website->api,
        ]);
    }
}
