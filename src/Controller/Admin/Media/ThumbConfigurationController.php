<?php

declare(strict_types=1);

namespace App\Controller\Admin\Media;

use App\Controller\Admin\AdminController;
use App\Entity\Media\ThumbConfiguration;
use App\Form\Type\Media\ThumbConfigurationType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ThumbConfigurationController.
 *
 * Media ThumbConfiguration management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/medias/thumbs-configurations', schemes: '%protocol%')]
class ThumbConfigurationController extends AdminController
{
    protected ?string $class = ThumbConfiguration::class;
    protected ?string $formType = ThumbConfigurationType::class;

    /**
     * Index ThumbConfiguration.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_thumbconfiguration_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New ThumbConfiguration.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_thumbconfiguration_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit ThumbConfiguration.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{thumbconfiguration}', name: 'admin_thumbconfiguration_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show ThumbConfiguration.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{thumbconfiguration}', name: 'admin_thumbconfiguration_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position ThumbConfiguration.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{thumbconfiguration}', name: 'admin_thumbconfiguration_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete ThumbConfiguration.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{thumbconfiguration}', name: 'admin_thumbconfiguration_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('thumbconfiguration')) {
            $items[$this->coreLocator->translator()->trans('Thumbnails', [], 'admin_breadcrumb')] = 'admin_thumbconfiguration_index';
        }

        parent::breadcrumb($request, $items);
    }
}
