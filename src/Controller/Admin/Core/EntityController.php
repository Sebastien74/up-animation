<?php

declare(strict_types=1);

namespace App\Controller\Admin\Core;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Entity;
use App\Entity\Core\Website;
use App\Form\Interface\CoreFormManagerInterface;
use App\Form\Type\Core\EntityType;
use App\Service\Development\EntityService;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use App\Service\Translation\Extractor;
use Doctrine\ORM\NonUniqueResultException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * EntityController.
 *
 * Entity management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/configuration/entities', schemes: '%protocol%')]
class EntityController extends AdminController
{
    protected ?string $class = Entity::class;
    protected ?string $formType = EntityType::class;

    /**
     * EntityController constructor.
     */
    public function __construct(
        protected CoreFormManagerInterface $coreFormInterface,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $coreFormInterface->entityConfiguration();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Entities generator.
     *
     * @throws NonUniqueResultException
     */
    #[IsGranted('ROLE_INTERNAL')]
    #[Route('/generate', name: 'admin_entities_generator', methods: 'GET')]
    public function generate(Request $request, Website $website, EntityService $entityService, Extractor $extractor): RedirectResponse
    {
        foreach ($website->getConfiguration()->getAllLocales() as $locale) {
            $entityService->execute($website, $locale);
        }

        $configuration = $website->getConfiguration();
        $extractor->extractEntities($website, $configuration->getLocale(), $configuration->getAllLocales());
        $extractor->clearCache();

        if ($request->headers->get('referer')) {
            return $this->redirect($request->headers->get('referer'));
        }

        return $this->redirectToRoute('admin_dashboard', ['website' => $website->getId()]);
    }

    /**
     * Index Entity.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_entity_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Entity.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_entity_new', methods: 'GET|POST')]
    public function new(Request $request): Response
    {
        return parent::new($request);
    }

    /**
     * Edit Entity.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{entity}', name: 'admin_entity_edit', methods: 'GET|POST')]
    public function edit(Request $request): Response
    {
        $this->template = 'admin/page/core/entity.html.twig';

        return parent::edit($request);
    }

    /**
     * Show Entity.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{entity}', name: 'admin_entity_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Entity.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{entity}', name: 'admin_entity_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Entity.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{entity}', name: 'admin_entity_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }
}
