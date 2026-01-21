<?php

declare(strict_types=1);

namespace App\Controller\Admin\Layout;

use App\Controller\Admin\AdminController;
use App\Entity\Layout\BlockType;
use App\Form\Type\Layout\Management\BlockTypeType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * BlockTypeController.
 *
 * Layout BlockType management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/layouts/blocks-types', schemes: '%protocol%')]
class BlockTypeController extends AdminController
{
    protected ?string $class = BlockType::class;
    protected ?string $formType = BlockTypeType::class;

    /**
     * Index BlockType.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_blocktype_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New BlockType.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_blocktype_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit BlockType.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{blocktype}', name: 'admin_blocktype_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show BlockType.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{blocktype}', name: 'admin_blocktype_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position BlockType.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{blocktype}', name: 'admin_blocktype_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete BlockType.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{blocktype}', name: 'admin_blocktype_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('blocktype')) {
            $items[$this->coreLocator->translator()->trans('Types', [], 'admin_breadcrumb')] = 'admin_blocktype_index';
        }

        parent::breadcrumb($request, $items);
    }
}
