<?php

declare(strict_types=1);

namespace App\Controller\Admin\Seo;

use App\Controller\Admin\AdminController;
use App\Service\Content\SitemapService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * SiteMapController.
 *
 * SEO sitemap management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/seo/sitemap', schemes: '%protocol%')]
class SitemapController extends AdminController
{
    /**
     * Index archive.
     *
     * @throws InvalidArgumentException
     */
    #[Route('/render', name: 'admin_seo_sitemap', methods: 'GET')]
    public function sitemap(Request $request, SitemapService $sitemapService): Response
    {
        $website = $this->getWebsite();

        parent::breadcrumb($request, []);

        return $this->adminRender('admin/page/seo/sitemap.html.twig', array_merge($this->arguments, [
            'defaultLocal' => $website->configuration->locale,
            'xml' => $sitemapService->execute($website->entity),
        ]));
    }
}
