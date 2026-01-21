<?php

declare(strict_types=1);

namespace App\Controller\Admin\Media;

use App\Controller\Admin\AdminController;
use App\Entity\Media\MediaRelation;
use App\Entity\Media\Thumb;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ThumbController.
 *
 * Media Thumb management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_MEDIA')]
#[Route('/admin-%security_token%/{website}/medias/thumbs', schemes: '%protocol%')]
class ThumbController extends AdminController
{
    protected ?string $class = Thumb::class;

    /**
     * Delete Thumb.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{thumb}', name: 'admin_thumb_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * Remove cache files list.
     */
    #[Route('/generator/remove-cache-list', name: 'admin_thumbs_remove_cache_list', methods: 'DELETE')]
    public function removeCacheList(Request $request, string $projectDir): JsonResponse
    {
        $arguments = [];
        $website = $arguments['website'] = $this->getWebsite();
        $filesystem = new Filesystem();
        $dirname = $projectDir.'/public/thumbnails';
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
        if ($filesystem->exists($dirname)) {
            $finder = Finder::create();
            $finder->in($dirname);
            foreach ($finder as $file) {
                if (!is_dir($file->getRealPath())) {
                    $mediaRelationsIds = [];
                    $mediaRelations = $this->coreLocator->em()->getRepository(MediaRelation::class)->findOneByWebsiteAndName($website->entity, $file->getFilename(), $file->getExtension());
                    foreach ($mediaRelations as $mediaRelation) {
                        $mediaRelationsIds[] = $mediaRelation->getId();
                    }
                    $arguments['files'][] = [
                        'filename' => $file->getFilename(),
                        'path' => urlencode($file->getRealPath()),
                        'mediaRelations' => json_encode($mediaRelationsIds),
                    ];
                }
            }
        }

        return new JsonResponse(['success' => true, 'html' => $this->renderView('admin/page/media/thumbs-list.html.twig', $arguments)]);
    }

    /**
     * Remove cache file.
     */
    #[Route('/generator/remove-cache', name: 'admin_thumbs_remove_cache_file', methods: 'DELETE')]
    public function removeCache(Request $request): JsonResponse
    {
        $relationsIds = json_decode($request->get('relations'));
        $em = $this->coreLocator->em();
        foreach ($relationsIds as $id) {
            $mediaRelation = $em->getRepository(MediaRelation::class)->find($id);
            $mediaRelation->setCacheDate(null);
            $em->persist($mediaRelation);
            $em->flush();
        }

        $filesystem = new Filesystem();
        $path = urldecode($request->get('path'));
        if ($path && str_contains($path, '\public\thumbnails') && !is_dir($path) && $filesystem->exists($path)) {
            $filesystem->remove($path);
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * Remove cache files end process.
     */
    #[Route('/generator/remove-cache/end-process', name: 'admin_thumbs_remove_cache_end_process', methods: 'DELETE')]
    public function removeCacheEndProcess(Request $request, string $projectDir): JsonResponse
    {
        $website = $this->getWebsite();
        $mediaRelations = $this->coreLocator->em()->getRepository(MediaRelation::class)->findByWebsiteAndAsCache($website->entity);
        $em = $this->coreLocator->em();
        foreach ($mediaRelations as $mediaRelation) {
            $mediaRelation->setCacheDate(null);
            $em->persist($mediaRelation);
            $em->flush();
        }

        $filesystem = new Filesystem();
        $dirname = $projectDir.'/public/thumbnails';
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
        if ($filesystem->exists($dirname)) {
            $finder = Finder::create();
            $finder->in($dirname)->depth(0);
            foreach ($finder as $dir) {
                if ($dir !== $dirname) {
                    $filesystem->remove($dir);
                }
            }
        }

        $this->coreLocator->cacheService()->clearCaches();

        return new JsonResponse(['success' => true]);
    }

    /**
     * Remove cache files.
     */
    #[Route('/generator/remove-front-cache', name: 'admin_thumbs_generator_remove_front_cache', methods: 'GET')]
    public function removeFrontCache(string $cacheDir): JsonResponse
    {
        $filesystem = new Filesystem();
        $filesystem->remove([$cacheDir.'/website']);

        return new JsonResponse(['success' => true]);
    }
}
