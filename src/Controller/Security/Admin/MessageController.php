<?php

declare(strict_types=1);

namespace App\Controller\Security\Admin;

use App\Controller\Admin\AdminController;
use App\Entity\Security\Message;
use App\Form\Type\Security\Admin\MessageType;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * MessageController.
 *
 * Security Message management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_USERS')]
#[Route('/admin-%security_token%/{website}/security/users/messages', schemes: '%protocol%')]
class MessageController extends AdminController
{
    protected ?string $class = Message::class;
    protected ?string $formType = MessageType::class;

    /**
     * Index Message.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_securitymessage_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Message.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_securitymessage_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Message.
     *
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    #[Route('/edit/{securitymessage}', name: 'admin_securitymessage_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Message.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{securitymessage}', name: 'admin_securitymessage_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Delete Message.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{securitymessage}', name: 'admin_securitymessage_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('securitymessage')) {
            $items[$this->coreLocator->translator()->trans('Messages', [], 'admin_breadcrumb')] = 'admin_securitymessage_index';
        }

        parent::breadcrumb($request, $items);
    }
}
