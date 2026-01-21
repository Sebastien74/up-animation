<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Form;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Form\ContactForm;
use App\Entity\Module\Form\Form;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * FormContactController.
 *
 * Form Contact management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_FORM')]
#[Route('/admin-%security_token%/{website}/forms/contacts', schemes: '%protocol%')]
class ContactFormController extends AdminController
{
    protected ?string $class = ContactForm::class;

    /**
     * ContactFormController constructor.
     */
    public function __construct(
        protected \App\Service\Interface\CoreLocatorInterface $coreLocator,
        protected \App\Service\Interface\AdminLocatorInterface $adminLocator,
    ) {
        $this->deleteService = $adminLocator->deleteManagers()->contactsService();
        $this->exportService = $adminLocator->exportManagers()->contactsService();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index ContactForm.
     *
     * {@inheritdoc}
     */
    #[Route('/{form}/index', name: 'admin_formcontact_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        $form = $this->coreLocator->em()->getRepository(Form::class)->find($request->attributes->getInt('form'));
        $prefix = $this->coreLocator->translator()->trans('Contacts', [], 'admin');
        $this->pageTitle = $form ? $prefix.' : '.$form->getAdminName() : $prefix;

        return parent::index($request, $paginator);
    }

    /**
     * Show ContactForm.
     *
     * {@inheritdoc}
     */
    #[Route('/{form}/show/{formcontact}', name: 'admin_formcontact_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Export ContactForm[].
     *
     * {@inheritdoc}
     */
    #[Route('/export', name: 'admin_formcontact_export', methods: 'GET|POST')]
    public function export(Request $request)
    {
        return parent::export($request);
    }

    /**
     * Delete ContactForm.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{formcontact}', name: 'admin_formcontact_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('form')) {
            $items[$this->coreLocator->translator()->trans('Formulaires', [], 'admin_breadcrumb')] = 'admin_form_index';
            if ($request->get('formcontact')) {
                $items[$this->coreLocator->translator()->trans('Contacts', [], 'admin_breadcrumb')] = 'admin_formcontact_index';
            }
        }

        parent::breadcrumb($request, $items);
    }
}
