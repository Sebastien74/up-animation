<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Form;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Form\StepForm;
use App\Form\Interface\ModuleFormManagerInterface;
use App\Form\Type\Module\Form\StepFormType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * StepFormController.
 *
 * Steps Form Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_STEP_FORM')]
#[Route('/admin-%security_token%/{website}/steps/forms', schemes: '%protocol%')]
class StepFormController extends AdminController
{
    protected ?string $class = StepForm::class;
    protected ?string $formType = StepFormType::class;

    /**
     * StepFormController constructor.
     */
    public function __construct(
        protected ModuleFormManagerInterface $moduleFormInterface,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $moduleFormInterface->stepForm();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index StepForm.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_stepform_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New StepForm.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_stepform_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit StepForm.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{stepform}', name: 'admin_stepform_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show StepForm.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{stepform}', name: 'admin_stepform_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Delete StepForm.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{stepform}', name: 'admin_stepform_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('stepform')) {
            $items[$this->coreLocator->translator()->trans('Formulaires', [], 'admin_breadcrumb')] = 'admin_stepform_index';
        }

        parent::breadcrumb($request, $items);
    }
}
