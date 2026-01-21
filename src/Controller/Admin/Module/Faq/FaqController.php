<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Faq;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Faq\Faq;
use App\Form\Type\Module\Faq\FaqType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * FaqController.
 *
 * Faq Action management.
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
#[IsGranted('ROLE_FAQ')]
#[Route('/admin-%security_token%/{website}/faqs', schemes: '%protocol%')]
class FaqController extends AdminController
{
    protected ?string $class = Faq::class;
    protected ?string $formType = FaqType::class;

    /**
     * Index Faq.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_faq_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Faq.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_faq_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Faq.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{faq}', name: 'admin_faq_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Faq.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{faq}', name: 'admin_faq_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Faq.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{faq}', name: 'admin_faq_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Faq.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{faq}', name: 'admin_faq_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('faq')) {
            $items[$this->coreLocator->translator()->trans('FAQ', [], 'admin_breadcrumb')] = 'admin_faq_index';
        }

        parent::breadcrumb($request, $items);
    }
}
