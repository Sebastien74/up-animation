<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Table;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Website;
use App\Entity\Module\Table\Cell;
use App\Entity\Module\Table\Table;
use App\Form\Manager\Module\TableManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CellController.
 *
 * Table Cell Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_TABLE')]
#[Route('/admin-%security_token%/{website}/tables/cells', schemes: '%protocol%')]
class CellController extends AdminController
{
    protected ?string $class = Cell::class;

    /**
     * Add Cell.
     */
    #[Route('/{table}/add', name: 'admin_tablecell_add', methods: 'GET')]
    public function addRow(Request $request, TableManager $manager, Website $website, Table $table): RedirectResponse
    {
        $manager->addRow($table, $website);
        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Position Cell.
     */
    #[Route('/position/{table}/{position}/{type}', name: 'admin_tablecell_position', methods: 'GET|POST')]
    public function rowPosition(Request $request, TableManager $manager, Table $table, int $position, string $type): RedirectResponse
    {
        $manager->rowPosition($table, $position, $type);
        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Delete Cell.
     */
    #[Route('/delete/{table}/{position}', name: 'admin_tablecell_delete', methods: 'DELETE|GET')]
    public function deleteRow(Table $table, TableManager $manager, int $position): JsonResponse
    {
        $manager->deleteRow($table, $position);
        return new JsonResponse(['success' => true, 'reload' => true]);
    }
}
