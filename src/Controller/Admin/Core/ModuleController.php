<?php

declare(strict_types=1);

namespace App\Controller\Admin\Core;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Module;
use App\Form\Type\Core\ModuleType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ModuleController.
 *
 * Module management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/configuration/modules', schemes: '%protocol%')]
class ModuleController extends AdminController
{
    protected ?string $class = Module::class;
    protected ?string $formType = ModuleType::class;

    /**
     * Index Module.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_module_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Module.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_module_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Module.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{module}', name: 'admin_module_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Module.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{module}', name: 'admin_module_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Module.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{module}', name: 'admin_module_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Module.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{module}', name: 'admin_module_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }
}
