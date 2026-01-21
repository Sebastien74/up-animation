<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\FrontController;
use App\Entity\Layout\Block;
use App\Entity\Layout\Page;
use App\Entity\Module\Portfolio\Card;
use App\Entity\Module\Portfolio\Category;
use App\Entity\Module\Portfolio\Listing;
use App\Entity\Seo\Url;
use App\Repository\Layout\PageRepository;
use App\Repository\Module\Portfolio\CardRepository;
use App\Repository\Module\Portfolio\CategoryRepository;
use App\Repository\Module\Portfolio\ListingRepository;
use App\Repository\Module\Portfolio\TeaserRepository;
use App\Service\Content\ListingService;
use App\Service\Content\SeoService;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;

/**
 * PortfolioController.
 *
 * Front Portfolio renders
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PortfolioController extends FrontController
{
    /**
     * Index.
     *
     * @throws \Exception
     */
    public function index(
        Request $request,
        CardRepository $cardRepository,
        ListingRepository $listingRepository,
        Url $url,
        ?Block $block = null,
        mixed $filter = null,
    ) {
        if (!$filter) {
            return new Response();
        }

        $cache = $this->coreLocator->cacheService()->cachePool($block, 'portfolio_index', 'GET');
        if ($cache) {
            return $cache;
        }

        /** @var Listing $listing */
        $listing = $listingRepository->find($filter);

        if (!$listing) {
            return new Response();
        }

        $website = $this->getWebsite();
        $entities = $cardRepository->findByListing($request->getLocale(), $website->entity, $listing);
        $configuration = $website->configuration;
        $template = $configuration->template;

        $categories = [];
        foreach ($entities as $entity) {
            foreach ($entity->getCategories() as $category) {
                if (!isset($categories[$category->getPosition()])) {
                    $categories[$category->getPosition()] = $category;
                }
            }
        }

        $entity = $block instanceof Block ? $block : $listing;
        $entity->setUpdatedAt($listing->getUpdatedAt());

        $response = $this->render('front/'.$template.'/actions/portfolio/index.html.twig', [
            'website' => $website,
            'url' => $url,
            'filter' => $filter,
            'listing' => $listing,
            'categories' => $categories,
            'entities' => $entities,
            'websiteTemplate' => $template,
            'thumbConfiguration' => $this->thumbConfiguration($website, Card::class, 'index'),
        ]);

        return $this->coreLocator->cacheService()->cachePool($block, 'portfolio_index', 'GENERATE', $response);
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
        Url $url,
        ?Block $block = null,
        mixed $filter = null,
    ) {
        if (!$filter) {
            return new Response();
        }

        $cache = $this->coreLocator->cacheService()->cachePool($block, 'portfolio_teaser', 'GET');
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
        $entities = $listingService->findTeaserEntities($teaser, $locale, Card::class, $website);

        $entity = $block instanceof Block ? $block : $teaser;
        $entity->setUpdatedAt($teaser->getUpdatedAt());

        $response = $this->render('front/'.$template.'/actions/portfolio/teaser.html.twig', [
            'websiteTemplate' => $template,
            'block' => $block,
            'url' => $url,
            'website' => $website,
            'urlsIndex' => $listingService->indexesPages($teaser, $locale, Listing::class, Card::class, $entities, []),
            'teaser' => $teaser,
            'entities' => $entities,
            'thumbConfiguration' => $this->thumbConfiguration($website, Card::class, 'teaser'),
        ]);

        return $this->coreLocator->cacheService()->cachePool($block, 'portfolio_teaser', 'GENERATE', $response);
    }

    /**
     * Category View.
     *
     * @throws NonUniqueResultException
     * @throws \Exception|InvalidArgumentException
     */
    #[Route([
        'fr' => '/{pageUrl}/portfolio-categorie/{url}',
        'en' => '/{pageUrl}/portfolio-category/{url}',
    ], name: 'front_portfoliocategory_view', methods: 'GET', schemes: '%protocol%')]
    #[Route([
        'fr' => '/portfolio-categorie/{url}',
        'en' => '/portfolio-category/{url}',
    ], name: 'front_portfoliocategory_view_only', methods: 'GET', schemes: '%protocol%')]
    #[Cache(expires: 'tomorrow', public: true)]
    public function category(
        Request $request,
        CategoryRepository $categoryRepository,
        PageRepository $pageRepository,
        SeoService $seoService,
        string $url,
        ?string $pageUrl = null,
        bool $preview = false,
    ): Response {
        $website = $this->getWebsite();

        /** @var Page $page */
        $page = $pageUrl ? $pageRepository->findByUrlCodeAndLocale($website, $pageUrl, $request->getLocale(), $preview) : null;
        $category = $categoryRepository->findByUrlAndLocale($url, $website->entity, $request->getLocale(), $preview);

        if (!$category) {
            throw $this->createNotFoundException();
        }

        $url = $category->getUrls()[0];
        $request->setLocale($url->getLocale());

        $websiteTemplate = $website->configuration->template;

        return $this->render('front/'.$websiteTemplate.'/actions/portfolio/category.html.twig', [
            'templateName' => 'new-view',
            'interface' => $this->getInterface(Category::class),
            'websiteTemplate' => $websiteTemplate,
            'seo' => $seoService->execute($url, $category),
            'thumbConfiguration' => $this->thumbConfiguration($website, Category::class, 'category'),
            'page' => $page,
            'url' => $url,
            'category' => $category,
        ]);
    }

    /**
     * Card View.
     */
    #[Route([
        'fr' => '/{pageUrl}/fiche-portfolio/{url}',
        'en' => '/{pageUrl}/portfolio-card/{url}',
    ], name: 'front_portfoliocard_view', methods: 'GET', schemes: '%protocol%', priority: 300)]
    #[Route([
        'fr' => '/fiche-portfolio/{url}',
        'en' => '/portfolio-card/{url}',
    ], name: 'front_portfoliocard_view_only', methods: 'GET', schemes: '%protocol%', priority: 300)]
    #[Cache(expires: 'tomorrow', public: true)]
    public function view()
    {
    }
}
