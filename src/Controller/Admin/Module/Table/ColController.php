<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Table;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Website;
use App\Entity\Module\Table\Col;
use App\Entity\Module\Table\Table;
use App\Form\Manager\Module\TableManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ColController.
 *
 * Table Col Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_TABLE')]
#[Route('/admin-%security_token%/{website}/tables/cols', schemes: '%protocol%')]
class ColController extends AdminController
{
    protected ?string $class = Col::class;

    /**
     * Add Col.
     */
    #[Route('/{table}/add', name: 'admin_tablecol_add', methods: 'GET')]
    public function add(Request $request, TableManager $manager, Website $website, Table $table): RedirectResponse
    {
        $manager->addCol($table, $website);
        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Position Col.
     */
    #[Route('/{table}/position/{col}/{type}', name: 'admin_tablecol_position', methods: 'GET|POST')]
    public function colPosition(Request $request, TableManager $manager, Table $table, Col $col, string $type): RedirectResponse
    {
        $manager->colPosition($table, $col, $type);
        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Delete Col.
     */
    #[Route('/{table}/delete/{col}', name: 'admin_tablecol_delete', methods: 'DELETE')]
    public function deleteCol(Request $request, TableManager $manager, Table $table, Col $col): JsonResponse
    {
        $manager->deleteCol($table, $col);
        return new JsonResponse(['success' => true, 'reload' => true]);
    }
}