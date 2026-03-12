<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Catalog;

use App\Controller\Admin\AdminController;
use App\Entity\Layout\Page;
use App\Entity\Module\Catalog\Catalog;
use App\Entity\Seo\Seo;
use App\Entity\Seo\Url;
use App\Form\Interface\ModuleFormManagerInterface;
use App\Form\Type\Module\Catalog\CatalogType;
use App\Service\Development\Import\MetaCrawlerService;
use App\Service\Development\Import\PagesUrlsCrawlerService;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use DateMalformedStringException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CatalogController.
 *
 * Catalog management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_CATALOG')]
#[Route('/admin-%security_token%/{website}/module/catalogs/catalogs', schemes: '%protocol%')]
class CatalogController extends AdminController
{
    protected ?string $class = Catalog::class;
    protected ?string $formType = CatalogType::class;

    /**
     * CatalogController constructor.
     */
    public function __construct(
        protected ModuleFormManagerInterface $moduleFormInterface,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
        protected PagesUrlsCrawlerService $pagesUrlsCrawlerService,
        protected MetaCrawlerService $metaCrawler,
    ) {
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index Catalog.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_catalog_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator, ?string $domains = null): JsonResponse|string|Response
    {
//        $path = $this->coreLocator->formatDirname($this->coreLocator->projectDir().'/var/crawler/contents.json');
//        if (file_exists($path)) {
//            $raw = @file_get_contents($path);
//            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
//            $products = $decoded['products'];
//            $indexes = $decoded['indexes'];
//            $categories = $decoded['categories'];
//            $pages = $decoded['pages'];
//
//            $path = $this->coreLocator->formatDirname($this->coreLocator->projectDir().'/var/crawler/metas.json');
//            $raw = @file_get_contents($path);
//            $metas = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
//
//            $animationsPage = $this->coreLocator->em()->getRepository(Page::class)->findOneBy(['slug' => 'animations', 'website' => $this->coreLocator->website()->entity, 'level' => 1]);
//
//            // Passer les URLS offline Page produits....
//
//            $noExisting = [];
//            foreach ($pages as $indexUrl => $data) {
//
//                $indexUrl = str_replace('http://', 'https://', $indexUrl);
//                $existingUrl = $this->coreLocator->em()->getRepository(Url::class)->findOneBy(['oldUrl' => $indexUrl]);
//            }
//        }

        return parent::index($request, $paginator);
    }

    /**
     * @throws DateMalformedStringException
     */
    private function setUrl(string $oldUrl, array $metas): Url
    {
        $matches = explode('/', $oldUrl);
        $urlCode = end($matches);
        if (str_contains($oldUrl, '/category/actus')) {
            $urlCode = 'category-actus';
        }

        $url = new Url();
        $url->setOldUrl($oldUrl);
        $url->setCode($urlCode);
        $url->setLocale($this->coreLocator->locale());
        $url->setWebsite($this->coreLocator->website()->entity);
        $url->setOnline(false);
        $url->setCreatedBy($this->coreLocator->user());
        $url->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));

        $seo = new Seo();
        $seo->setMetaTitle($metas['meta-title']);
        $seo->setMetaDescription($metas['meta-description']);
        $seo->setUrl($url);

        return $url;
    }

    /**
     * New Catalog.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_catalog_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Catalog.
     *
     * {@inheritdoc}
     */
    #[Route('/layout/{catalog}', name: 'admin_catalog_layout', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Catalog.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{catalog}', name: 'admin_catalog_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Catalog.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{catalog}', name: 'admin_catalog_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Catalog.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{catalog}', name: 'admin_catalog_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('catalog')) {
            $items[$this->coreLocator->translator()->trans('Catalogues', [], 'admin_breadcrumb')] = 'admin_catalog_index';
        }

        parent::breadcrumb($request, $items);
    }
}
