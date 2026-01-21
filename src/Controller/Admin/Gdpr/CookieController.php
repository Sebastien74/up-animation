<?php

declare(strict_types=1);

namespace App\Controller\Admin\Gdpr;

use App\Controller\Admin\AdminController;
use App\Entity\Gdpr\Cookie;
use App\Form\Interface\GdprFormManagerInterface;
use App\Form\Type\Gdpr\CookieType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CookieController.
 *
 * Gdpr Cookie management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/gdpr/cookies', schemes: '%protocol%')]
class CookieController extends AdminController
{
    protected ?string $class = Cookie::class;
    protected ?string $formType = CookieType::class;

    /**
     * CookieController constructor.
     */
    public function __construct(
        protected readonly GdprFormManagerInterface $gdprFormManager,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $gdprFormManager->cookie();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index Gdpr Cookie.
     *
     * {@inheritdoc}
     */
    #[Route('/{gdprcategory}/{gdprgroup}/index', name: 'admin_gdprcookie_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Gdpr Cookie.
     *
     * {@inheritdoc}
     */
    #[Route('/{gdprgroup}/new', name: 'admin_gdprcookie_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Gdpr Cookie.
     *
     * {@inheritdoc}
     */
    #[Route('/{gdprcategory}/{gdprgroup}/edit/{gdprcookie}', name: 'admin_gdprcookie_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Gdpr Cookie.
     *
     * {@inheritdoc}
     */
    #[Route('/{gdprcategory}/{gdprgroup}/show/{gdprcookie}', name: 'admin_gdprcookie_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Gdpr Cookie.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{gdprcookie}', name: 'admin_gdprcookie_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Gdpr Cookie.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{gdprcookie}', name: 'admin_gdprcookie_delete', methods: 'DELETE')]
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
            if ($request->get('gdprcookie')) {
                $items[$this->coreLocator->translator()->trans('Cookies', [], 'admin_breadcrumb')] = 'admin_gdprcookie_index';
            }
        }

        parent::breadcrumb($request, $items);
    }
}
