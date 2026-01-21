<?php

declare(strict_types=1);

namespace App\Controller\Admin\Gdpr;

use App\Controller\Admin\AdminController;
use App\Entity\Gdpr\Group;
use App\Form\Type\Gdpr\GroupType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * GroupController.
 *
 * Gdpr Group management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/gdpr/groups', schemes: '%protocol%')]
class GroupController extends AdminController
{
    protected ?string $class = Group::class;
    protected ?string $formType = GroupType::class;

    /**
     * Index Gdpr Group.
     *
     * {@inheritdoc}
     */
    #[Route('/{gdprcategory}/index', name: 'admin_gdprgroup_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Gdpr Group.
     *
     * {@inheritdoc}
     */
    #[Route('/{gdprcategory}/new', name: 'admin_gdprgroup_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Gdpr Group.
     *
     * {@inheritdoc}
     */
    #[Route('/{gdprcategory}/edit/{gdprgroup}', name: 'admin_gdprgroup_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Gdpr Group.
     *
     * {@inheritdoc}
     */
    #[Route('/{gdprcategory}/show/{gdprgroup}', name: 'admin_gdprgroup_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Gdpr Group.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{gdprgroup}', name: 'admin_gdprgroup_position', methods: 'GET')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Gdpr Group.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{gdprgroup}', name: 'admin_gdprgroup_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('gdprcategory')) {
            $items[$this->coreLocator->translator()->trans('RGPD', [], 'admin_breadcrumb')] = 'admin_gdprgroup_index';
            if ($request->get('gdprgroup')) {
                $items[$this->coreLocator->translator()->trans('Groupes', [], 'admin_breadcrumb')] = 'admin_gdprgroup_index';
            }
        }

        parent::breadcrumb($request, $items);
    }
}
