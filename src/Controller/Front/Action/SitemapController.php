<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\FrontController;
use App\Entity\Layout\Block;
use App\Service\Content\SitemapService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * SitemapController.
 *
 * Front Sitemap renders
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SitemapController extends FrontController
{
    /**
     * View.
     *
     * @throws InvalidArgumentException|\Exception
     */
    #[Route('/module/sitemap/view', name: 'front_sitemap_view', options: ['isMainRequest' => false], methods: 'GET', schemes: '%protocol%')]
    public function view(Request $request, SitemapService $sitemapService, ?Block $block = null): Response
    {
        $website = $this->getWebsite();
        $configuration = $website->configuration;
        $websiteTemplate = $configuration->template;
        return $this->render('front/'.$websiteTemplate.'/actions/sitemap/view.html.twig', [
            'trees' => $sitemapService->execute($website->entity, $request->getLocale(), false, true),
            'websiteTemplate' => $websiteTemplate,
        ]);
    }
}
