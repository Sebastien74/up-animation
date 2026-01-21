<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Slider;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Slider\Slider;
use App\Form\Type\Module\Slider\SliderType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * SliderController.
 *
 * Slider Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_SLIDER')]
#[Route('/admin-%security_token%/{website}/sliders', schemes: '%protocol%')]
class SliderController extends AdminController
{
    protected ?string $class = Slider::class;
    protected ?string $formType = SliderType::class;

    /**
     * SliderController constructor.
     */
    public function __construct(
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index Slider.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_slider_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Slider.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_slider_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Slider.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{slider}', name: 'admin_slider_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Video Slider.
     *
     * {@inheritdoc}
     */
    #[Route('/video/{slider}', name: 'admin_slider_video', methods: 'GET|POST')]
    public function video(Request $request): RedirectResponse
    {
        return parent::video($request);
    }

    /**
     * Show Slider.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{slider}', name: 'admin_slider_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Slider.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{slider}', name: 'admin_slider_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Slider.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{slider}', name: 'admin_slider_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('slider')) {
            $items[$this->coreLocator->translator()->trans('Carrousels', [], 'admin_breadcrumb')] = 'admin_slider_index';
        }

        parent::breadcrumb($request, $items);
    }
}
