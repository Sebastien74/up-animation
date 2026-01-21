<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Form;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Form\ContactStepForm;
use App\Entity\Module\Form\Form;
use App\Entity\Module\Form\StepForm;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ContactStepFormController.
 *
 * Form Contact management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_FORM')]
#[Route('/admin-%security_token%/{website}/steps/forms/contacts', schemes: '%protocol%')]
class ContactStepFormController extends AdminController
{
    protected ?string $class = ContactStepForm::class;

    /**
     * ContactFormController constructor.
     */
    public function __construct(
        protected \App\Service\Interface\CoreLocatorInterface $coreLocator,
        protected \App\Service\Interface\AdminLocatorInterface $adminLocator,
    ) {
        $this->deleteService = $adminLocator->deleteManagers()->contactsService();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index ContactStepForm.
     *
     * {@inheritdoc}
     */
    #[Route('/index/{stepform}', name: 'admin_contactstepform_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        $form = $this->coreLocator->em()->getRepository(StepForm::class)->find($request->attributes->getInt('stepform'));
        $prefix = $this->coreLocator->translator()->trans('Contacts', [], 'admin');
        $this->pageTitle = $form ? $prefix.' : '.$form->getAdminName() : $prefix;

        return parent::index($request, $paginator);
    }

    /**
     * Show ContactStepForm.
     *
     * {@inheritdoc}
     */
    #[Route('/{stepform}/show/{contactstepform}', name: 'admin_contactstepform_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Delete ContactStepForm.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{contactstepform}', name: 'admin_contactstepform_delete', methods: 'DELETE')]
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
            if ($request->get('formcontact')) {
                $items[$this->coreLocator->translator()->trans('Contacts', [], 'admin_breadcrumb')] = 'admin_contactstepform_index';
            }
        }

        parent::breadcrumb($request, $items);
    }
}
