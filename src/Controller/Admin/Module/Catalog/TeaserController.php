<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Catalog;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Catalog\Teaser;
use App\Form\Type\Module\Catalog\TeaserType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * TeaserController.
 *
 * Catalog Teaser Product[] management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_CATALOG')]
#[Route('/admin-%security_token%/{website}/module/catalogs/teasers', schemes: '%protocol%')]
class TeaserController extends AdminController
{
    protected ?string $class = Teaser::class;
    protected ?string $formType = TeaserType::class;

    /**
     * Index Teaser.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_productteaser_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Teaser.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_productteaser_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Teaser.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{productteaser}', name: 'admin_productteaser_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Teaser.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{productteaser}', name: 'admin_productteaser_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Teaser.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{productteaser}', name: 'admin_productteaser_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Teaser.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{productteaser}', name: 'admin_productteaser_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('productteaser')) {
            $items[$this->coreLocator->translator()->trans('Teasers', [], 'admin_breadcrumb')] = 'admin_productteaser_index';
        }

        parent::breadcrumb($request, $items);
    }
}
