<?php

declare(strict_types=1);

namespace App\Controller\Admin\Development;

use App\Controller\Admin\AdminController;
use App\Entity\Core\ScheduledCommand;
use App\Form\Type\Development\ScheduledCommandType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ScheduledCommandController.
 *
 * Scheduled command management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/development/scheduled-command', schemes: '%protocol%')]
class ScheduledCommandController extends AdminController
{
    protected ?string $class = ScheduledCommand::class;
    protected ?string $formType = ScheduledCommandType::class;

    /**
     * Index ScheduledCommand.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_command_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New ScheduledCommand.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_command_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit ScheduledCommand.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{command}', name: 'admin_command_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show ScheduledCommand.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{command}', name: 'admin_command_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Delete ScheduledCommand.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{command}', name: 'admin_command_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('command')) {
            $items[$this->coreLocator->translator()->trans('Tâches planifiées', [], 'admin_breadcrumb')] = 'admin_command_index';
        }

        parent::breadcrumb($request, $items);
    }
}
