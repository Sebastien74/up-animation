<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Newscast;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Newscast\Teaser;
use App\Form\Interface\ModuleFormManagerInterface;
use App\Form\Type\Module\Newscast\TeaserType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * TeaserController.
 *
 * Newscast Teaser Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_NEWSCAST')]
#[Route('/admin-%security_token%/{website}/newscasts/teasers', schemes: '%protocol%')]
class TeaserController extends AdminController
{
    protected ?string $class = Teaser::class;
    protected ?string $formType = TeaserType::class;

    /**
     * ListingController constructor.
     */
    public function __construct(
        protected ModuleFormManagerInterface $moduleFormInterface,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $moduleFormInterface->newscastTeaser();
        $this->exportService = $adminLocator->exportManagers()->productsService();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index Teaser.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_newscastteaser_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Teaser.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_newscastteaser_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Teaser.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{newscastteaser}', name: 'admin_newscastteaser_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Teaser.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{newscastteaser}', name: 'admin_newscastteaser_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Teaser.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{newscastteaser}', name: 'admin_newscastteaser_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Teaser.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{newscastteaser}', name: 'admin_newscastteaser_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('newscastteaser')) {
            $items[$this->coreLocator->translator()->trans('Teasers', [], 'admin_breadcrumb')] = 'admin_newscastteaser_index';
        }

        parent::breadcrumb($request, $items);
    }
}
