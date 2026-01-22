<?php

declare(strict_types=1);

namespace App\Controller\Admin\Development;

use App\Controller\Admin\AdminController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Yaml\Yaml;

/**
 * ImportController.
 *
 * Import management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/development/imports', schemes: '%protocol%')]
class ImportController extends AdminController
{
    /**
     * Index.
     */
    #[Route('/index', name: 'admin_dev_import', methods: 'GET')]
    public function importIndex(Request $request): Response
    {
        parent::breadcrumb($request, []);

        return $this->render('admin/page/development/imports.html.twig', array_merge($this->arguments, [
            'website' => $this->getWebsite(),
        ]));
    }
}
