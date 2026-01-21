<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Timeline;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Timeline\Step;
use App\Form\Type\Module\Timeline\StepType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * StepController.
 *
 * Step Timeline management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_FORM')]
#[Route('/admin-%security_token%/{website}/timelines/steps', schemes: '%protocol%')]
class StepController extends AdminController
{
    protected ?string $class = Step::class;
    protected ?string $formType = StepType::class;

    /**
     * Index Step.
     *
     * {@inheritdoc}
     */
    #[Route('/{timeline}/index', name: 'admin_timelinestep_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Step.
     *
     * {@inheritdoc}
     */
    #[Route('/{timeline}/new', name: 'admin_timelinestep_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Step.
     *
     * {@inheritdoc}
     */
    #[Route('/{timeline}/edit/{timelinestep}', name: 'admin_timelinestep_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Step.
     *
     * {@inheritdoc}
     */
    #[Route('/{timeline}/show/{timelinestep}', name: 'admin_timelinestep_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Step.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{timelinestep}', name: 'admin_timelinestep_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Step.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{timelinestep}', name: 'admin_timelinestep_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('timeline')) {
            $items[$this->coreLocator->translator()->trans('Chronologies', [], 'admin_breadcrumb')] = 'admin_timeline_index';
            if ($request->get('timelinestep')) {
                $items[$this->coreLocator->translator()->trans('Étapes', [], 'admin_breadcrumb')] = 'admin_timelinestep_index';
            }
        }

        parent::breadcrumb($request, $items);
    }
}
