<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Newscast;

use App\Controller\Admin\AdminController;
use App\Entity\Layout\BlockType;
use App\Entity\Module\Newscast\Newscast;
use App\Form\Interface\ModuleFormManagerInterface;
use App\Form\Type\Module\Newscast\NewscastDuplicateType;
use App\Form\Type\Module\Newscast\NewscastType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * NewscastController.
 *
 * Newscast Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_NEWSCAST')]
#[Route('/admin-%security_token%/{website}/newscasts', schemes: '%protocol%')]
class NewscastController extends AdminController
{
    protected ?string $class = Newscast::class;
    protected ?string $formType = NewscastType::class;

    /**
     * NewscastController constructor.
     */
    public function __construct(
        protected ModuleFormManagerInterface $moduleFormInterface,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $moduleFormInterface->newscast();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index Newscast.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_newscast_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        $this->arguments['interfaceHideColumns'] = ['position'];

        return parent::index($request, $paginator);
    }

    /**
     * New Newscast.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_newscast_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Newscast.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{newscast}', name: 'admin_newscast_edit', methods: 'GET|POST')]
    #[Route('/layout/{newscast}', name: 'admin_newscast_layout', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        $newscast = $this->coreLocator->em()->getRepository($this->class)->find($request->get('newscast'));
        if ($newscast instanceof Newscast && !$newscast->isCustomLayout()) {
            $this->template = 'admin/page/newscast/newscast-edit.html.twig';
            $this->arguments['activeTab'] = $request->get('tab');
        } else {
            $this->arguments['blockTypesDisabled'] = ['layout' => ['']];
            $this->arguments['blockTypesCategories'] = ['layout', 'content', 'global', 'action', 'modules'];
            $this->arguments['blockTypeAction'] = $this->coreLocator->em()->getRepository(BlockType::class)->findOneBy(['slug' => 'core-action']);
        }

        return parent::edit($request);
    }

    /**
     * Medias Newscast.
     *
     * @throws NonUniqueResultException|MappingException
     */
    #[Route('/medias/{newscast}', name: 'admin_newscast_medias', methods: 'GET|POST')]
    public function medias(Request $request): Response
    {
        $newscast = $this->coreLocator->em()->getRepository($this->class)->find($request->attributes->getInt('newscast'));
        $this->breadcrumb($request);

        return $this->render('admin/page/newscast/newscast-medias.html.twig', array_merge($this->arguments, [
            'entity' => $newscast,
            'website' => $this->getWebsite(),
            'activeTab' => $request->get('tab'),
            'interface' => $this->getInterface($this->class),
            'tooHeavyFiles' => $this->adminLocator->tooHeavyFiles($newscast),
            'mediasAlert' => $this->adminLocator->mediasAlert($newscast),
            'seoAlert' => $this->coreLocator->seoService()->seoAlert($newscast, $this->coreLocator->website()),
        ]));
    }

    /**
     * Video Newscast.
     *
     * {@inheritdoc}
     */
    #[Route('/video/{newscast}', name: 'admin_newscast_video', methods: 'GET|POST')]
    public function video(Request $request): RedirectResponse
    {
        return parent::video($request);
    }

    /**
     * Position Newscast.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{newscast}', name: 'admin_newscast_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Export Newscast[].
     *
     * {@inheritdoc}
     */
    #[Route('/export', name: 'admin_newscast_export', methods: 'GET|POST')]
    public function export(Request $request)
    {
        return parent::export($request);
    }

    /**
     * Duplicate Newscast.
     *
     * {@inheritdoc}
     */
    #[Route('/duplicate/{newscast}', name: 'admin_newscast_duplicate', methods: 'GET|POST')]
    public function duplicate(Request $request)
    {
        $this->formType = NewscastDuplicateType::class;
        $this->formDuplicateManager = $this->moduleFormInterface->newscastDuplicate();

        return parent::duplicate($request);
    }

    /**
     * Delete Newscast.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{newscast}', name: 'admin_newscast_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('newscast')) {
            $items[$this->coreLocator->translator()->trans('Actualités', [], 'admin_breadcrumb')] = 'admin_newscast_index';
        }

        parent::breadcrumb($request, $items);
    }
}
