<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Gallery;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Gallery\Teaser;
use App\Form\Type\Module\Gallery\TeaserType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * TeaserController.
 *
 * Event Teaser Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_GALLERY')]
#[Route('/admin-%security_token%/{website}/galleries/teasers', schemes: '%protocol%')]
class TeaserController extends AdminController
{
    protected ?string $class = Teaser::class;
    protected ?string $formType = TeaserType::class;

    /**
     * Index Teaser.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_galleryteaser_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Teaser.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_galleryteaser_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Teaser.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{galleryteaser}', name: 'admin_galleryteaser_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Teaser.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{galleryteaser}', name: 'admin_galleryteaser_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Teaser.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{galleryteaser}', name: 'admin_galleryteaser_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Teaser.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{galleryteaser}', name: 'admin_galleryteaser_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('galleryteaser')) {
            $items[$this->coreLocator->translator()->trans('Teasers', [], 'admin_breadcrumb')] = 'admin_galleryteaser_index';
        }

        parent::breadcrumb($request, $items);
    }
}
