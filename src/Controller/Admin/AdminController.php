<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\Core\Entity;
use App\Entity\Core\Website;
use App\Entity\Layout\BlockMediaRelation;
use App\Entity\Layout\LayoutConfiguration;
use App\Entity\Layout\Page;
use App\Form\Type\Core\FilterType;
use App\Form\Type\Core\PositionType;
use App\Form\Type\Core\TreeType;
use App\Model\Core\WebsiteModel;
use App\Model\MediasModel;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Doctrine\Persistence\Mapping\MappingException;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * AdminController.
 *
 * Admin base controller
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AdminController extends BaseController
{
    protected ?string $class = null;
    protected bool $forceEntities = false;
    protected iterable $entities = [];
    protected mixed $entity = null;
    protected ?string $pageTitle = null;
    protected ?string $formType = null;
    protected mixed $formManager = null;
    protected mixed $deleteService = null;
    protected mixed $exportService = null;
    protected mixed $formDuplicateManager = null;
    protected array $formOptions = [];
    protected ?string $template = null;
    protected ?string $templateConfig = null;
    protected bool $disableFormNew = false;
    protected bool $disableFlash = false;
    protected array $arguments = [];

    /**
     * AdminController constructor.
     */
    public function __construct(
        protected \App\Service\Interface\CoreLocatorInterface $coreLocator,
        protected \App\Service\Interface\AdminLocatorInterface $adminLocator,
    ) {
        parent::__construct($coreLocator);
    }

    /**
     * Index view.
     *
     * @throws NonUniqueResultException|InvalidArgumentException|\Exception
     */
    protected function index(Request $request, PaginatorInterface $paginator)
    {
        $interface = $this->getInterface($this->class, $this->arguments);
        $website = !empty($interface['website']) && $interface['website'] instanceof Website ? $interface['website'] : $this->getWebsite();
        $website = $website instanceof Website ? WebsiteModel::fromEntity($website, $this->coreLocator) : $website;

        $filterForm = is_object($interface['configuration']) && $interface['configuration']->searchFilters
            ? $this->createForm(FilterType::class, null, [
                'method' => 'GET',
                'filterName' => 'searchFilters',
                'website' => $website,
                'interface' => $interface,
            ]) : null;

        $helper = $this->adminLocator->indexHelper();
        $helper->setDisplaySearchForm(true);
        $helper->execute($this->class, $interface, 15, $this->entities, $this->forceEntities);
        $pagination = $helper->getPagination();

        if ($filterForm) {
            $filterForm->handleRequest($request);
            if ($filterForm->isSubmitted() && $filterForm->isValid()) {
                $pagination = $this->getPagination($paginator, $this->adminLocator->searchFilterService()->execute($request, $filterForm, $interface), $interface['configuration']->adminLimit);
            }
        }

        if (empty($this->arguments['breadcrumb'])) {
            $this->breadcrumb($request);
        }

        $template = $this->template ?: 'admin/core/index.html.twig';
        $arguments = array_merge($this->arguments, [
            'disableFormNew' => $this->disableFormNew,
            'pageTitle' => $this->pageTitle,
            'namespace' => $this->getCurrentNamespace($request),
            'searchFiltersForm' => $filterForm?->createView(),
            'searchForm' => $helper->getSearchForm()->createView(),
            'columns' => $interface['configuration']->columns,
            'website' => $website,
            'pagination' => $pagination,
            'interface' => $interface,
            'archivedCount' => $helper->getArchivedCount(),
        ]);

        if (!empty($request->get('ajax'))) {
            return new JsonResponse(['html' => $this->adminRender($template, $arguments, $request)]);
        }

        return $this->adminRender($template, $arguments);
    }

    /**
     * Tree view.
     *
     * @throws \Exception
     */
    protected function tree(Request $request)
    {
        $interface = $this->getInterface($this->class);
        $template = !empty($this->template) ? $this->template : 'admin/core/tree.html.twig';
        $entities = $this->adminLocator->treeHelper()->execute($this->class, $interface, $this->getWebsite());
        $formPositions = $this->getTreeForm($request);
        if ($formPositions instanceof JsonResponse) {
            return $formPositions;
        }

        $helper = $this->adminLocator->indexHelper();
        $helper->execute($this->class, $interface, null, null, false, true);

        if (empty($this->arguments['breadcrumb'])) {
            $this->breadcrumb($request);
        }

        return $this->adminRender($template, array_merge($this->arguments, [
            'pageTitle' => $this->pageTitle,
            'tree' => $this->getTree($entities),
            'namespace' => $this->getCurrentNamespace($request),
            'interface' => $interface,
            'formPositions' => $formPositions->createView(),
            'archivedCount' => $helper->getArchivedCount(),
        ]));
    }

    /**
     * Set Tree Form position.
     */
    protected function getTreeForm(Request $request, ?string $classname = null): JsonResponse|FormInterface
    {
        $formPositions = $this->createForm(TreeType::class);
        $formPositions->handleRequest($request);
        if ($formPositions->isSubmitted() && $formPositions->isValid()) {
            $data = $formPositions->getData();
            $class = $classname ?: $this->class;
            $manager = $this->adminLocator->treeManager();
            $manager->post($data, $class);

            return new JsonResponse(['success' => true, 'data' => $data]);
        }

        return $formPositions;
    }

    /**
     * Layout view.
     *
     * @throws InvalidArgumentException|NonUniqueResultException|\Doctrine\ORM\Mapping\MappingException|\ReflectionException|QueryException
     */
    protected function layout(Request $request)
    {
        if (!in_array('ROLE_EDIT', $this->getUser()->getRoles())) {
            $this->denyAccessUnlessGranted('ROLE_EDIT');
        }
        $this->arguments['view'] = 'layout';

        return $this->forward(
            'App\Controller\Admin\AdminController::edition',
            $this->editionArguments($request)
        );
    }

    /**
     * New view.
     *
     * @throws InvalidArgumentException|NonUniqueResultException|\Doctrine\ORM\Mapping\MappingException|\ReflectionException|QueryException
     */
    protected function new(Request $request)
    {
        if (!in_array('ROLE_EDIT', $this->getUser()->getRoles())) {
            $this->denyAccessUnlessGranted('ROLE_EDIT');
        }
        $this->arguments['view'] = 'new';

        return $this->forward(
            'App\Controller\Admin\AdminController::edition',
            $this->editionArguments($request)
        );
    }

    /**
     * Edit view.
     *
     * @throws InvalidArgumentException|NonUniqueResultException|\Doctrine\ORM\Mapping\MappingException|\ReflectionException|QueryException
     */
    protected function edit(Request $request)
    {
        if (!in_array('ROLE_EDIT', $this->getUser()->getRoles())) {
            $this->denyAccessUnlessGranted('ROLE_EDIT');
        }
        $this->arguments['view'] = 'edit';

        return $this->forward(
            'App\Controller\Admin\AdminController::edition',
            $this->editionArguments($request)
        );
    }

    /**
     * New video.
     *
     * @throws NonUniqueResultException
     */
    protected function video(Request $request): RedirectResponse
    {
        $website = $this->getWebsite();
        $this->adminLocator->videoService()->add($website->entity, $this->class);
        $interface = $this->getInterface($this->class);
        $referer = $request->headers->get('referer');

        return $referer ? $this->redirect($referer) : $this->redirectToRoute('admin_'.$interface['name'].'_edit', [
            'website' => $website->id,
            $interface['name'] => $request->get($interface['name']),
        ]);
    }

    /**
     * Show view.
     *
     * @throws \Exception
     */
    protected function show(Request $request)
    {
        $interface = $this->getInterface($this->class);
        $entity = $this->coreLocator->em()->getRepository($this->class)->find($request->get($interface['name']));
        $template = $this->template ?: 'admin/core/show.html.twig';
        if (!$entity) {
            throw $this->createNotFoundException(sprintf("Aucune entité pour l'ID %s", $request->get($interface['name'])));
        }
        if (empty($this->arguments['breadcrumb'])) {
            $this->breadcrumb($request);
        }

        return $this->adminRender($template, array_merge($this->arguments, [
            'pageTitle' => $this->pageTitle,
            'entity' => $entity,
            'interface' => $interface,
            'metadata' => $this->coreLocator->em()->getClassMetadata($this->class),
        ]));
    }

    /**
     * Duplicate.
     *
     * @throws NonUniqueResultException
     */
    protected function duplicate(Request $request)
    {
        $helper = $this->adminLocator->formDuplicateHelper();
        $helper->execute($request, $this->formType, $this->class, $this->formOptions, $this->formDuplicateManager);
        if (!$helper->getEntity()) {
            return new Response();
        }
        $render = $this->renderView('admin/core/duplicate.html.twig', [
            'pageTitle' => $this->pageTitle,
            'form' => $helper->getForm()->createView(),
            'interface' => $helper->getInterface(),
            'entity' => $helper->getEntityToDuplicate(),
            'refresh' => $request->get('refresh'),
            'template' => $request->get('template'),
        ]);
        if ($helper->isSubmitted()) {
            return new JsonResponse(['success' => $helper->isValid(), 'html' => $render]);
        }

        return new JsonResponse(['html' => $render]);
    }

    /**
     * Export.
     */
    protected function export(Request $request)
    {
        $form = null;
        $exportService = $this->exportService ?: $this->adminLocator->exportManagers()->coreService();
        $interface = $this->getInterface($this->class);
        $configuration = !empty($interface['configuration']) ? $interface['configuration'] : null;
        $filterFields = $configuration && $configuration->searchFilters ? $configuration->searchFilters : [];
        $referEntity = new $this->class();

        $fieldsCount = 0;
        foreach ($filterFields as $filterField) {
            $existing = method_exists($referEntity, 'get'.ucfirst($filterField)) || method_exists($referEntity, 'is'.ucfirst($filterField));
            if ($existing) {
                ++$fieldsCount;
            }
        }

        if ($fieldsCount > 0) {
            $form = $this->createForm(FilterType::class, null, [
                'website' => $this->getWebsite(),
                'filterName' => 'searchFilters',
                'interface' => $interface,
            ]);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $entities = $this->adminLocator->searchFilterService()->execute($request, $form, $interface);
                $response = $exportService->execute($entities, $interface);

                return $this->file($response['tempFile'], $response['fileName'], ResponseHeaderBag::DISPOSITION_INLINE);
            }
        }

        if ($request->get('export')) {
            $repository = $this->coreLocator->em()->getRepository($interface['classname']);
            $filterBuilder = $repository->createQueryBuilder('e');
            if (!empty($interface['masterField']) && $request->get($interface['masterField'])) {
                $filterBuilder->andWhere('e.'.$interface['masterField'].' = :'.$interface['masterField']);
                $filterBuilder->setParameter($interface['masterField'], $request->get($interface['masterField']));
            }
            $order = is_object($configuration) && $configuration->orderBy ? $configuration->orderBy : 'id';
            $sort = is_object($configuration) && $configuration->orderSort ? $configuration->orderSort : 'ASC';
            $filterBuilder->orderBy('e.'.$order, $sort);
            $entities = $filterBuilder->getQuery()->getResult();
            $response = $exportService->execute($entities, $interface);

            return $this->file($response['tempFile'], $response['fileName'], ResponseHeaderBag::DISPOSITION_INLINE);
        }

        return $this->adminRender('admin/core/form/export.html.twig', [
            'form' => !empty($form) ? $form->createView() : null,
            'interface' => $interface,
        ]);
    }

    /**
     * Entity position.
     *
     * @throws \Exception
     */
    protected function position(Request $request)
    {
        if (!in_array('ROLE_EDIT', $this->getUser()->getRoles())) {
            $this->denyAccessUnlessGranted('ROLE_EDIT');
        }

        if ($request->get('ajax')) {
            $interface = $this->getInterface($this->class);
            $entity = !empty($interface['name']) ? $this->coreLocator->em()->getRepository($this->class)->find($request->get($interface['name'])) : null;
            if (is_object($entity) && $request->get('position') && method_exists($entity, 'setPosition')) {
                $entity->setPosition(intval($request->get('position')));
                $this->coreLocator->em()->persist($entity);
                $this->coreLocator->em()->flush();
            }

            return new JsonResponse(['success' => true]);
        } else {
            $service = $this->adminLocator->positionService();
            $service->setVars($this->class, $request);

            $form = $this->createForm(PositionType::class, $service->getEntity(), [
                'data_class' => get_class($service->getEntity()),
                'old_position' => $service->getEntity()->getPosition(),
                'iterations' => $service->getCount(),
            ]);

            $ajaxView = $request->get('ajax-view');
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $service->execute($form, $form->getData());

                return !$ajaxView ? $this->redirect($request->headers->get('referer')) : new JsonResponse(['success' => true]);
            }

            $template = 'admin/core/form/position.html.twig';
            $arguments = [
                'ajaxView' => $ajaxView,
                'form' => $form->createView(),
                'entity' => $service->getEntity(),
                'interface' => $service->getInterface(),
            ];
            return !$ajaxView ? $this->adminRender($template, $arguments) : new JsonResponse(['html' => $this->renderView($template, $arguments)]);
        }
    }

    /**
     * Delete entity.
     *
     * @throws NonUniqueResultException
     */
    protected function delete(Request $request)
    {
        if (!in_array('ROLE_DELETE', $this->getUser()->getRoles())) {
            $this->denyAccessUnlessGranted('ROLE_DELETE');
        }
        if (is_object($this->deleteService)) {
            if (method_exists($this->deleteService, 'execute')) {
                $this->deleteService->execute();
            }
        }
        $referer = $request->headers->get('referer');
        $redirection = !empty($this->arguments['redirection']) ? $this->arguments['redirection']
            : ($referer ? str_replace('?open_modal=1', '', $referer) : null);
        $this->adminLocator->deleteManagers()->coreService()->execute($this->class, $this->entities);
        if ($this->formManager && !empty($this->arguments['formManagerMethod'])) {
            $method = $this->arguments['formManagerMethod'];
            $this->formManager->$method($this->arguments['entity'], $this->coreLocator->website()->entity);
            $this->coreLocator->em()->persist($this->arguments['entity']);
            $this->coreLocator->em()->flush();
        }
        if ($request->get('ajax')) {
            return new JsonResponse(['success' => true, 'redirection' => $redirection]);
        } elseif ($redirection) {
            return $redirection;
        } else {
            return new Response();
        }
    }

    /**
     * Breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        $label = $this->coreLocator->translator()->trans('Tableau de bord', [], 'admin_breadcrumb');
        $dashboardArgs = $this->coreLocator->routeArgs('admin_dashboard');
        $this->arguments['breadcrumb'][$label] = $this->coreLocator->router()->generate('admin_dashboard', $dashboardArgs);

        if (empty($items)) {
            $interface = $this->class ? $this->getInterface($this->class) : [];
            if (!empty($interface['classname']) && !empty($interface['name']) && $request->get($interface['name']) && $this->coreLocator->routeExist('admin_'.$interface['name'].'_index')) {
                $entityConfiguration = $this->coreLocator->em()->getRepository(Entity::class)->findOneBy([
                    'website' => $request->get('website'),
                    'className' => $interface['classname'],
                ]);
                $breadcrumb = $this->coreLocator->translator()->trans('breadcrumb', [], 'entity_'.$interface['name']);
                $plural = $this->coreLocator->translator()->trans('plural', [], 'entity_'.$interface['name']);
                $title = 'breadcrumb' !== $breadcrumb ? $breadcrumb : ('plural' !== $plural ? $plural : $entityConfiguration->getAdminName());
                $items[$title] = 'admin_'.$interface['name'].'_index';
            }
        }

        foreach ($items as $label => $route) {
            $asUrl = str_contains($route, '/');
            $routeArgs = !$asUrl ? $this->coreLocator->routeArgs($route) : false;
            $this->arguments['breadcrumb'][$label] = $asUrl ? $route : $this->coreLocator->router()->generate($route, $routeArgs);
        }
    }

    /**
     * Get edition arguments (new & edit).
     *
     * @throws NonUniqueResultException|InvalidArgumentException|\Doctrine\ORM\Mapping\MappingException|\ReflectionException|QueryException
     */
    protected function editionArguments(Request $request): array
    {
        $view = !empty($this->arguments['view']) ? $this->arguments['view'] : null;
        $interface = $this->class ? $this->getInterface($this->class) : [];
        $entity = $this->entity && is_object($this->entity) && str_contains(get_class($this->entity), 'Model') && property_exists($this->entity, 'entity')
            ? $this->entity->entity : ($this->entity ?: (!empty($interface['entity']) ? $interface['entity'] : null));

        if ($entity && $entity->getId()) {
            $this->adminLocator->urlManager()->synchronizeLocales($entity, $interface['website']);
        }

        $website = $interface ? $interface['website'] : $this->getWebsite();
        $website = $website instanceof Website ? WebsiteModel::fromEntity($website, $this->coreLocator) : $website;
        $mediasAlertExec = $entity instanceof Page || ($entity && method_exists($entity, 'isCustomLayout') && $entity->isCustomLayout());
        $mediasAlert = 'layout' === $view && $mediasAlertExec && $entity->getLayout()
            ? $this->coreLocator->em()->getRepository(BlockMediaRelation::class)->findWithEmptyAlt($entity->getLayout())
            : ('edit' === $view ? $this->adminLocator->mediasAlert($entity) : []);

        $arguments = [
            'pageTitle' => $this->pageTitle,
            'entity' => $entity,
            'interface' => $interface,
            'website' => $website,
            'configuration' => $website->configuration,
            'request' => $request,
            'entitylocale' => $request->get('entitylocale'),
            'formType' => $this->formType,
            'templateConfig' => $this->templateConfig,
            'formManager' => $this->formManager,
            'disableFlash' => $this->disableFlash,
            'class' => $this->class,
            'formOptions' => $this->formOptions,
            'template' => $this->template,
            'view' => !empty($this->arguments['view']) ? $this->arguments['view'] : null,
            'tooHeavyFiles' => $this->adminLocator->tooHeavyFiles($entity),
            'seoAlert' => $this->coreLocator->seoService()->seoAlert($entity, $website),
            'mediasAlert' => $mediasAlert,
            'seoModels' => $this->coreLocator->seoService()->getLocalesModels($entity, $website),
        ];

        if (empty($this->arguments['breadcrumb'])) {
            $this->breadcrumb($request);
        }

        if ($entity && method_exists($entity, 'getLayout') && $entity->getLayout()) {
            $arguments['tooHeavyFilesBlocks'] = $arguments['tooHeavyFiles'] = [];
            foreach ($entity->getLayout()->getZones() as $zone) {
                foreach ($zone->getCols() as $col) {
                    foreach ($col->getBlocks() as $block) {
                        $tooHeavyFiles = $this->adminLocator->tooHeavyFiles($block);
                        $arguments['tooHeavyFiles'] = array_merge($arguments['tooHeavyFiles'], $tooHeavyFiles);
                    }
                }
            }
        }

        if ($entity && method_exists($entity, 'getLayout')) {
            $arguments['layoutConfiguration'] = $this->coreLocator->em()->getRepository(LayoutConfiguration::class)->findOneBy(
                ['entity' => $interface['classname'], 'website' => $interface['website']]
            );
        }

        $arguments = array_merge($arguments, $this->arguments);

        return ['params' => (object) $arguments];
    }

    /**
     * Forward edition view (new & edit).
     *
     * @throws NonUniqueResultException|MappingException|ContainerExceptionInterface|NotFoundExceptionInterface|\ReflectionException|\Doctrine\ORM\Mapping\MappingException|InvalidArgumentException|InvalidArgumentException|InvalidArgumentException|InvalidArgumentException
     */
    public function edition($params): JsonResponse|string|Response
    {
        /** @var Request $request */
        $request = $params->request;
        $view = $params->view ?: debug_backtrace()[4]['function'];
        $template = $params->template ?: 'admin/core/'.$view.'.html.twig';
        $formHelper = $this->adminLocator->formHelper();
        $formHelper->execute($params->formType, $params->entity, $params->class, $params->formOptions, $params->formManager, $params->disableFlash, $view);
        $entity = $formHelper->getEntity();

        $arguments = array_merge($this->arguments, [
            'form' => $formHelper->getForm()?->createView(),
            'entity' => $entity,
            'medias' => $entity && !property_exists($params, 'medias') ? MediasModel::fromEntity($entity, $this->coreLocator)->mediasAndVideos
                : (property_exists($params, 'medias') ? $params->medias : []),
            'haveH1' => $formHelper->haveH1(),
            'namespace' => $this->getCurrentNamespace($params->request),
        ]);

        $arguments = array_merge((array) $params, $arguments);

        if (!empty($request->get('ajax'))) {
            $redirection = !empty($arguments['redirection']) ? $arguments['redirection'] : null;
            $redirectionHost = $redirection && !preg_match('/'.$request->getHost().'/', $redirection) ? $request->getSchemeAndHttpHost().$redirection : null;
            $success = $formHelper->getForm() && $formHelper->getForm()->isSubmitted() ? $formHelper->getForm()->isValid() : true;
            $response = ['success' => $success, 'html' => $this->renderView($template, $arguments), 'redirection' => $redirectionHost];
            if (isset($arguments['history'])) {
                $response['history'] = $arguments['history'];
            }

            return new JsonResponse($response);
        }

        if (!empty($formHelper->getRedirection())) {
            header('Location:'.$formHelper->getRedirection());
            exit;
        }

        ksort($arguments);

        return $this->adminRender($template, $arguments);
    }

    /**
     * Check entity locale.
     */
    protected function checkEntityLocale(Request $request): void
    {
        $website = $this->getWebsite();
        $localeRequest = $request->get('entitylocale');
        if ($localeRequest && !in_array($localeRequest, $website->configuration->allLocales)) {
            throw $this->createNotFoundException();
        }
    }

    /**
     * Get render.
     */
    protected function adminRender(string $template, array $arguments = [], ?Request $request = null): string|Response
    {
        if ($request && $request->get('ajax') || $request && $request->get('jsonResponse')) {
            return $this->renderView($template, $arguments);
        } else {
            return $this->render($template, $arguments);
        }
    }
}
