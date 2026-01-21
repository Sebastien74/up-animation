<?php

declare(strict_types=1);

namespace App\Controller\Admin\Development;

use App\Controller\Admin\AdminController;
use App\Entity\Layout\Page;
use App\Entity\Module\Catalog;
use App\Entity\Module\Faq\Faq;
use App\Entity\Module\Form\Form;
use App\Entity\Module\Menu\Menu;
use App\Entity\Module\Newscast;
use App\Entity\Module\Slider\Slider;
use App\Service\Development\Cms;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
    #[Route('/index', name: 'admin_dev_import_index', methods: 'GET')]
    public function importIndex(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $entities = [
            'cms6' => [
                'newscasts-teaser' => [
                    'title' => $this->coreLocator->translator()->trans("Teasers d'actualités", [], 'admin'),
                    'classname' => urlencode(Newscast\Teaser::class),
                    'tables' => urlencode(json_encode(['module_newscast_teaser'])),
                ],
                'newscasts-index' => [
                    'title' => $this->coreLocator->translator()->trans("Index d'actualités", [], 'admin'),
                    'classname' => urlencode(Newscast\Listing::class),
                    'tables' => urlencode(json_encode(['module_newscast_listing'])),
                ],
                'catalog-teaser' => [
                    'title' => $this->coreLocator->translator()->trans('Teasers de produits', [], 'admin'),
                    'classname' => urlencode(Catalog\Teaser::class),
                    'tables' => urlencode(json_encode(['module_catalog_product_teaser'])),
                ],
                'catalog-index' => [
                    'title' => $this->coreLocator->translator()->trans('Index de produits', [], 'admin'),
                    'classname' => urlencode(Catalog\Listing::class),
                    'tables' => urlencode(json_encode(['module_catalog_listing'])),
                ],
                'pages' => [
                    'title' => $this->coreLocator->translator()->trans('Pages', [], 'admin'),
                    'classname' => urlencode(Page::class),
                    'tables' => urlencode(json_encode(['layout_page', 'content_page'])),
                ],
                'menus' => [
                    'title' => $this->coreLocator->translator()->trans('Menus', [], 'admin'),
                    'classname' => urlencode(Menu::class),
                    'tables' => urlencode(json_encode(['module_menu'])),
                ],
                'newscasts' => [
                    'title' => $this->coreLocator->translator()->trans('Actualités', [], 'admin'),
                    'classname' => urlencode(Newscast\Newscast::class),
                    'tables' => urlencode(json_encode(['module_newscast'])),
                ],
                'products' => [
                    'title' => $this->coreLocator->translator()->trans('Produits', [], 'admin'),
                    'classname' => urlencode(Catalog\Product::class),
                    'tables' => urlencode(json_encode(['module_catalog_product'])),
                ],
                'sliders' => [
                    'title' => $this->coreLocator->translator()->trans('Carrousels', [], 'admin'),
                    'classname' => urlencode(Slider::class),
                    'tables' => urlencode(json_encode(['module_slider'])),
                ],
                'forms' => [
                    'title' => $this->coreLocator->translator()->trans('Formulaires', [], 'admin'),
                    'classname' => urlencode(Form::class),
                    'tables' => urlencode(json_encode(['module_form'])),
                ],
                'faqs' => [
                    'title' => $this->coreLocator->translator()->trans('FAQ', [], 'admin'),
                    'classname' => urlencode(Faq::class),
                    'tables' => urlencode(json_encode(['module_faq'])),
                ],
            ],
        ];

        parent::breadcrumb($request, []);

        return $this->render('admin/page/development/imports-index.html.twig', array_merge($this->arguments, [
            'website' => $this->getWebsite(),
            'entities' => $entities,
        ]));
    }

    /**
     * Entities list to import.
     */
    #[Route('/entities', name: 'admin_dev_import_entities', methods: 'GET')]
    public function entities(Request $request, Cms\PageImportV4Interface $importV4, Cms\EntityImportV6Interface $importV6): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $website = $this->getWebsite();
        $version = $request->get('version');
        $entities = 4 == $version ? $importV4->entities($website->entity) : $importV6->entities($website->entity);

        return new \Symfony\Component\HttpFoundation\JsonResponse(['html' => $this->renderView('admin/page/development/imports-data.html.twig', [
            'entities' => $entities,
            'website' => $website,
            'route' => 'admin_dev_import_entity',
            'version' => $request->get('version'),
            'classname' => $request->get('classname'),
            'tables' => $request->get('tables'),
            'property_name' => 4 == $version ? 'page_name' : 'adminName',
        ])]);
    }

    /**
     * Entity import.
     */
    #[Route('/entity/{id}', name: 'admin_dev_import_entity', methods: 'GET')]
    public function entity(Request $request, Cms\PageImportV4Interface $importV4, Cms\EntityImportV6Interface $importV6, int $id): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $version = $request->get('version');
        $service = 4 == $version ? $importV4 : (6 == $version ? $importV6 : null);
        $service->execute($this->getWebsite()->entity, $id);

        return new \Symfony\Component\HttpFoundation\JsonResponse(['success' => true]);
    }

    /**
     * Newscasts list to import.
     */
    #[Route('/newscasts', name: 'admin_dev_import_newscasts', methods: 'GET')]
    public function newscasts(Request $request, Cms\NewscastsImportV4Interface $importV4): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $website = $this->getWebsite();
        $version = $request->get('version');

        return new \Symfony\Component\HttpFoundation\JsonResponse(['html' => $this->renderView('admin/page/development/imports-data.html.twig', [
            'entities' => 4 == $version ? $importV4->entities($website->entity) : null,
            'website' => $website,
            'route' => 'admin_dev_import_newscast',
            'version' => $request->get('version'),
            'property_name' => 4 == $version ? 'news_name' : 'adminName',
        ])]);
    }

    /**
     * Newscast import.
     */
    #[Route('/newscast/{id}', name: 'admin_dev_import_newscast', methods: 'GET')]
    public function newscast(Request $request, Cms\NewscastsImportV4Interface $importV4, int $id): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $version = $request->get('version');
        if (4 == $version) {
            $importV4->execute($this->getWebsite()->entity, $id);
        } else {
        }

        return new \Symfony\Component\HttpFoundation\JsonResponse(['success' => true]);
    }
}
