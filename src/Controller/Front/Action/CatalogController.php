<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\ActionController;
use App\Entity\Core\Domain;
use App\Entity\Layout\Block;
use App\Entity\Module\Catalog;
use App\Entity\Seo\Url;
use App\Form\Type\Module\Catalog\FrontSearchFiltersType;
use App\Form\Type\Module\Catalog\FrontSearchTextType;
use App\Model\Core\WebsiteModel;
use App\Model\EntityModel;
use App\Model\Module\ProductModel;
use App\Repository\Module\Catalog\CategoryRepository;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CatalogController.
 *
 * Catalog render
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CatalogController extends ActionController
{
    private array $arguments = [];
    private array $cache = [];

    /**
     * Index.
     *
     * @throws NonUniqueResultException|Exception|InvalidArgumentException
     */
    #[Route('/module/catalog/index/{url}/{filter}', name: 'front_cataloglisting_index', options: ['isMainRequest' => false], methods: 'GET', schemes: '%protocol%')]
    public function index(
        Request $request,
        PaginatorInterface $paginator,
        Url $url,
        mixed $filter = null): JsonResponse|Response
    {
        if (!$filter) {
            return new Response();
        }

        $website = $this->getWebsite();
        $listing = $this->coreLocator->em()->getRepository(Catalog\Listing::class)->findOneByFilter($website->entity, $request->getLocale(), $filter);
        if ($listing) {
            $this->getIndexArguments($listing, $url);
        }

        if (!$listing) {
            return new Response();
        }

        $this->arguments['websiteTemplate'] = $website->configuration->template;
        $this->arguments['filter'] = $filter;
        $this->arguments['template'] = $listing->isShowMap() ? 'front/'.$this->arguments['websiteTemplate'].'/actions/catalog/map.html.twig' :
            ($this->coreLocator->fileExist('front/'.$this->arguments['websiteTemplate'].'/actions/catalog/index/'.$listing->getSlug().'.html.twig')
                ? 'front/'.$this->arguments['websiteTemplate'].'/actions/catalog/index/'.$listing->getSlug().'.html.twig'
                : 'front/'.$this->arguments['websiteTemplate'].'/actions/catalog/index.html.twig');

        return $this->getResults($request, $paginator, $website, $listing, $this->getData(), $listing->getItemsPerPage());
    }

    /**
     * View.
     *
     * @throws ReflectionException|ContainerExceptionInterface|InvalidArgumentException|NonUniqueResultException|NotFoundExceptionInterface|MappingException|QueryException
     */
    #[Route([
        'fr' => '/fiche-produit/{url}',
        'fr_ch' => '/fiche-produit/{url}',
        'en' => '/product-card/{url}',
    ], name: 'front_catalogproduct_view_only', methods: 'GET', schemes: '%protocol%', priority: 300)]
    #[Cache(expires: 'tomorrow', public: true)]
    public function view(Request $request, string $url, ?string $pageUrl = null, bool $preview = false): Response
    {
        $this->setTemplate('catalog/view.html.twig');
        $this->setClassname(Catalog\Product::class);
        $this->setModel(ProductModel::class);
        $this->setModelOptions([]);
        $this->setListingClassname(Catalog\Listing::class);
        $this->setAssociatedThumbMethod('catalog');
        $this->setAssociatedEntitiesProperties(['catalog']);
        $this->setAssociatedEntitiesLimit(6);

        return $this->getView($request, $url, $pageUrl, $preview);
    }

    /**
     * Teaser.
     *
     * @throws ContainerExceptionInterface|InvalidArgumentException|MappingException|NonUniqueResultException|NotFoundExceptionInterface|ReflectionException|QueryException
     */
    public function teaser(Request $request, Block $block, Url $url, mixed $filter = null): Response
    {
        $this->setTemplate('catalog/teaser.html.twig');
        $this->setTeaserClassname(Catalog\Teaser::class);
        $this->setListingClassname(Catalog\Listing::class);
        $this->setClassname(Catalog\Product::class);
        $this->setModel(ProductModel::class);
        $this->setModelOptions([]);
        $this->setInterfaceName('catalog');

        return $this->getTeaser($request, $block, $url, $filter);
    }

    /**
     * Preview.
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface|MappingException|NonUniqueResultException|InvalidArgumentException
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin-%security_token%/front/product/preview/{url}', name: 'front_catalogproduct_preview', methods: 'GET|POST', schemes: '%protocol%')]
    public function preview(Request $request, Url $url): Response
    {
        $this->setClassname(Catalog\Product::class);
        $this->setModel(ProductModel::class);
        $this->setModelOptions([]);
        $this->setListingClassname(Catalog\Listing::class);
        $this->setCategoryClassname(Catalog\Category::class);
        $this->setController(CatalogController::class);

        return $this->getPreview($request, $url);
    }

    /**
     * Search.
     *
     * @throws Exception|InvalidArgumentException
     */
    #[Route('/module/catalog/search/{listing}/{url}/{filter}', name: 'front_catalog_search', options: ['isMainRequest' => false], methods: 'GET', schemes: '%protocol%')]
    public function search(
        Request $request,
        PaginatorInterface $paginator,
        Catalog\Listing $listing,
        ?Url $url = null,
        mixed $filter = null): JsonResponse|Response
    {
        $website = $this->coreLocator->website();
        $data = $this->getData();
        $websiteTemplate = $website->configuration->template;
        $domain = !$url instanceof Url ? $this->coreLocator->em()->getRepository(Domain::class)->findByName($request->getHost()) : null;
        $locale = $url instanceof Url ? $url->getLocale() : ($domain instanceof Domain && $domain->getLocale() ? $domain->getLocale() : $request->getLocale());
        $searchService = $this->frontLocator->catalogSearch();
        $allProducts = $searchService->execute($listing, $data, $locale);
        $request->setLocale($locale);

        /** Form text */
        $formText = !$listing->isCombineFieldsText() ? $this->createForm(FrontSearchTextType::class, null, [
            'action' => $this->generateUrl('front_catalog_search', ['listing' => $listing->getId(), 'url' => $url->getId()]),
            'text' => $data['text'],
            'method' => 'GET',
        ]) : null;

        /** Form select */
        $formFilters = $this->createForm(FrontSearchFiltersType::class, $listing, [
            'action' => $this->generateUrl('front_catalog_search', ['listing' => $listing->getId(), 'url' => $url->getId()]),
            'filters' => $data['filters'],
            'products' => $allProducts,
            'website' => $website->entity,
            'method' => 'GET',
        ]);

        $this->getIndexArguments($listing, $url, $allProducts['searchResults']);
        $this->arguments['listing'] = $listing;
        $this->arguments['filter'] = $filter;
        $this->arguments['categories'] = $allProducts['categories'];
        $this->arguments['initialProducts'] = $allProducts['initialResults'];
        $this->arguments['formText'] = $formText?->createView();
        $this->arguments['formFilters'] = $formFilters->createView();
        $this->arguments['count'] = count($allProducts['searchResults']);

        if (!empty($request->get('ajax'))) {
            $this->arguments['template'] = 'front/'.$websiteTemplate.'/actions/catalog/index.html.twig';
            $this->getIndexArguments($listing, $url);
            $count = !$listing->isScrollInfinite() && !$listing->isShowMoreBtn() ? $listing->getItemsPerPage() : null;
            return $this->getResults($request, $paginator, $website, $listing, $data, $count);
        }

        return $this->render('front/'.$websiteTemplate.'/actions/catalog/form/search.html.twig', $this->arguments);
    }

    /**
     * Map product.
     *
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException|ReflectionException|QueryException
     */
    #[Route('/front/map/product/{listing}/{product}', name: 'front_map_product', methods: 'GET|POST', schemes: '%protocol%')]
    public function mapProduct(Request $request, Catalog\Listing $listing, Catalog\Product $product): Response
    {
        $website = $this->coreLocator->website();
        $websiteTemplate = $website->configuration->template;
        $listingService = $this->coreLocator->listingService();
        $urlsIndex = $listingService->indexesPages($listing, $request->getLocale(), Catalog\Listing::class, Catalog\Product::class, [$product]);
        return new JsonResponse(['html' => $this->renderView('front/'.$websiteTemplate.'/actions/catalog/map-product.html.twig', [
            'product' => ProductModel::fromEntity($product, $this->coreLocator, ['urlsIndex' => $urlsIndex]),
        ])]);
    }

    /**
     * Get results.
     *
     * @throws InvalidArgumentException|Exception
     */
    private function getResults(
        Request $request,
        PaginatorInterface $paginator,
        WebsiteModel $website,
        Catalog\Listing $listing,
        array $data,
        ?int $limit = -1): JsonResponse|Response
    {
        $this->arguments['locale'] = $request->getLocale();
        $this->arguments['limit'] = (-1 === $limit) || !is_numeric($limit) ? 1000000 : $limit;
        $searchProducts = ($listing->isShowMap() && $this->coreLocator->request()->get('ajax')) || !$listing->isShowMap();
        $searchService = $this->frontLocator->catalogSearch();

        $productIdsDisplay = [];
        $products = [];
        if ($searchProducts && !empty($data['text']) && !$listing->isCombineFieldsText()) {
            $products = $this->coreLocator->em()->getRepository(Catalog\Product::class)->findLikeInTitle($website->entity, $request->getLocale(), $data['text'], $listing);
        } elseif ($searchProducts && !empty($data['filters']) || !empty($data['text'])) {
            $products = $searchService->execute($listing, $data)['searchResults'];
            $listing->setCounter(true);
        } elseif ($searchProducts) {
            $results = $searchService->execute($listing);
            $products = $results['initialResults'];
            $productIdsDisplay = $results['productIdsDisplay'];
        }

        $listingService = $this->coreLocator->listingService();
        $this->arguments['count'] = $count = count($products);
        $this->arguments['microDataActive'] = $website->seoConfiguration->microData;
        $this->arguments['products'] = $this->arguments['limit'] > 0 ? $this->getPagination($paginator, $products, $this->arguments['limit']) : $products;
        $this->arguments['urlsIndex'] = $listingService->indexesPages($listing, $request->getLocale(), Catalog\Listing::class, Catalog\Product::class, $products);
        $this->arguments['maxPage'] = $count > 0 && $this->arguments['limit'] > 0 ? intval(ceil($count / $this->arguments['limit'])) : $count;
        $this->arguments['productsByCategories'] = [];
        $this->arguments['haveFilters'] = !empty($data['filters']) || !empty($data['text']);

        $items = [];
        if ($listing->isGroupByCategories()) {
            foreach ($products as $product) {
                $productModel = $this->arguments['microdataEntities'][] = ProductModel::fromEntity($product, $this->coreLocator, ['urlsIndex' => $this->arguments['urlsIndex'], 'entitiesIds' => $productIdsDisplay]);
                foreach ($product->getCategories() as $category) {
                    $categoryModel = $this->cache['categories'][$category->getId()] = !empty($this->cache['categories'][$category->getId()]) ? $this->cache['categories'][$category->getId()] : EntityModel::fromEntity($category, $this->coreLocator)->response;
                    $entities = !empty($this->arguments['productsByCategories'][$category->getPosition()]['products']) ? $this->arguments['productsByCategories'][$category->getPosition()]['products'] : [];
                    $entities[] = $productModel;
                    $this->arguments['productsByCategories'][$category->getPosition()] = [
                        'entity' => $categoryModel,
                        'products' => $entities,
                    ];
                    ksort($this->arguments['productsByCategories']);
                }
            }
        } else {
            foreach ($this->arguments['products']->getItems() as $item) {
                $items[] = $this->arguments['microdataEntities'][] = ProductModel::fromEntity($item, $this->coreLocator, ['urlsIndex' => $this->arguments['urlsIndex'], 'entitiesIds' => $productIdsDisplay]);
            }
            $this->arguments['products']->setItems($items);
        }

        if ($request->attributes->get('ajax') || $request->attributes->get('scroll-ajax')) {
            return new JsonResponse(['count' => $this->arguments['count'], 'html' => $this->renderView($this->arguments['template'], $this->arguments)]);
        } else {
            return $this->render($this->arguments['template'], $this->arguments);
        }
    }

    /**
     * Get data.
     */
    private function getData(): array
    {
        $getRequest = filter_input_array(INPUT_GET);
        $text = null;
        $filters = [];

        if (isset($getRequest['text']) || isset($getRequest['search_products']['text'])) {
            $text = $getRequest['search_products']['text'] ?? $getRequest['text'];
            $text = $this->coreLocator->XssProtectionData($text);
        }

        if (!isset($getRequest['not_as_search'])) {
            if (isset($getRequest['categories']) || isset($getRequest['products'])) {
                $filters = isset($getRequest['products']['categories']) ? $getRequest['products'] : $getRequest;
            } elseif (!empty($getRequest)) {
                $filters = $getRequest;
            }
            foreach ($filters as $key => $value) {
                $filters[$key] = $this->coreLocator->XssProtectionData($value);
            }
            $unsets = ['scroll-ajax', 'ajax', 'page', 'website'];
            foreach ($unsets as $unset) {
                if (isset($filters[$unset])) {
                    unset($filters[$unset]);
                }
            }
            if (!empty($filters['text'])) {
                unset($filters['text']);
            }
        }

        return ['text' => $text, 'filters' => $filters];
    }

    /**
     * Get arguments.
     */
    private function getIndexArguments(Catalog\Listing $listing, ?Url $url = null, array $products = []): void
    {
        $website = $this->coreLocator->website();
        $thumbConfiguration = $this->thumbConfiguration($website, Catalog\Listing::class, 'index', $listing->getSlug());
        $thumbConfiguration = !$thumbConfiguration ? $this->thumbConfiguration($website, Catalog\Product::class, 'index', $listing->getSlug()) : $thumbConfiguration;
        $this->arguments = array_merge($this->arguments, [
            'websiteTemplate' => $website->configuration->template,
            'website' => $website,
            'listing' => $listing,
            'scrollInfinite' => $listing->isScrollInfinite(),
            'showMoreBtn' => $listing->isShowMoreBtn(),
            'highlight' => true,
            'url' => $url,
            'thumbConfiguration' => $thumbConfiguration,
            'products' => $products,
        ]);
    }

    /**
     * Teaser categories.
     *
     * @throws Exception
     */
    public function teaserCategories(
        CategoryRepository $categoryRepository,
        Block $block,
        Url $url): Response
    {
        $website = $this->getWebsite();
        $categories = $categoryRepository->findBy(['website' => $website->entity]);
        if (!$categories) {
            return new Response();
        }
        $websiteTemplate = $website->configuration->template;

        return $this->render('front/'.$websiteTemplate.'/actions/catalog/teaser-categories.html.twig', [
            'websiteTemplate' => $websiteTemplate,
            'block' => $block,
            'url' => $url,
            'website' => $website,
            'entities' => $categories,
            'thumbConfiguration' => $this->thumbConfiguration($website, Catalog\Product::class, 'teaser'),
        ]);
    }
}
