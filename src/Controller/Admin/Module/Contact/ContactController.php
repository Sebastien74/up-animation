<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Contact;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Contact\Contact;
use App\Form\Type\Module\Contact\ContactType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ContactController.
 *
 * Contact Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_CONTACT')]
#[Route('/admin-%security_token%/{website}/contacts', schemes: '%protocol%')]
class ContactController extends AdminController
{
    protected ?string $class = Contact::class;
    protected ?string $formType = ContactType::class;

    /**
     * Index Contact.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_contact_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Contact.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_contact_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Contact.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{contact}', name: 'admin_contact_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Contact.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{contact}', name: 'admin_contact_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Contact.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{contact}', name: 'admin_contact_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Contact.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{contact}', name: 'admin_contact_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('contact')) {
            $items[$this->coreLocator->translator()->trans('Informations de contact', [], 'admin_breadcrumb')] = 'admin_contact_index';
        }

        parent::breadcrumb($request, $items);
    }
}
