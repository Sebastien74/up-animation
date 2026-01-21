<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Timeline;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Timeline\Timeline;
use App\Form\Type\Module\Timeline\TimelineType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * TimelineController.
 *
 * Timeline Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_TIMELINE')]
#[Route('/admin-%security_token%/{website}/timelines', schemes: '%protocol%')]
class TimelineController extends AdminController
{
    protected ?string $class = Timeline::class;
    protected ?string $formType = TimelineType::class;

    /**
     * Index Timeline.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_timeline_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Timeline.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_timeline_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Timeline.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{timeline}', name: 'admin_timeline_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Position Timeline.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{timeline}', name: 'admin_timeline_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Timeline.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{timeline}', name: 'admin_timeline_delete', methods: 'DELETE')]
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
        }

        parent::breadcrumb($request, $items);
    }
}
