<?php

declare(strict_types=1);

namespace App\Controller\Admin\Development;

use App\Controller\Admin\AdminController;
use App\Service\Development\DocumentationProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * DocumentationController.
 *
 * Standalone documentation portal (dashboard + pages) for the internal team.
 * Has its own dedicated layout and theming, separate from the admin chrome.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/development/documentation', schemes: '%protocol%')]
class DocumentationController extends AdminController
{
    /**
     * Documentation dashboard.
     */
    private const string FEATURED_SLUG = 'mise-en-production';

    #[Route('', name: 'admin_documentation', methods: 'GET')]
    public function dashboard(DocumentationProvider $documentation): Response
    {
        return $this->render('admin/page/documentation/dashboard.html.twig', [
            'pages' => $documentation->pages(),
            'featured' => $documentation->page(self::FEATURED_SLUG),
            'externalResources' => [
                [
                    'route' => 'app.swagger_ui',
                    'title' => 'API & tests (Swagger)',
                    'excerpt' => 'Lancer les suites de tests (mails, commandes) et explorer les endpoints internes.',
                    'icon' => 'icm-code',
                ],
            ],
        ]);
    }

    /**
     * Single documentation page.
     */
    #[Route('/{slug}', name: 'admin_documentation_page', methods: 'GET')]
    public function page(string $slug, DocumentationProvider $documentation): Response
    {
        $page = $documentation->page($slug);
        if (null === $page) {
            throw $this->createNotFoundException(sprintf('No documentation page for "%s".', $slug));
        }

        return $this->render('admin/page/documentation/page.html.twig', [
            'pages' => $documentation->pages(),
            'page' => $page,
        ]);
    }
}
