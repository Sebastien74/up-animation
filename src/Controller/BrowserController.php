<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Front\FrontController;
use App\Service\Content\RobotsService;
use App\Service\Content\SitemapService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;

/**
 * BrowserController.
 *
 * To add action by browser
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BrowserController extends FrontController
{
    /**
     * Favicon.
     */
    #[Route('/favicon.{_format}', name: 'browser_favicon', requirements: ['_format' => 'ico'], options: ['isMainRequest' => false], methods: 'GET', schemes: '%protocol%', priority: 600)]
    #[Cache(expires: 'tomorrow', public: true)]
    public function favicon(string $projectDir): BinaryFileResponse|Response
    {
        $website = $this->getWebsite();
        $logos = $website->configuration->logos;
        $filesystem = new Filesystem();

        // Site-specific favicon path or default
        $fileDirname = !empty($logos['favicon']) ? $projectDir.'/public'.$logos['favicon'] : $projectDir.'/public/medias/favicon.ico';
        $fileDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileDirname);

        // Check for the existence of the favicon file
        if ($filesystem->exists($fileDirname)) {
            $response = new BinaryFileResponse($fileDirname);
            $response->setPublic();
            $response->setMaxAge(31536000); // One-year cache
            $response->setSharedMaxAge(31536000); // One-year shared cache
            $response->headers->addCacheControlDirective('immutable'); // Indicates that the file does not change

            return $response;
        }

        return new Response('', Response::HTTP_NOT_FOUND); // 404 response if the file does not exist
    }

    /**
     * Robots.
     *
     * @throws \Exception|InvalidArgumentException
     */
    #[Route('/robots.{_format}', name: 'browser_robots', requirements: ['_format' => 'txt'], options: ['isMainRequest' => false], methods: 'GET', schemes: '%protocol%', priority: 600)]
    #[Cache(expires: 'tomorrow', public: true)]
    public function robots(Request $request, RobotsService $robotsService): Response
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/plain');

        return $this->cache($request, 'core/analytics/robots.txt.twig', 'robots.txt', [
            'robots' => $robotsService->execute($this->getWebsite()),
            'sitemap' => $this->generateUrl('browser_sitemap', ['_format' => 'xml'], 0),
        ]);
    }

    /**
     * Xml.
     *
     * @throws \Exception|InvalidArgumentException
     */
    #[Route('/sitemap.{_format}', name: 'browser_sitemap', requirements: ['_format' => 'xml'], options: ['isMainRequest' => false], methods: 'GET', schemes: '%protocol%', priority: 600)]
    #[Cache(expires: 'tomorrow', public: true)]
    public function sitemap(Request $request, SitemapService $sitemapService): Response
    {
        $website = $this->getWebsite();
        $items = $sitemapService->execute($website->entity);
        foreach ($items as $locale => $localeItems) {
            foreach ($localeItems as $groupLocale => $localesItems) {
                foreach ($localesItems as $key => $item) {
                    if (!$item['url']) {
                        unset($items[$locale][$groupLocale][$key]);
                        if (empty($items[$locale][$groupLocale])) {
                            unset($items[$locale][$groupLocale]);
                        }
                    }
                }
            }
        }

        return $this->cache($request, 'core/analytics/sitemap.xml.twig', null, [
            'defaultLocal' => $website->configuration->locale,
            'xml' => $items,
        ]);
    }

    /**
     * Browser config.
     *
     * @throws \Exception
     */
    #[Route('/browserconfig.{_format}', name: 'browser_config', requirements: ['_format' => 'xml'], options: ['isMainRequest' => false], methods: 'GET', schemes: '%protocol%', priority: 600)]
    #[Cache(expires: 'tomorrow', public: true)]
    public function browserConfig(Request $request): Response
    {
        return $this->cache($request, 'core/analytics/browser-config.xml.twig', null, [
            'website' => $this->getWebsite(),
        ]);
    }

    /**
     * View.
     */
    #[Route('/core/browser/ie/alert', name: 'browser_ie_alert', options: ['isMainRequest' => false], methods: 'GET', schemes: '%protocol%', priority: 600)]
    #[Cache(expires: 'tomorrow', public: true)]
    public function ieAlert(): JsonResponse
    {
        ob_start('ob_gzhandler');

        return new JsonResponse(['html' => $this->renderView('core/ie-alert.html.twig')]);
    }
}
