<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Newsletter;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Newsletter\Email;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * EmailController.
 *
 * Newsletters Email Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_NEWSLETTER')]
#[Route('/admin-%security_token%/{website}/newsletters/campaigns/emails', schemes: '%protocol%')]
class EmailController extends AdminController
{
    protected ?string $class = Email::class;

    /**
     * Index Email.
     *
     * {@inheritdoc}
     */
    #[Route('/{campaign}/index', name: 'admin_newsletteremail_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * Show Email.
     *
     * {@inheritdoc}
     */
    #[Route('/{campaign}/show/{newsletteremail}', name: 'admin_newsletteremail_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Export Email[].
     *
     * {@inheritdoc}
     */
    #[Route('/export', name: 'admin_newsletteremail_export', methods: 'GET|POST')]
    public function export(Request $request)
    {
        return parent::export($request);
    }

    /**
     * Delete Email.
     *
     * {@inheritdoc}
     */
    #[Route('/{campaign}/delete/{newsletteremail}', name: 'admin_newsletteremail_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('campaign')) {
            $items[$this->coreLocator->translator()->trans('Campagnes', [], 'admin_breadcrumb')] = 'admin_campaign_index';
            if ($request->get('newsletteremail')) {
                $items[$this->coreLocator->translator()->trans('Emails', [], 'admin_breadcrumb')] = 'admin_newsletteremail_index';
            }
        }

        parent::breadcrumb($request, $items);
    }
}
