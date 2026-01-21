<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Portfolio;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Portfolio\Teaser;
use App\Form\Type\Module\Portfolio\TeaserType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * TeaserController.
 *
 * Portfolio Teaser Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_PORTFOLIO')]
#[Route('/admin-%security_token%/{website}/portfolios/teasers', schemes: '%protocol%')]
class TeaserController extends AdminController
{
    protected ?string $class = Teaser::class;
    protected ?string $formType = TeaserType::class;

    /**
     * Index Teaser.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_portfolioteaser_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Teaser.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_portfolioteaser_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Teaser.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{portfolioteaser}', name: 'admin_portfolioteaser_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Teaser.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{portfolioteaser}', name: 'admin_portfolioteaser_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Teaser.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{portfolioteaser}', name: 'admin_portfolioteaser_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Teaser.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{portfolioteaser}', name: 'admin_portfolioteaser_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('portfolioteaser')) {
            $items[$this->coreLocator->translator()->trans('Teasers', [], 'admin_breadcrumb')] = 'admin_portfolioteaser_index';
        }

        parent::breadcrumb($request, $items);
    }
}
