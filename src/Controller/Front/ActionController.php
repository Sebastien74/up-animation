<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Entity\Core\Website;
use App\Entity\Layout\Block;
use App\Entity\Layout\Page;
use App\Entity\Seo\Url;
use App\Model\Core\ConfigurationModel;
use App\Model\Core\WebsiteModel;
use App\Model\EntityModel;
use App\Model\ViewModel;
use App\Service\Content\ActionService;
use App\Service\Interface\CoreLocatorInterface;
use App\Service\Interface\FrontLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ActionController.
 *
 * Manager main Action methods
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ActionController extends FrontController
{
    private ActionService $service;
    private WebsiteModel $website;
    private ConfigurationModel $configuration;
    private string $websiteTemplate = '';
    private string $controller = '';
    private string $classname = '';
    private string $listingClassname = '';
    private string $teaserClassname;
    private string $categoryClassname = '';
    private string $template = '';
    private ?string $filtersForm = null;
    private ?object $filtersFormManager = null;
    private int $associatedEntitiesLimit = 4;
    private ?\DateTime $associatedEntitiesLastDate = null;
    private ?string $associatedThumbMethod = null;
    private ?array $associatedEntitiesProperties = [];
    private array $arguments = [];
    private array $customArguments = [];
    private ?string $model = ViewModel::class;
    private array $modelOptions = [];
    private ?string $interfaceName = null;

    /**
     * ActionController constructor.
     */
    public function __construct(
        #[AutowireLocator(ActionService::class, indexAttribute: 'key')] protected ServiceLocator $actionLocator,
        protected FrontLocatorInterface $frontLocator,
        protected CoreLocatorInterface $coreLocator,
    ) {
        parent::__construct($frontLocator, $coreLocator);
    }

    /**
     * To get Index render.
     *
     * @throws ContainerExceptionInterface|NonUniqueResultException|NotFoundExceptionInterface|MappingException|InvalidArgumentException|QueryException|Exception
     */
    public function getIndex(
        Request $request,
        PaginatorInterface $paginator,
        Url $url,
        ?Block $block = null,
        $filter = null,
    ): JsonResponse|Response {

        $this->setCore($request, $url, $block, $filter);

        $cache = $this->cachePool($block, $this->arguments['interface']['name'].'_index', 'GET');
        if ($cache) {
            return $cache;
        }

        $listing = !$filter ? $this->findListing('main') : $this->findListing($filter);

        if (!$listing) {
            return new Response();
        }

        $locale = $request->getLocale();
        $lastEntity = method_exists($listing, 'isLargeFirst') && $listing->isLargeFirst() ? $this->service->getLastEntity($listing) : null;
        $entities = $this->service->findByListing($listing, $lastEntity, false);

        /** To order past events in bottom of array */
        $orderBy = explode('-', $listing->getOrderBy());
        if ( method_exists($listing, 'isAsEvents') && !empty($entities[0]) && 'startDate' === $orderBy[0] && method_exists($entities[0], 'getStartDate')) {
            $today = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
            $futureEntities = [];
            $pastEntities = [];
            if ($listing->isAsEvents()) {
                foreach ($entities as $entity) {
                    if ($listing->isPastEvents() && $entity->getStartDate() && $entity->getStartDate() < $today) {
                        $pastEntities[] = $entity;
                    } elseif ($entity->getStartDate() >= $today) {
                        $futureEntities[] = $entity;
                    }
                }
            }
            $pastEntities = array_reverse($pastEntities);
            $entities = array_merge($futureEntities, $pastEntities);
        }

        $page = $this->coreLocator->em()->getRepository(Page::class)->findOneByUrlIdAndLocale($url->getId(), $locale);
        $listingService = $this->coreLocator->listingService();
        $urlsIndex = $listingService->indexesPages($listing, $locale, $this->listingClassname, $this->classname, $entities, [], false, true);

        $count = count($entities);
        $limit = method_exists($listing, 'getItemsPerPage') && $listing->getItemsPerPage() ? $listing->getItemsPerPage() : 12;
        $entity = $block instanceof Block ? $block : $listing;
        $entity->setUpdatedAt($listing->getUpdatedAt());

        $thumbConfiguration = $this->thumbConfiguration($this->website, $this->classname, 'index', $listing->getSlug());
        $thumbConfiguration = $thumbConfiguration ?: $this->thumbConfiguration($this->website, $this->classname, 'index', $listing->getId());
        $thumbConfiguration = $thumbConfiguration ?: $this->thumbConfiguration($this->website, $this->classname, 'index');
        $thumbConfigurationFirst = $this->customArguments['thumbConfigurationFirst'] ?? ($thumbConfiguration ?: $this->thumbConfigurationByFilter($this->website, $this->classname, 'first-'.$this->arguments['interfaceName'].'-index'));

        $arguments = array_merge($this->arguments, [
            'website' => $this->website,
            'mainPages' => $this->website->configuration->pages,
            'logos' => $this->website->configuration->logos,
            'currentPage' => $page,
            'microDataActive' => $this->website->seoConfiguration->microData,
            'listing' => $listing,
            'lastEntity' => $lastEntity ? ($this->model)::fromEntity($lastEntity, $this->coreLocator) : null,
            'allEntities' => $lastEntity ? array_merge([$lastEntity], $entities) : $entities,
            'count' => $count,
            'withoutFiltersEntities' => $this->service->findByListing($listing, $lastEntity),
            'scrollInfinite' => method_exists($listing, 'isScrollInfinite') && $listing->isScrollInfinite(),
            'showMoreBtn' => method_exists($listing, 'isShowMoreBtn') && $listing->isShowMoreBtn(),
            'maxPage' => $count > 0 ? intval(ceil($count / $limit)) : $count,
            'thumbConfigurationHeader' => $this->thumbConfiguration($this->website, Block::class, 'block', null, 'title-header'),
            'thumbConfiguration' => $thumbConfiguration,
            'thumbConfigurationFirst' => $thumbConfigurationFirst ?: $thumbConfiguration,
        ]);

        if (method_exists($listing, 'isDisplayFilters') && $listing->isDisplayFilters()) {
            $arguments = $this->getFiltersForm($request, $paginator, $arguments);
            $entities = isset($arguments['isSubmitted']) && $arguments['isSubmitted'] && isset($arguments['entities']) ? $arguments['entities'] : $entities;
            if (method_exists($listing, 'setCounter')) {
                $listing->setCounter(true);
            }
            if (!empty($arguments['formResponse'])) {
                return $arguments['formResponse'];
            }
        }

        $pagination = $this->getPagination($paginator, $entities, $limit);
        $inAdmin = preg_match('/\/admin-'.$_ENV['SECURITY_TOKEN'].'/', $this->coreLocator->request()->getUri()) && !str_contains($this->coreLocator->request()->getUri(), '/preview');
        if (!$inAdmin) {
            $items = [];
            foreach ($pagination->getItems() as $item) {
                $items[] = ($this->model)::fromEntity($item, $this->coreLocator, ['urlsIndex' => $urlsIndex]);
            }
            $pagination->setItems($items);
        }

        $arguments['entities'] = $pagination;
        $arguments = $this->getArguments($arguments);

        if ($this->coreLocator->request()->get('page') && empty($pagination->getItems())) {
            $pageId = $this->coreLocator->request()->get('page');
            $redirection = str_replace(['&page='.$pageId, '?page='.$pageId], '', $this->coreLocator->request()->getUri());
            header('Location: ' . $redirection);
            exit;
        }

        if (isset($arguments['forceEntities']) && $arguments['forceEntities'] && isset($arguments['allEntitiesForce'])) {
            $arguments['allEntities'] = $arguments['entities'] = $arguments['allEntitiesForce'];
            $arguments['disablePagination'] = true;
        }

        $template = $this->getTemplate($this->websiteTemplate, 'index');
        if (method_exists($listing, 'isAsEvents') && $listing->isAsEvents()) {
            $template = 'front/'.$this->websiteTemplate.'/actions/'.$this->interfaceName.'/index-events.html.twig';
        }

        $response = $request->get('scroll-ajax') || $request->get('ajax') ? new JsonResponse(['html' => $this->renderView($template, $arguments)])
            : $this->render($template, $arguments);

        return $this->cachePool($block, $this->arguments['interface']['name'].'_index', 'GENERATE', $response);
    }

    /**
     * To get View render.
     *
     * @throws \ReflectionException|ContainerExceptionInterface|InvalidArgumentException|NonUniqueResultException|NotFoundExceptionInterface|MappingException|QueryException
     */
    public function getView(
        Request $request,
        string $url,
        ?string $pageUrl = null,
        bool $preview = false,
        bool $onlyArguments = false,
    ): array|Response|RedirectResponse {

        $this->setCore($request);

        $cache = $this->cachePool($url, $this->arguments['interface']['name'].'_view', 'GET');
        if ($cache) {
            return $cache;
        }

        $page = $pageUrl ? $this->service->findPageByUrlCodeAndLocale($pageUrl, $preview) : null;
        $entity = $this->service->findEntityByUrlAndLocale($url, $preview);

        if (!$entity) {
            throw $this->createNotFoundException();
        }

        $url = $entity->getUrls()->first();
        $locale = $url->getLocale();
        $request->setLocale($locale);

        /** Check if the index URL code is correct */
        $listingService = $this->coreLocator->listingService();
        $indexPagesCodes = $listingService->indexesPages($entity, $locale, $this->listingClassname, $this->classname, [$entity]);
        $indexPageCode = !empty($indexPagesCodes) ? $indexPagesCodes[array_key_first($indexPagesCodes)] : $pageUrl;
        $this->modelOptions['urlsIndex'] = $indexPagesCodes;
        if ($indexPageCode && $indexPageCode !== $pageUrl && $this->coreLocator->checkRoute('front_'.$this->arguments['interface']['name'].'_view.'.$locale)) {
            return $this->redirectToRoute('front_'.$this->arguments['interface']['name'].'_view', ['pageUrl' => $indexPageCode, 'url' => $url->getCode()], 301);
        }

        /** To display cache pool */
        if (self::CACHE_POOL) {
            $poolResponse = $this->cachePool($entity, $this->arguments['interface']['name'], 'GET');
            if ($poolResponse) {
                return $poolResponse;
            }
        }

        $thumbConfigurationHeader = $this->thumbConfiguration($this->website, $this->classname, 'view', null, 'title-header');
        $associatedThumbMethod = $this->associatedThumbMethod ? 'get'.ucfirst($this->associatedThumbMethod) : null;
        $associatedEntitiesThumbConfiguration = $associatedThumbMethod && is_object($entity->$associatedThumbMethod()) && method_exists($entity->$associatedThumbMethod(), 'getSlug')
            ? $this->thumbConfiguration($this->website, $this->classname, 'view', 'associated-'.$entity->$associatedThumbMethod()->getSlug()) : null;
        if (!$associatedEntitiesThumbConfiguration) {
            $associatedEntitiesThumbConfiguration = $this->thumbConfiguration($this->website, $this->classname, 'view', 'associated-'.$this->arguments['interface']['name']);
        }

        $entityModel = ($this->model)::fromEntity($entity, $this->coreLocator, $this->modelOptions);
        $mainCategory = !empty($this->customArguments['mainTemplateCategory']) && !empty($this->customArguments['mainLayout']) ? $this->customArguments['mainTemplateCategory'] : null;
        $category = property_exists($entityModel, 'category') ? $entityModel->category : $this->service->getCategory($entity);
        $category = $category && !$category instanceof ViewModel
            ? ViewModel::fromEntity($category, $this->coreLocator, ['disabledIntl' => true, 'disabledMedias' => true, 'disabledUrl' => true, 'disabledCategory' => true, 'disabledCategories' => true])
            : $category;
        $entityLayout = $entityModel->haveLayout ? $entityModel->layout : null;
        $categoryLayout = $category && $category->haveLayout ? $category->layout : ($mainCategory && $mainCategory->haveLayout ? $mainCategory->layout : null);

        $arguments = array_merge([
            'thumbConfigurationHeader' => $thumbConfigurationHeader ?: $this->thumbConfiguration($this->website, Block::class, 'block', null, 'title-header'),
            'thumbConfigurationView' => $this->thumbConfiguration($this->website, $this->classname, 'view'),
            'templateName' => $this->arguments['interface']['name'].'-view',
            'seo' => $this->coreLocator->seoService()->execute($url, $entityModel),
            'layout' => $entityLayout ?: $categoryLayout,
            'entity' => $entityModel,
            'entityLayout' => $entityLayout ? $entityModel : $category,
            'category' => $category,
            'categories' => $this->categoryClassname ? $this->coreLocator->em()->getRepository($this->categoryClassname)->findBy([], ['position' => 'ASC']) : (property_exists($entityModel, 'categories') ? $entityModel->categories : []),
            'associatedEntities' => $this->getAssociatedEntities($request, $entity),
            'associatedEntitiesThumbConfiguration' => $associatedEntitiesThumbConfiguration,
            'page' => $page,
            'pageUrl' => $pageUrl,
        ], $this->defaultArgs($this->website, $url, $entityModel));

        $arguments = $this->getArguments($arguments);

        if ($onlyArguments) {
            return $arguments;
        }

        $response = $this->render($this->getTemplate($this->websiteTemplate, 'view', $entity), $arguments);

        return $this->cachePool($entity, $this->arguments['interface']['name'].'_view', 'GENERATE', $response);
    }

    private function setTeaserArguments(?object $teaser): void
    {
        if (is_object($teaser)) {
            $interface = $this->getInterface(get_class($teaser));
            $this->arguments['interface'] = $interface;
            $this->arguments['interfaceName'] = !empty($interface['name']) ? $interface['name'] : null;
        }
    }

    /**
     * To get Teaser render.
     *
     * @throws ContainerExceptionInterface|InvalidArgumentException|MappingException|NonUniqueResultException|NotFoundExceptionInterface|\ReflectionException|QueryException|Exception
     */
    public function getTeaser(Request $request, ?Block $block = null, ?Url $url = null, mixed $filter = null): Response
    {
        $this->website = $this->getWebsite();
        $teaser = $this->findTeaser($this->website->entity, $filter);

        $this->setTeaserArguments($teaser);

        $cache = $this->cachePool($block, ($this->arguments['interface']['name'] ?? 'vendor').'_teaser', 'GET');
        if ($cache) {
            return $cache;
        }

        if (!$teaser) {
            return new Response();
        }

        $configuration = $this->website->configuration;
        $this->websiteTemplate = $websiteTemplate = $configuration->template;
        $locale = $request->getLocale();
        $listingService = $this->coreLocator->listingService();
        $entities = $listingService->findTeaserEntities($teaser, $locale, $this->classname, $this->website);

        /** To order past events in bottom of array */
        $orderBy = explode('-', $teaser->getOrderBy());
        if (!empty($entities) && property_exists($teaser, 'pastEvents') && $teaser->isPastEvents() && 'startDate' === $orderBy[0]) {
            $today = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
            $futureEntities = [];
            $pastEntities = [];
            foreach ($entities as $entitiesGroup) {
                foreach ($entitiesGroup as $entity) {
                    if ($entity->getStartDate() < $today) {
                        $pastEntities[] = $entity;
                    } else {
                        $futureEntities[] = $entity;
                    }
                }
            }
            $pastEntities = array_reverse($pastEntities);
            $entities = array_merge($futureEntities, $pastEntities);
        }

        if ($block instanceof Block) {
            $block->setUpdatedAt($teaser->getUpdatedAt());
        }

        /* To remove current view entity and set Model */
        $inView = $url instanceof Url && 'front_index' !== $this->coreLocator->requestStack()->getMainRequest()->get('_route');
        if ($inView) {
            $this->setCore($request);
        }
        $urlsIndex = $listingService->indexesPages($teaser, $locale, $this->listingClassname, $this->classname, $entities, []);
        $currentEntity = $inView ? $this->service->findEntityByUrlAndLocale($url->getCode()) : null;
        $classname = $this->classname ?: null;
        $referEntity = $classname ? new $classname() : null;
        $renderEntities = $allEntities = [];
        foreach ($entities as $group => $entitiesGroup) {
            if (is_iterable($entitiesGroup)) {
                foreach ($entitiesGroup as $key => $entity) {
                    $referEntity = $entity;
                    if (is_object($currentEntity) && $entity->getId() === $currentEntity->getId()) {
                        unset($entities[$group][$key]);
                    } else {
                        $renderEntities[] = ($this->model)::fromEntity($entity, $this->coreLocator, [
                            'disabledLayout' => true,
                            'configEntity' => $teaser,
                            'urlsIndex' => $urlsIndex
                        ]);
                    }
                    $allEntities[] = $entity;
                }
            } else {
                if (is_object($currentEntity) && $entitiesGroup->getId() === $currentEntity->getId()) {
                    unset($entities[$group][$group]);
                } else {
                    $renderEntities[] = ($this->model)::fromEntity($entitiesGroup, $this->coreLocator, ['configEntity' => $teaser, 'urlsIndex' => $urlsIndex]);
                }
            }
        }

        /* To get template */
        if ($referEntity) {
            $this->coreLocator->interfaceHelper()->setInterface(get_class($referEntity));
        }
        $interfaceName = $this->interfaceName ?: ($referEntity ? $this->coreLocator->interfaceHelper()->getInterface()['name'] : 'vendor');
        $defaultTemplate = 'front/'.$websiteTemplate.'/actions/'.$interfaceName.'/teaser.html.twig';
        $teaserTemplate = method_exists($teaser, 'getTemplate') ? 'front/'.$websiteTemplate.'/actions/'.$interfaceName.'/teaser/'.$teaser->getTemplate().'.html.twig' : $defaultTemplate;
        $teaserTemplate = $this->coreLocator->fileExist($teaserTemplate) ? $teaserTemplate : ($block && $block->getTemplate() ? 'front/'.$websiteTemplate.'/actions/'.$interfaceName.'/teaser/'.$block->getTemplate().'.html.twig' : null);
        $sliderMultiTemplate = method_exists($teaser, 'getItemsPerSlide') && $teaser->getItemsPerSlide() > 1 && !str_contains($teaserTemplate, 'list')
            ? 'front/'.$websiteTemplate.'/actions/'.$interfaceName.'/teaser/slider-multi.html.twig' : null;
        $sliderTemplate = $sliderMultiTemplate && $this->coreLocator->fileExist($sliderMultiTemplate) ? $sliderMultiTemplate : $teaserTemplate;
        $teaserTemplate = $sliderTemplate && $this->coreLocator->fileExist($sliderTemplate) ? $sliderTemplate : $teaserTemplate;
        $template = $this->coreLocator->fileExist($teaserTemplate) ? $teaserTemplate : $defaultTemplate;
        $intl = ViewModel::fromEntity($teaser, $this->coreLocator)->intl;
        $thumbConfiguration = $this->thumbConfiguration($this->website, $this->classname, 'teaser', $teaser->getSlug());
        $thumbConfiguration = !$thumbConfiguration ? $this->thumbConfiguration($this->website, $this->classname, 'teaser', $teaser->getId()) : $thumbConfiguration;

        $categories = [];
        if (method_exists($teaser, 'getCategories')) {
            foreach ($teaser->getCategories() as $category) {
                if (!empty($renderCategories[$category->getId()])) {
                    $categories[$category->getPosition()] = EntityModel::fromEntity($category, $this->coreLocator, ['disabledMedias' => true, 'disabledLayout', true])->response;
                }
            }
        }
        ksort($categories);

        $arguments = [
            'websiteTemplate' => $websiteTemplate,
            'interfaceName' => $interfaceName,
            'block' => $block,
            'asMulti' => $teaser->getItemsPerSlide() > 1,
            'promoteFirst' => $teaser->isPromoteFirst(),
            'url' => $url,
            'website' => $this->website,
            'mainPages' => $this->website->configuration->pages,
            'logos' => $this->website->configuration->logos,
            'urlsIndex' => $urlsIndex,
            'teaser' => $teaser,
            'teaserIntl' => ViewModel::fromEntity($teaser, $this->coreLocator)->intl,
            'intl' => $intl,
            'entities' => $renderEntities,
            'categories' => $categories,
            'filter' => $filter,
            'thumbConfiguration' => $thumbConfiguration,
        ];

        if (method_exists($teaser, 'isDisplayFilters') && $teaser->isDisplayFilters() && $this->filtersForm) {
            $haveManager = is_object($this->filtersFormManager);
            $arguments['allEntities'] = $allEntities;
            $filters = $arguments['filters'] = $haveManager && method_exists($this->filtersFormManager, 'getFilters') ? $this->filtersFormManager->getFilters() : [];
            $form = $this->createForm($this->filtersForm, $filters, ['method' => 'GET', 'teaser' => $teaser, 'arguments' => $arguments]);
            $form->handleRequest($request);
            $arguments['form'] = $form->createView();
            $arguments['formAction'] = $this->generateUrl('front_'.$interfaceName.'_teaser', ['block' => $block->getId(), 'url' => $url->getId(), 'filter' => $filter]);
            $arguments['isSubmitted'] = $form->isSubmitted();
            if ($form->isSubmitted() || $filters) {
                if ($haveManager && method_exists($this->filtersFormManager, 'getResults')) {
                    $entities = $this->filtersFormManager->getResults($arguments['teaser'], $filters, $arguments['allEntities']);
                    foreach ($entities as $key => $entity) {
                        $entities[$key] = ($this->model)::fromEntity($entity, $this->coreLocator, ['configEntity' => $teaser, 'urlsIndex' => $urlsIndex]);
                    }
                    $arguments['entities'] = $entities;
                }
            }
        }

        $response = $request->get('ajax') ? new JsonResponse([
            'success' => true,
            'html' => $this->renderView($template, $arguments),
        ]) : $this->render($template, $arguments);

        return $this->cachePool($block, $this->arguments['interface']['name'].'_teaser', 'GENERATE', $response);
    }

    /**
     * To get Preview render.
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface|MappingException|NonUniqueResultException|InvalidArgumentException
     */
    public function getPreview(Request $request, Url $url): Response
    {
        if (!$url->getCode()) {
            throw $this->createNotFoundException($this->coreLocator->translator()->trans('Vous devez renseigner un code URL.', [], 'admin'));
        }
        $this->setCore($request, $url);
        $entity = $this->service->findEntityByUrlAndLocale($url->getCode(), true);
        if (!$entity) {
            throw $this->createNotFoundException();
        }
        $listingService = $this->coreLocator->listingService();
        $indexUrls = $listingService->indexesPages($entity, $url->getLocale(), $this->listingClassname, $this->classname, [$entity]);
        $request->setLocale($url->getLocale());

        return $this->forward($this->controller.'::view', [
            'pageUrl' => !empty($indexUrls[$entity->getId()]) ? $indexUrls[$entity->getId()] : null,
            'url' => $url->getCode(),
            'preview' => true,
        ]);
    }

    /**
     * Set controller.
     */
    public function setController(string $controller): void
    {
        $this->controller = $controller;
    }

    /**
     * Set classname.
     */
    public function setClassname(string $classname): void
    {
        $this->classname = $classname;
    }

    /**
     * Set model.
     */
    public function setModel(string $model): void
    {
        $this->model = $model;
    }

    /**
     * Set model options.
     */
    public function setModelOptions(array $options = []): void
    {
        $this->modelOptions = $options;
    }

    /**
     * Set interfaceName.
     */
    public function setInterfaceName(string $interfaceName): void
    {
        $this->interfaceName = $interfaceName;
    }

    /**
     * Set listingClassname.
     */
    public function setListingClassname(string $listingClassname): void
    {
        $this->listingClassname = $listingClassname;
    }

    /**
     * Set teaserClassname.
     */
    public function setTeaserClassname(string $teaserClassname): void
    {
        $this->teaserClassname = $teaserClassname;
    }

    /**
     * Set category classname.
     */
    public function setCategoryClassname(string $classname): void
    {
        $this->categoryClassname = $classname;
    }

    /**
     * Get template.
     */
    public function getTemplate(string $websiteTemplate, string $view, mixed $entity = null): string
    {
        $rootDir = dirname(__DIR__, 3);
        $vendorTemplate = 'front/'.$websiteTemplate.'/actions/vendor/'.$view.'.html.twig';
        $template = $this->template ? 'front/'.$websiteTemplate.'/actions/'.$this->template : $vendorTemplate;
        $dirname = $rootDir.'/templates/'.$template;
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
        $filesystem = new Filesystem();
        if (!$filesystem->exists($dirname)) {
            $template = $vendorTemplate;
        }
        if ($this->interfaceName && is_object($entity) && method_exists($entity, 'getCategory') && is_object($entity->getCategory())) {
            $templateCategory = 'front/'.$websiteTemplate.'/actions/'.$this->interfaceName.'/'.$view.'/'.$entity->getCategory()->getSlug().'.html.twig';
            $dirnameCategory = $rootDir.'/templates/'.$templateCategory;
            $dirnameCategory = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirnameCategory);
            $template = $filesystem->exists($dirnameCategory) ? $templateCategory : $template;
        }

        return $template;
    }

    /**
     * Set template.
     */
    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }

    /**
     * Set Filter Form Type.
     */
    public function setFiltersForm(string $filtersForm): void
    {
        $this->filtersForm = $filtersForm;
    }

    /**
     * Set Filter Form Manager.
     */
    public function setFiltersFormManager(object $filtersFormManager): void
    {
        $this->filtersFormManager = $filtersFormManager;
    }

    /**
     * To set associated thumb method.
     */
    public function setAssociatedThumbMethod(string $method): void
    {
        $this->associatedThumbMethod = $method;
    }

    /**
     * Set Associated entities limit.
     */
    public function setAssociatedEntitiesLimit(int $limit): void
    {
        $this->associatedEntitiesLimit = $limit;
    }

    /**
     * Set Associated entities last date.
     *
     * @throws Exception
     */
    public function setAssociatedEntitiesLastDate(int $limit): void
    {
        $datetime = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $datetime->modify('- '.$limit.' days');
        $this->associatedEntitiesLastDate = $datetime;
    }

    /**
     * Set Associated entities properties.
     */
    public function setAssociatedEntitiesProperties(array $properties): void
    {
        $this->associatedEntitiesProperties = $properties;
    }

    /**
     * Get Filter Form.
     */
    public function getFiltersForm(Request $request, PaginatorInterface $paginator, array $arguments = []): array
    {
        if ($this->filtersForm) {
            $listing = $arguments['listing'];
            $haveManager = is_object($this->filtersFormManager);
            $filters = $arguments['filters'] = $haveManager && method_exists($this->filtersFormManager, 'getFilters') ? $this->filtersFormManager->getFilters() : [];
            $form = $this->createForm($this->filtersForm, $filters, ['method' => 'GET', 'listing' => $listing, 'arguments' => $arguments]);
            $form->handleRequest($request);
            $arguments['form'] = $form->createView();
            $arguments['isSubmitted'] = $form->isSubmitted() || $filters;
            if ($form->isSubmitted() || $filters) {
                if ($haveManager && method_exists($this->filtersFormManager, 'getResults')) {
                    $entities = $arguments['entities'] = $this->filtersFormManager->getResults($arguments['listing'], $filters, $arguments['allEntities']);
                    $arguments['allEntitiesForce'] = $arguments['allEntities'] = $this->getPagination($paginator, $entities, 1500);
                }
            }
        }

        return $arguments;
    }

    /**
     * Get arguments.
     */
    public function getArguments(array $arguments = []): array
    {
        $result = array_merge($this->customArguments, $this->arguments, $arguments);
        ksort($result);
        $this->arguments = $result;

        return $result;
    }

    /**
     * Set custom arguments.
     */
    public function setCustomArguments(array $arguments): void
    {
        $this->customArguments = $arguments;
    }

    /**
     * Set core.
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface|MappingException|NonUniqueResultException|InvalidArgumentException
     */
    public function setCore(Request $request, ?Url $url = null, ?Block $block = null, $filter = null): void
    {
        $interface = $this->getInterface($this->classname);
        $websiteId = $request->get('website') ? intval($request->get('website')) : null;
        $this->website = str_contains($request->getUri(), 'preview') && $websiteId
            ? $this->coreLocator->em()->getRepository(Website::class)->findObject($websiteId)
            : $this->coreLocator->em()->getRepository(Website::class)->findOneByHost($request->getHost());
        $this->service = $this->actionLocator->get('action_service');
        $this->service->setWebsite($this->website);
        $this->service->setClassname($this->classname);
        $this->service->setCategoryClassname($this->categoryClassname);

        $this->arguments['interface'] = $interface;
        $this->arguments['interfaceName'] = !empty($interface['name']) ? $interface['name'] : null;
        $this->arguments['url'] = $url;
        $this->arguments['block'] = $block;
        $this->arguments['filter'] = $filter;
        $this->arguments['website'] = $this->website;
        $this->configuration = $this->arguments['configuration'] = $this->website->configuration;
        $this->websiteTemplate = $this->arguments['websiteTemplate'] = $this->configuration->template;
    }

    /**
     * To get associated entities.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    public function getAssociatedEntities(Request $request, mixed $entity): array
    {
        $classname = get_class($entity);
        $matches = explode('\\', $classname);
        $this->associatedEntitiesProperties[] = end($matches);

        $associatedEntities = [];
        foreach ($this->associatedEntitiesProperties as $key => $associatedEntitiesProperty) {
            $method = 'get'.ucfirst($associatedEntitiesProperty).'s';
            if (method_exists($entity, $method)) {
                foreach ($entity->$method() as $associatedElement) {
                    $associatedEntities[] = $associatedElement;
                }
                unset($this->associatedEntitiesProperties[$key]);
            }
        }

        if (!$associatedEntities) {
            $onlineEntities = $this->findOnlineEntities($request, $classname, $entity);
            $onlineEntities = !$onlineEntities ? $this->findOnlineEntities($request, $classname, $entity, false) : $onlineEntities;
//            shuffle($onlineEntities);
            $associatedEntities = array_merge($associatedEntities, $onlineEntities);
        }

        $result = [];
        $indexPages = [];
        $currentLimit = 1;
        $listingService = $this->coreLocator->listingService();
        $entitiesIds = [];
        foreach ($associatedEntities as $associatedEntity) {
            $entitiesIds[] = $associatedEntity->getId();
        }

        $allAssociatedEntities = [];
        foreach ($associatedEntities as $associatedEntity) {
            $valid = true;
            if ($this->associatedEntitiesLastDate) {
                $valid = method_exists($associatedEntity, 'getPublicationStart') && $associatedEntity->getPublicationStart() >= $this->associatedEntitiesLastDate;
            }
            if ($associatedEntity->getId() !== $entity->getId() && count($allAssociatedEntities) < $this->associatedEntitiesLimit && $valid) {
                $allAssociatedEntities[] = $associatedEntity;
            } elseif ($associatedEntity->getId() !== $entity->getId() && count($allAssociatedEntities) >= $this->associatedEntitiesLimit) {
                break;
            }
        }

        $indexPagesCodes = $listingService->indexesPages($entity, $request->getLocale(), $this->listingClassname, $classname, $allAssociatedEntities);

        foreach ($allAssociatedEntities as $associatedEntity) {
            $result[] = ($this->model)::fromEntity($associatedEntity, $this->coreLocator, ['entitiesIds' => $entitiesIds]);
            $indexPages[$associatedEntity->getId()] = !empty($indexPagesCodes[$associatedEntity->getId()]) ? $indexPagesCodes[$associatedEntity->getId()] : null;
        }

        return [
            'list' => $result,
            'indexPages' => $indexPages,
        ];
    }

    /**
     * Find online.
     */
    public function findOnlineEntities(Request $request, string $classname, mixed $currentEntity, bool $categoryFilter = true): array
    {
        $referClass = new $classname();
        $queryBuilder = $this->coreLocator->em()->getRepository($classname)->createQueryBuilder('e')
            ->leftJoin('e.website', 'w')
            ->leftJoin('e.urls', 'u')
            ->andWhere('e.id != :currentId')
            ->andWhere('e.website = :website')
            ->andWhere('u.locale = :locale')
            ->andWhere('u.online = :online')
            ->setParameter('currentId', $currentEntity->getId())
            ->setParameter('website', $this->website->entity)
            ->setParameter('locale', $request->getLocale())
            ->setParameter('online', true)
            ->addSelect('w')
            ->addSelect('u');

        if (method_exists($referClass, 'getPublicationStart')) {
            $queryBuilder->andWhere('e.publicationStart IS NULL OR e.publicationStart < CURRENT_TIMESTAMP()')
                ->andWhere('e.publicationStart IS NOT NULL');
        }

        if (method_exists($referClass, 'getPublicationStart')) {
            $queryBuilder->andWhere('e.publicationEnd IS NULL OR e.publicationEnd > CURRENT_TIMESTAMP()');
        }

        foreach ($this->associatedEntitiesProperties as $property) {
            $method = 'get'.ucfirst($property);
            $method = method_exists($referClass, $method) ? $method : 'is'.ucfirst($property);
            if (method_exists($referClass, $method)) {
                $queryBuilder->andWhere('e.'.$property.' = :'.$property)
                    ->setParameter($property, $currentEntity->$method());
            }
        }

        if (method_exists($referClass, 'getIntls')) {
            $queryBuilder->leftJoin('e.intls', 'i')
                ->andWhere('i.locale = :locale')
                ->addSelect('i');
        }

        if (method_exists($referClass, 'getMediaRelations')) {
            $queryBuilder->leftJoin('e.mediaRelations', 'mr')
                ->addSelect('mr');
        }

        if ($categoryFilter && method_exists($referClass, 'getCategory')) {
            $queryBuilder->leftJoin('e.category', 'c')
                ->andWhere('e.category = :category')
                ->setParameter('category', $currentEntity->getCategory())
                ->addSelect('c');
        } elseif ($categoryFilter && method_exists($referClass, 'getCategories')) {
            $categoryIds = [];
            foreach ($currentEntity->getCategories() as $category) {
                $categoryIds[] = $category->getId();
            }
            if ($categoryIds) {
                $queryBuilder->leftJoin('e.categories', 'cat')
                    ->addSelect('cat')
                    ->andWhere('cat.id IN (:categoryIds)')
                    ->andWhere('cat.id IS NOT NULL')
                    ->setParameter('categoryIds', $categoryIds)
                    ->addSelect('cat');
            }
        }

        $sort = method_exists($referClass, 'getPublicationStart')
            ? 'publicationStart' : (method_exists($referClass, 'getPosition') ? 'position' : 'id');
        $order = 'publicationStart' === $sort ? 'DESC' : 'ASC';
        $entities = $queryBuilder->orderBy('e.'.$sort, $order)
            ->getQuery()
            ->getResult();

        return $this->cleanResult($request, $entities);
    }

    /**
     * Find Listing.
     *
     * @throws NonUniqueResultException
     */
    private function findListing(mixed $filter): mixed
    {
        $listing = new $this->listingClassname();
        $queryBuilder = $this->coreLocator->em()->getRepository($this->listingClassname)->createQueryBuilder('l');
        if (method_exists($listing, 'getWebsite')) {
            $queryBuilder->leftJoin('l.website', 'w')
                ->andWhere('w.id = :websiteId')
                ->setParameter('websiteId', $this->website->id);
        }
        if (is_numeric($filter)) {
            $queryBuilder->andWhere('l.id = :id')
                ->setParameter('id', $filter);
        } else {
            $queryBuilder->andWhere('l.slug = :slug')
                ->setParameter('slug', $filter);
        }

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * To get Teaser.
     *
     * @throws NonUniqueResultException
     */
    protected function findTeaser(Website $website, mixed $filter = null): mixed
    {
        if (!$filter) {
            return false;
        }

        $referClass = $this->classname ? new $this->classname() : null;
        $teaser = new $this->teaserClassname();
        $queryBuilder = $this->coreLocator->em()->getRepository($this->teaserClassname)->createQueryBuilder('t')
            ->leftJoin('t.website', 'w')
            ->andWhere('t.website = :website')
            ->setParameter('website', $website);

        if ($referClass && method_exists($referClass, 'getCatalogs')) {
            $queryBuilder->leftJoin('t.catalogs', 'c')
                ->addSelect('c');
        }

        if ($teaser && method_exists($teaser, 'getCategories')) {
            $queryBuilder->leftJoin('t.categories', 'cat')
                ->addSelect('cat');
        }

        if (is_numeric($filter)) {
            $queryBuilder->andWhere('t.id = :id')
                ->setParameter('id', $filter);
        } else {
            $queryBuilder->andWhere('t.slug = :slug')
                ->setParameter('slug', $filter);
        }

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * To clean result.
     */
    private function cleanResult(Request $request, array $entities, bool $onlyEntitiesPromote = false): array
    {
        foreach ($entities as $key => $entity) {
            $urlLocaleExiting = false;
            $unset = false;
            foreach ($entity->getUrls() as $url) {
                if ($url->getLocale() === $request->getLocale()) {
                    $urlLocaleExiting = true;
                    $unset = !$url->isOnline();
                    break;
                }
            }
            if (!$urlLocaleExiting || $unset || (method_exists($entity, 'isPromote') && $onlyEntitiesPromote && !$entity->isPromote())) {
                unset($entities[$key]);
            }
        }

        return $entities;
    }
}
