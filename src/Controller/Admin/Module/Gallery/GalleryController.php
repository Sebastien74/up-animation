<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Gallery;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Gallery\Gallery;
use App\Form\Type\Module\Gallery\GalleryType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * GalleryController.
 *
 * Gallery Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_GALLERY')]
#[Route('/admin-%security_token%/{website}/galleries', schemes: '%protocol%')]
class GalleryController extends AdminController
{
    protected ?string $class = Gallery::class;
    protected ?string $formType = GalleryType::class;

    /**
     * Index Gallery.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_gallery_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Gallery.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_gallery_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Gallery.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{gallery}', name: 'admin_gallery_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Gallery.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{gallery}', name: 'admin_gallery_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Gallery.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{gallery}', name: 'admin_gallery_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Gallery.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{gallery}', name: 'admin_gallery_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('gallery')) {
            $items[$this->coreLocator->translator()->trans('Galeries', [], 'admin_breadcrumb')] = 'admin_gallery_index';
        }

        parent::breadcrumb($request, $items);
    }
}
