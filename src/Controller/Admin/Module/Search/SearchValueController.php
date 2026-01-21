<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Search;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Search\SearchValue;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * SearchValueController.
 *
 * Catalog management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_SEARCH_ENGINE')]
#[Route('/admin-%security_token%/{website}/search/values', schemes: '%protocol%')]
class SearchValueController extends AdminController
{
    protected ?string $class = SearchValue::class;

    /**
     * Delete SearchValue.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{searchvalue}', name: 'admin_searchvalue_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }
}
