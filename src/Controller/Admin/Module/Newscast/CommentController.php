<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Newscast;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Newscast\Comment;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CommentController.
 *
 * Newscast Comment Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_NEWSCAST')]
#[Route('/admin-%security_token%/{website}/newscasts/comments', schemes: '%protocol%')]
class CommentController extends AdminController
{
    protected ?string $class = Comment::class;

    /**
     * Index Comments.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_comment_index', defaults: ['join' => null], methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * Show Comments.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{comment}', name: 'admin_comment_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }
}
