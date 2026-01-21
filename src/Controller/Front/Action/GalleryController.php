<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\ActionController;
use App\Entity\Layout\Block;
use App\Entity\Module\Gallery\Gallery;
use App\Entity\Seo\Url;
use App\Model\ViewModel;
use App\Repository\Module\Gallery\GalleryRepository;
use App\Repository\Module\Gallery\TeaserRepository;
use App\Service\Content\ListingService;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * GalleryController.
 *
 * Front Gallery renders
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GalleryController extends ActionController
{
    /**
     * Index.
     *
     * @throws ContainerExceptionInterface|NonUniqueResultException|NotFoundExceptionInterface|MappingException|InvalidArgumentException
     */
    #[Route('/action/gallery/index/{url}/{filter}', name: 'front_gallery_index', options: ['isMainRequest' => false], methods: 'GET', schemes: '%protocol%')]
    public function index(
        Request $request,
        PaginatorInterface $paginator,
        Url $url,
        ?Block $block = null,
        mixed $filter = null,
    ): JsonResponse|Response|null {
        $this->setTemplate('gallery/index.html.twig');
        $this->setClassname(Gallery::class);

        return $this->getIndex($request, $paginator, $url, $block, $filter);
    }

    /**
     * View.
     *
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    public function view(Request $request, GalleryRepository $galleryRepository, ?Block $block = null, mixed $filter = null): Response
    {
        if (!$filter) {
            return new Response();
        }

        $cache = $this->coreLocator->cacheService()->cachePool($block, 'gallery_view', 'GET');
        if ($cache) {
            return $cache;
        }

        $website = $this->getWebsite();
        $gallery = $galleryRepository->findOneByFilter($website->entity, $request->getLocale(), $filter);

        if (!$gallery) {
            return new Response();
        }

        $configuration = $website->configuration;
        $template = $configuration->template;
        $entity = $block instanceof Block ? $block : $gallery;
        $gallery = ViewModel::fromEntity($gallery, $this->coreLocator);
        $entity->setUpdatedAt($gallery->entity->getUpdatedAt());

        $response = $this->render('front/'.$template.'/actions/gallery/view.html.twig', [
            'websiteTemplate' => $template,
            'website' => $website,
            'gallery' => $gallery,
            'thumbConfiguration' => $this->thumbConfiguration($website, Gallery::class, 'view', $gallery->entity),
        ]);

        return $this->coreLocator->cacheService()->cachePool($block, 'gallery_view', 'GENERATE', $response);
    }

    /**
     * Teaser.
     *
     * @throws NonUniqueResultException|\Exception
     */
    public function teaser(
        Request $request,
        TeaserRepository $teaserRepository,
        ListingService $listingService,
        Block $block,
        Url $url,
        mixed $filter = null): Response
    {
        if (!$filter) {
            return new Response();
        }

        $cache = $this->coreLocator->cacheService()->cachePool($block, 'gallery_teaser', 'GET');
        if ($cache) {
            return $cache;
        }

        $website = $this->getWebsite();
        $teaser = $teaserRepository->findOneByFilter($website->entity, $request->getLocale(), $filter);

        if (!$teaser) {
            return new Response();
        }

        $configuration = $website->configuration;
        $template = $configuration->template;
        $locale = $request->getLocale();
        $entities = $listingService->findTeaserEntities($teaser, $locale, Gallery::class, $website->entity);

        $block->setUpdatedAt($teaser->getUpdatedAt());

        $response = $this->render('front/'.$template.'/actions/gallery/teaser.html.twig', [
            'websiteTemplate' => $template,
            'block' => $block,
            'url' => $url,
            'website' => $website,
            'teaser' => $teaser,
            'entities' => $entities,
            'thumbConfiguration' => $this->thumbConfiguration($website, Gallery::class, 'teaser', $teaser->getSlug()),
        ]);

        return $this->coreLocator->cacheService()->cachePool($block, 'gallery_teaser', 'GENERATE', $response);
    }
}
