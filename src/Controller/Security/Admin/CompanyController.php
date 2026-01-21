<?php

declare(strict_types=1);

namespace App\Controller\Security\Admin;

use App\Controller\Admin\AdminController;
use App\Entity\Security\Company;
use App\Form\Interface\SecurityFormManagerInterface;
use App\Form\Type\Security\Admin\CompanyType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CompanyController.
 *
 * Security Company management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/security/companies', schemes: '%protocol%')]
class CompanyController extends AdminController
{
    protected ?string $class = Company::class;
    protected ?string $formType = CompanyType::class;

    /**
     * CompanyController constructor.
     */
    public function __construct(
        protected SecurityFormManagerInterface $securityFormLocator,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $securityFormLocator->adminCompany();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index Company.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_securitycompany_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Company.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_securitycompany_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Company.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{securitycompany}', name: 'admin_securitycompany_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        $this->template = 'admin/page/security/company.html.twig';

        return parent::edit($request);
    }

    /**
     * Show Company.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{securitycompany}', name: 'admin_securitycompany_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Delete Company.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{securitycompany}', name: 'admin_securitycompany_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('securitycompany')) {
            $items[$this->coreLocator->translator()->trans('Entreprises', [], 'admin_breadcrumb')] = 'admin_securitycompany_index';
        }

        parent::breadcrumb($request, $items);
    }
}
