<?php

declare(strict_types=1);

namespace App\Controller\Admin\Core;

use App\Controller\Admin\AdminController;
use App\Entity\Core\MailLog;
use App\Repository\Core\MailLogRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * MailLogController.
 *
 * refonte-admin views to browse sent emails (index + show).
 * Read-only listing of the audit trail produced by MailerService.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%', schemes: '%protocol%')]
final class MailLogController extends AdminController
{
    private const int PAGE_SIZE = 20;

    #[Route('/mail-log/index/{website}', name: 'admin_maillog_index', defaults: ['website' => null], methods: 'GET')]
    public function list(
        Request $request,
        PaginatorInterface $paginator,
        MailLogRepository $repository,
    ): Response {
        $status = $request->query->get('status');
        $status = in_array($status, [MailLog::STATUS_SUCCESS, MailLog::STATUS_FAILED], true) ? $status : null;

        $pagination = $paginator->paginate(
            $repository->indexQueryBuilder($status),
            $request->query->getInt('page', 1),
            self::PAGE_SIZE,
            ['wrap-queries' => true]
        );

        $this->breadcrumb($request, ['Mails envoyés' => 'admin_maillog_index']);

        return $this->adminRender('admin/page/core/mail-log/index.html.twig', array_merge($this->arguments, [
            'pageTitle' => $this->coreLocator->translator()->trans('Mails envoyés', [], 'admin'),
            'pagination' => $pagination,
            'currentStatus' => $status,
            'counts' => $repository->countByStatus(),
        ]));
    }

    #[Route('/mail-log/show/{mailLog}/{website}', name: 'admin_maillog_show', defaults: ['website' => null], methods: 'GET')]
    public function detail(
        Request $request,
        MailLogRepository $repository,
        int $mailLog,
    ): Response {
        $entity = $repository->find($mailLog);
        if (!$entity instanceof MailLog) {
            throw $this->createNotFoundException(sprintf('Aucun mail pour l\'ID %d', $mailLog));
        }

        $label = $entity->getSubject() ?: '#'.$entity->getId();
        $this->breadcrumb($request, [
            'Mails envoyés' => 'admin_maillog_index',
            $label => 'admin_maillog_show',
        ]);

        return $this->adminRender('admin/page/core/mail-log/show.html.twig', array_merge($this->arguments, [
            'pageTitle' => $entity->getSubject() ?: 'Mail #'.$entity->getId(),
            'entity' => $entity,
        ]));
    }
}