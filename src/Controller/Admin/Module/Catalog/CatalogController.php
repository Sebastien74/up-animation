<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Catalog;

use App\Controller\Admin\AdminController;
use App\Entity\Layout\Layout;
use App\Entity\Layout\Page;
use App\Entity\Module\Catalog\Catalog;
use App\Entity\Module\Catalog\Product;
use App\Entity\Module\Catalog\ProductIntl;
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
use Symfony\Component\HttpFoundation\Request;
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
    public function index(Request $request, PaginatorInterface $paginator)
    {
        $path = $this->coreLocator->formatDirname($this->coreLocator->projectDir().'/var/crawler/contents.json');
        $raw = @file_get_contents($path);
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        $products = $decoded['products'];
        $indexes = $decoded['indexes'];
        $categories = $decoded['categories'];
        $pages = $decoded['pages'];

        $path = $this->coreLocator->formatDirname($this->coreLocator->projectDir().'/var/crawler/metas.json');
        $raw = @file_get_contents($path);
        $metas = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        $animationsPage = $this->coreLocator->em()->getRepository(Page::class)->findOneBy(['slug' => 'animations', 'website' => $this->coreLocator->website()->entity, 'level' => 1]);
//        foreach ($animationsPage->getPages() as $page) {
//            foreach ($page->getUrls() as $url) {
//                $url->setOnline(false);
//                $this->coreLocator->em()->persist($url);
//            }
//        }
//        $this->coreLocator->em()->flush();
//        die;

        // Passer les URLS offline Page produits....

        $noExisting = [];
        foreach ($pages as $indexUrl => $data) {

            $indexUrl = str_replace('http://', 'https://', $indexUrl);
            $existingUrl = $this->coreLocator->em()->getRepository(Url::class)->findOneBy(['oldUrl' => $indexUrl]);


//            if ($existingUrl) {
//                $seo = $existingUrl->getSeo();
//                if (!$seo->getMetaTitle() || !$seo->getMetaDescription()) {
//                    $metas = !empty($metas[$indexUrl]) ? $metas[$indexUrl] : [];
//                    if (empty($metas)) {
//                        $metas = $this->metaCrawler->crawlUrls([$indexUrl], 60, 'SymfonyMetaCrawler/1.0');
//                        $metas = !empty($metas[$indexUrl]) ? $metas[$indexUrl] : [];
//                    }
////                    dd($metas);
//                    $seo->setMetaTitle($metas['meta-title']);
//                    $seo->setMetaDescription($metas['meta-description']);
//                    $this->coreLocator->em()->persist($seo);
//                    $this->coreLocator->em()->flush();
//                }
//            }
//
//            if (!empty($data['urls'])) {
//                foreach ($data['urls'] as $oldUrl) {
//                    $oldUrl = str_replace('http://', 'https://', $oldUrl);
//                    $existingUrl = $this->coreLocator->em()->getRepository(Url::class)->findOneBy(['oldUrl' => $oldUrl]);
//                    if ($existingUrl) {
//                        $seo = $existingUrl->getSeo();
//                        if (!$seo->getMetaTitle() || !$seo->getMetaDescription()) {
//                            $metas = !empty($metas[$indexUrl]) ? $metas[$indexUrl] : [];
//                            if (empty($metas)) {
//                                $metas = $this->metaCrawler->crawlUrls([$indexUrl], 60, 'SymfonyMetaCrawler/1.0');
//                                $metas = !empty($metas[$indexUrl]) ? $metas[$indexUrl] : [];
//                            }
////                            dd($metas);
//                            $seo->setMetaTitle($metas['meta-title']);
//                            $seo->setMetaDescription($metas['meta-description']);
//                            $this->coreLocator->em()->persist($seo);
//                            $this->coreLocator->em()->flush();
//                        }
//                    }
//                }
//            }


//            if (!$existingUrl && $indexUrl !== 'https://up-animations.fr/') {
//
//                $metas = !empty($metas[$indexUrl]) ? $metas[$indexUrl] : [];
//
//                if (empty($metas)) {
//                    $metas = $this->metaCrawler->crawlUrls([$indexUrl], 60, 'SymfonyMetaCrawler/1.0');
//                    $metas = !empty($metas[$indexUrl]) ? $metas[$indexUrl] : [];
//                }
//
//                $url = $this->setUrl($indexUrl, $metas);
////
//                if (str_contains($indexUrl, '/animations/') || str_contains($indexUrl, '/animations-') || str_contains($indexUrl, '/animation-') || str_contains($indexUrl, '/category/actus')) {
////
////                    dd($url);
//
////                    $position = count($this->coreLocator->em()->getRepository(Page::class)->findBy(['parent' => $animationsPage])) + 1;
////                    $page = new Page();
////                    $page->setWebsite($this->coreLocator->website()->entity);
////                    $page->setAdminName($metas['title']);
////                    $page->setPosition($position);
////                    $page->setSlug($url->getCode());
////                    $page->setParent($animationsPage);
////                    $page->setLevel(2);
////                    $page->addUrl($url);
////                    $this->coreLocator->em()->persist($page);
////
////                    $layout = new Layout();
////                    $layout->setWebsite($this->coreLocator->website()->entity);
////                    $layout->setAdminName(ucfirst($animationsPage->getSlug()));
////                    $page->setLayout($layout);
////
////                    $zone = $this->pagesUrlsCrawlerService->addZone($layout, 1, true);
////                    $col = $this->pagesUrlsCrawlerService->addCol($zone);
////                    $this->pagesUrlsCrawlerService->addHeader($col, $metas['title'], $this->coreLocator->website()->entity);
////                    $this->coreLocator->em()->persist($layout);
////
////                    $this->coreLocator->em()->flush();
////
//                } else {
////
////                    $catalog = $this->coreLocator->em()->getRepository(Catalog::class)->findOneBy(['slug' => 'animation']);
////
////                    $product = new Product();
////                    $product->setWebsite($this->coreLocator->website()->entity);
////                    $product->setSlug($url->getCode());
////                    $product->setAdminName($metas['title']);
////                    $product->setNoSeo(empty($metas['meta-title']));
////                    $product->addUrl($url);
////                    $product->setPosition(count($catalog->getProducts()) + 1);
////
////                    $intl = new ProductIntl();
////                    $intl->setLocale($url->getLocale());
////                    $intl->setTitle($metas['title']);
////                    $intl->setWebsite($this->coreLocator->website()->entity);
////                    $product->addIntl($intl);
////
////                    $catalog->addProduct($product);
////
////                    $this->coreLocator->em()->persist($product);
////                    $this->coreLocator->em()->flush();
//
//////                    dd($product);
//                }
//                $noExisting[] = $indexUrl;
//            }
//            if (!empty($data['urls'])) {
//                foreach ($data['urls'] as $oldUrl) {
//                    $oldUrl = str_replace('http://', 'https://', $oldUrl);
//                    $existingUrl = $this->coreLocator->em()->getRepository(Url::class)->findOneBy(['oldUrl' => $oldUrl]);
//                    if (!$existingUrl && $oldUrl !== 'https://up-animations.fr/') {
////                        if (str_contains($oldUrl, '/animations/') || str_contains($oldUrl, '/animations-') || str_contains($oldUrl, '/animation-')) {
////                            dd($oldUrl);
////                        } else {
////                            dd($oldUrl);
////                        }
//                        $noExisting[] = $oldUrl;
//                    }
//                }
//            }
        }

//        dd($noExisting);

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
