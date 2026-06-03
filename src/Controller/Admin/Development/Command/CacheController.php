<?php

declare(strict_types=1);

namespace App\Controller\Admin\Development\Command;

use App\Command\CacheCommand;
use App\Command\LiipCommand;
use App\Service\Core\CachePoolManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CacheController.
 *
 * To execute cache commands
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/development/commands/cache', schemes: '%protocol%')]
class CacheController extends BaseCommand
{
    private const string POOL_CSRF_TOKEN_ID = 'admin_cache_pool_clear';

    /**
     * List cache pools with an aggregate disk usage and a per-pool clear action.
     */
    #[Route('/pools', name: 'cache_pools_index', methods: 'GET')]
    public function pools(Request $request, CachePoolManager $cachePoolManager): Response
    {
        $this->breadcrumb($request, [
            $this->coreLocator->translator()->trans('Pools de cache', [], 'admin_breadcrumb') => 'cache_pools_index',
        ]);

        return $this->adminRender('admin/page/development/cache-pools.html.twig', [
            'breadcrumb' => $this->arguments['breadcrumb'],
            'pools' => $cachePoolManager->listPools(),
            'usage' => $cachePoolManager->getUsage(),
        ]);
    }

    /**
     * Clear a single cache pool, or all of them when pool is the ALL marker.
     */
    #[Route('/pools/clear', name: 'cache_pool_clear', methods: 'POST')]
    public function poolClear(
        Request $request,
        CachePoolManager $cachePoolManager,
        TranslatorInterface $translator,
    ): RedirectResponse {
        $redirect = $this->redirect($request->headers->get('referer') ?: $this->generateUrl('cache_pools_index'));

        if (!$this->isCsrfTokenValid(self::POOL_CSRF_TOKEN_ID, (string) $request->request->get('_token'))) {
            $this->addFlash('error', $translator->trans('Token CSRF invalide.', [], 'admin'));
            return $redirect;
        }

        $pool = (string) $request->request->get('pool');

        if (CachePoolManager::ALL === $pool) {
            $cachePoolManager->clearAll();
            $this->addFlash('success', $translator->trans('Tous les pools de cache ont été vidés.', [], 'admin'));
            return $redirect;
        }

        if (!$cachePoolManager->isKnownPool($pool)) {
            $this->addFlash('error', $translator->trans('Pool de cache inconnu.', [], 'admin'));
            return $redirect;
        }

        $cachePoolManager->clearPool($pool);
        $this->addFlash('success', $translator->trans('Le pool « %pool% » a été vidé.', ['%pool%' => $pool], 'admin'));

        return $redirect;
    }

    /**
     * Clear cache.
     *
     * @throws \Exception
     */
    #[Route('/clear', name: 'cache_clear', options: ['expose' => true], methods: 'GET')]
    public function clear(Request $request, CacheCommand $cmd, string $projectDir): RedirectResponse|JsonResponse
    {
        if ($request->query->get('ajax')) {
            return new JsonResponse(['success' => true]);
        }

        if ($request->query->get('clear')) {
            $filesystem = new Filesystem();
            $finder = Finder::create();
            $finder->directories()->name('__*')->in($projectDir.'/var/cache/')->depth([0]);
            foreach ($finder as $file) {
                $filesystem->remove($file->getRealPath());
            }
            return new JsonResponse(['success' => true]);
        }

        if ($request->query->get('translations')) {
            $filesystem = new Filesystem();
            $finder = Finder::create();
            $finder->files()->in($projectDir.'/var/cache/'.$_ENV['APP_ENV'].'/translations');
            foreach ($finder as $file) {
                $filesystem->remove($file->getRealPath());
            }
            return new JsonResponse(['success' => true]);
        }

        $asRename = (bool) $request->query->get('rename');
        $this->setFlashBag($cmd->clear($asRename, $asRename), 'cache:clear', $projectDir);
        return $this->redirect($request->headers->get('referer').'?cache_clear=true');
    }

    /**
     * Clear cache.
     *
     * @throws \Exception
     */
    #[Route('/clear-html', name: 'cache_clear_html', methods: 'GET')]
    public function clearHtml(Request $request, string $projectDir): RedirectResponse
    {
        $website = $this->getWebsite();
        $filesystem = new Filesystem();
        $cacheDirname = $projectDir.'/var/cache/';
        $cacheDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cacheDirname);
        $websiteUploadDirname = $website->uploadDirname;
        $environments = ['prod', 'dev'];
        foreach ($environments as $environment) {
            $dirname = $cacheDirname.$environment.'/'.$websiteUploadDirname;
            if ($filesystem->exists($dirname)) {
                $filesystem->remove($dirname);
            }
        }
        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Clear Liip cache.
     *
     * @throws \Exception
     */
    #[Route('/liip/clear', name: 'cache_liip_clear', methods: 'GET')]
    public function liipClear(Request $request, LiipCommand $cmd, string $projectDir): RedirectResponse
    {
        $filesytem = new Filesystem();
        $cacheDirname = $projectDir.'/public/medias/webp/';
        $cacheDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cacheDirname);
        if ($filesytem->exists($cacheDirname)) {
            $filesytem->remove($cacheDirname);
        }
        $this->setFlashBag($cmd->remove(), 'liip:imagine:cache:remove', $projectDir);
        return $this->redirect($request->headers->get('referer'));
    }
}
