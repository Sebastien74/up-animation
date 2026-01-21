<?php

declare(strict_types=1);

namespace App\Controller\Admin\Development\Command;

use App\Command\CacheCommand;
use App\Command\LiipCommand;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
    /**
     * Clear cache.
     *
     * @throws \Exception
     */
    #[Route('/clear', name: 'cache_clear', options: ['expose' => true], methods: 'GET')]
    public function clear(Request $request, CacheCommand $cmd, string $projectDir): RedirectResponse|JsonResponse
    {
        if ($request->get('ajax')) {
            return new JsonResponse(['success' => true]);
        }

        if ($request->get('clear')) {
            $filesystem = new Filesystem();
            $finder = Finder::create();
            $finder->directories()->name('__*')->in($projectDir.'/var/cache/')->depth([0]);
            foreach ($finder as $file) {
                $filesystem->remove($file->getRealPath());
            }
            return new JsonResponse(['success' => true]);
        }

        if ($request->get('translations')) {
            $filesystem = new Filesystem();
            $finder = Finder::create();
            $finder->files()->in($projectDir.'/var/cache/'.$_ENV['APP_ENV'].'/translations');
            foreach ($finder as $file) {
                $filesystem->remove($file->getRealPath());
            }
            return new JsonResponse(['success' => true]);
        }

        $asRename = (bool) $request->get('rename');
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
