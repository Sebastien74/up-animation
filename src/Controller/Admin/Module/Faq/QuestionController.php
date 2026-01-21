<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Faq;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Faq\Question;
use App\Form\Type\Module\Faq\QuestionType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * QuestionController.
 *
 * Faq Question Action management
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
#[IsGranted('ROLE_FAQ')]
#[Route('/admin-%security_token%/{website}/faqs/questions', schemes: '%protocol%')]
class QuestionController extends AdminController
{
    protected ?string $class = Question::class;
    protected ?string $formType = QuestionType::class;

    /**
     * Index Question.
     *
     * {@inheritdoc}
     */
    #[Route('/{faq}/index', name: 'admin_faqquestion_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Question.
     *
     * {@inheritdoc}
     */
    #[Route('/{faq}/new', name: 'admin_faqquestion_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Question.
     *
     * {@inheritdoc}
     */
    #[Route('/{faq}/edit/{faqquestion}', name: 'admin_faqquestion_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        $this->template = 'admin/page/faq/question-edit.html.twig';

        return parent::edit($request);
    }

    /**
     * Show Question.
     *
     * {@inheritdoc}
     */
    #[Route('/{faq}/show/{faqquestion}', name: 'admin_faqquestion_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Question.
     *
     * {@inheritdoc}
     */
    #[Route('/{faq}/position/{faqquestion}', name: 'admin_faqquestion_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Question.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{faqquestion}', name: 'admin_faqquestion_delete', methods: 'DELETE')]
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
            if ($request->get('faqquestion')) {
                $items[$this->coreLocator->translator()->trans('Questions', [], 'admin_breadcrumb')] = 'admin_faqquestion_index';
            }
        }

        parent::breadcrumb($request, $items);
    }
}
