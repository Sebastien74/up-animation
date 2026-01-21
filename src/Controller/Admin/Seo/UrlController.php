<?php

declare(strict_types=1);

namespace App\Controller\Admin\Seo;

use App\Controller\Admin\AdminController;
use App\Entity\Layout\Page;
use App\Entity\Seo\Url;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * UrlController.
 *
 * SEO Url management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/{website}/seo/urls', schemes: '%protocol%')]
class UrlController extends AdminController
{
    /**
     * Url status.
     */
    #[Route('/status/{url}', name: 'admin_url_status', options: ['expose' => true], methods: 'GET')]
    public function status(Request $request, Url $url): JsonResponse
    {
        $newStatus = !$url->isOnline();
        $classname = $request->get('classname') ? urldecode($request->get('classname')) : null;
        $entityId = $request->get('entityId');

        if ($entityId && $classname === Page::class) {
            $currentPage = $this->coreLocator->em()->getRepository(Page::class)->find(intval($entityId));
            if ($currentPage) {
                foreach ($this->coreLocator->website()->configuration->entity->getPages() as $page) {
                    if ($currentPage->getId() === $page->getId()) {
                        $filesystem = new Filesystem();
                        $dirname = $this->coreLocator->cacheDir().'/pages.cache.json';
                        if ($filesystem->exists($dirname)) {
                            $filesystem->remove($dirname);
                        }
                        break;
                    }
                }
            }
        }

        $url->setOnline($newStatus);
        $this->coreLocator->em()->persist($url);
        $this->coreLocator->em()->flush();

        return new JsonResponse(['status' => $newStatus ? 'online' : 'offline']);
    }
}
