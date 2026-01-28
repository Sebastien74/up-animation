<?php

namespace App\Service\Development\Import;

use App\Entity\Core\Website;
use App\Entity\Layout;
use App\Entity\Module\Catalog\Listing;
use App\Entity\Module\Catalog\Product;
use App\Entity\Seo\Seo;
use App\Entity\Seo\Url;
use App\Service\Core\Urlizer;
use App\Service\Interface\CoreLocatorInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;

readonly class PagesUrlsCrawlerService
{
    private const string DEFAULT_LOCALE = 'fr';

    public function __construct(
        private CoreLocatorInterface $coreLocator,
        private MetaCrawlerService $metaCrawler,
    ) {

    }

    public function createPageIndex(string $url, array $contents, array $metas): void
    {
        $website = $this->getWebsite();
        $urlCode = $this->getUrlCode($url);
        $metas = $this->getMetas($metas, $url);

        $listingRepository = $this->coreLocator->em()->getRepository(Listing::class);
        $productRepository = $this->coreLocator->em()->getRepository(Product::class);
        $pageRepository = $this->coreLocator->em()->getRepository(Layout\Page::class);

        $listingAdminName = $metas['title'] ?? ucfirst(str_replace(['-'], ' ', $urlCode));
        $listing = $listingRepository->findOneBy(['slug' => $urlCode, 'website' => $website]);

        if (!$listing) {
            $position = count($listingRepository->findBy([ 'website' => $website])) + 1;
            $listing = new Listing();
            $listing->setWebsite($website);
            $listing->setPosition($position);
            $listing->setSlug($urlCode);
        }
        $listing->setAdminName($listingAdminName);

        if (!empty($contents['urls'])) {

            foreach ($listing->getProducts() as $product) {
                $listing->removeProduct($product);
            }

            foreach ($contents['urls'] as $productUrl) {
                $product = $productRepository->findByOldUrl($productUrl);
                if ($product) {
                    $listing->addProduct($product);
                }
            }
            $this->coreLocator->em()->persist($listing);
            $this->coreLocator->em()->flush();

            $animationsCodes = ['olympiades'];
            $parentSlug = str_contains($url, '/animations/') || str_contains($url, 'animation-') || in_array($urlCode, $animationsCodes) ? 'animations'
                : (str_contains($url, 'spectacle-') ? 'spectacle'
                    : (str_contains($url, '/category/') ? 'category' : null));
            $parentTitle = str_contains($url, '/animations/') || str_contains($url, 'animation-') || in_array($urlCode, $animationsCodes) ? 'Nos animations'
                : (str_contains($url, 'spectacle-') ? 'Nos spectacles'
                    : (str_contains($url, '/category/') ? 'Catégories' : null));
            $parentPage = $pageRepository->findOneBy(['slug' => $parentSlug, 'website' => $website, 'level' => 1]);
            if (!$parentPage) {

                $position = count($pageRepository->findBy(['website' => $website, 'level' => 1])) + 1;
                $parentPage = new Layout\Page();
                $parentPage->setWebsite($website);
                $parentPage->setPosition($position);
                $parentPage->setSlug($parentSlug);
                $parentPage->setLevel(1);

                $layout = new Layout\Layout();
                $layout->setWebsite($website);
                $layout->setAdminName(ucfirst($parentSlug));
                $parentPage->setLayout($layout);
            }

            $parentPage->setAdminName(ucfirst($parentTitle));

            if ($parentPage->getUrls()->isEmpty()) {

                $urlEntity = new Url();
                $urlEntity->setWebsite($website);
                $urlEntity->setLocale(self::DEFAULT_LOCALE);
                $urlEntity->setCode($urlCode);
                $urlEntity->setOnline(true);

                $seo = new Seo();

                $urlEntity->setSeo($seo);

                $parentPage->addUrl($urlEntity);
            }

            $parentPage->setNoSeo(false);

            $this->coreLocator->em()->persist($parentPage);
            $this->coreLocator->em()->flush();

            $page = $pageRepository->findOneBy(['slug' => $urlCode, 'website' => $website]);

            if (!$page) {

                $position = count($pageRepository->findBy(['website' => $website, 'parent' => $parentPage, 'level' => 2])) + 1;
                $page = new Layout\Page();
                $page->setWebsite($website);
                $page->setPosition($position);
                $page->setSlug($urlCode);
                $page->setParent($parentPage);
                $page->setLevel(2);
                $this->coreLocator->em()->persist($page);

                $layout = new Layout\Layout();
                $layout->setWebsite($website);
                $layout->setAdminName(ucfirst($parentSlug));
                $page->setLayout($layout);
            }

            $layout = $page->getLayout();

            if ($layout->getZones()->isEmpty()) {

                $titleZone = !empty($metas['title']) ? $metas['title'] : (!empty($metas['meta-title']) ? $metas['meta-title'] : ucfirst(str_replace(['-'], ' ', $urlCode)));
                $zone = $this->addZone($layout, 1, true);
                $col = $this->addCol($zone);
                $this->addHeader($col, $titleZone, $website);

                $zone = $this->addZone($layout, 2);
                $col = $this->addCol($zone);
                $this->addBlock($col, 'core-action', 'catalog-index', $listing->getId());

                $this->coreLocator->em()->persist($layout);
            }

            if ($page->getUrls()->isEmpty()) {

                $urlEntity = new Url();
                $urlEntity->setWebsite($website);
                $urlEntity->setLocale(self::DEFAULT_LOCALE);
                $urlEntity->setCode($urlCode);
                $urlEntity->setOnline(true);

                $seo = new Seo();

                $urlEntity->setSeo($seo);

                $page->addUrl($urlEntity);
            }

            $urlEntity = $page->getUrls()->first();
            $urlEntity->setOldUrl($url);

            $seo = $urlEntity->getSeo();
            if (!empty($metas['meta-title'])) {
                $seo->setMetaTitle($metas['meta-title']);
            } else {
                $logger = new Logger('no-seo');
                $logger->pushHandler(new RotatingFileHandler($this->coreLocator->logDir().'/no-seo.log', 20, Level::Info));
                $logger->info(trim($url));
            }
            if (!empty($metas['meta-description'])) {
                $seo->setMetaDescription($metas['meta-description']);
            }
            $this->coreLocator->em()->persist($seo);

            $page->setAdminName(ucfirst($listingAdminName));
            $page->setNoSeo(empty($metas['meta-title']));

            $this->coreLocator->em()->persist($page);
            $this->coreLocator->em()->flush();
        }
    }

    public function createPage(string $url, array $contents, array $metas): void
    {
        $website = $this->getWebsite();
        $urlCode = $this->getUrlCode($url);
        $metas = $this->getMetas($metas, $url);

        $pageRepository = $this->coreLocator->em()->getRepository(Layout\Page::class);
        $page = $pageRepository->findOneBy(['slug' => $urlCode, 'website' => $website]);
        $titleZone = !empty($metas['title']) ? $metas['title'] : (!empty($metas['meta-title']) ? $metas['meta-title'] : ucfirst(str_replace(['-'], ' ', $urlCode)));

        if (!$page) {

            $position = count($pageRepository->findBy(['website' => $website, 'level' => 1])) + 1;
            $page = new Layout\Page();
            $page->setWebsite($website);
            $page->setPosition($position);
            $page->setSlug($urlCode);
            $this->coreLocator->em()->persist($page);

            $layout = new Layout\Layout();
            $layout->setWebsite($website);
            $layout->setAdminName(ucfirst($titleZone));
            $page->setLayout($layout);
        }

        $page->setLevel(1);

        $layout = $page->getLayout();

        if ($layout->getZones()->isEmpty()) {
            $zone = $this->addZone($layout, 1, true);
            $col = $this->addCol($zone);
            $this->addHeader($col, $titleZone, $website);
            $this->coreLocator->em()->persist($layout);
        }

        if ($page->getUrls()->isEmpty()) {

            $urlEntity = new Url();
            $urlEntity->setWebsite($website);
            $urlEntity->setLocale(self::DEFAULT_LOCALE);
            $urlEntity->setCode($urlCode);
            $urlEntity->setOnline(true);

            $seo = new Seo();

            $urlEntity->setSeo($seo);

            $page->addUrl($urlEntity);
        }

        $urlEntity = $page->getUrls()->first();
        $urlEntity->setOldUrl($url);

        $seo = $urlEntity->getSeo();
        if (!empty($metas['meta-title'])) {
            $seo->setMetaTitle($metas['meta-title']);
        } else {
            $logger = new Logger('no-seo');
            $logger->pushHandler(new RotatingFileHandler($this->coreLocator->logDir().'/no-seo.log', 20, Level::Info));
            $logger->info(trim($url));
        }
        if (!empty($metas['meta-description'])) {
            $seo->setMetaDescription($metas['meta-description']);
        }
        $this->coreLocator->em()->persist($seo);

        $page->setAdminName(ucfirst($titleZone));
        $page->setNoSeo(empty($metas['meta-title']));

        $this->coreLocator->em()->persist($page);
        $this->coreLocator->em()->flush();
    }

    /**
     * Get the first Website id from database (id ASC).
     */
    private function getWebsite(): Website
    {
        $websiteId = $this->getFirstWebsiteId();
        return $this->getWebsiteRef($websiteId);
    }

    /**
     * Get url code.
     */
    private function getUrlCode(string $url): string
    {
        $urlCode = str_replace(['https://up-animations.fr', 'http://up-animations.fr'], '', $url);
        return Urlizer::urlize(trim($urlCode,'/'));
    }

    /**
     * Get metas.
     */
    private function getMetas(array $metas, string $url): array
    {
        if (empty($metas['meta-title'])) {
            $metas = $this->metaCrawler->crawlUrls([$url], 60, 'SymfonyMetaCrawler/1.0');
            $metas = !empty($metas[$url]) ? $metas[$url] : [];
        }
        return $metas;
    }

    /**
     * Get the first Website id from database (id ASC).
     */
    private function getFirstWebsiteId(): int
    {
        $qb = $this->coreLocator->em()->createQueryBuilder();
        $qb->select('w.id')
            ->from(Website::class, 'w')
            ->orderBy('w.id', 'ASC')
            ->setMaxResults(1);
        $row = $qb->getQuery()->getOneOrNullResult();
        $websiteId = is_array($row) && isset($row['id']) ? (int) $row['id'] : 0;

        if ($websiteId <= 0) {
            throw new \RuntimeException('No Website found in database.');
        }

        return $websiteId;
    }

    /**
     * Get a managed Website reference.
     */
    private function getWebsiteRef(int $websiteId): Website
    {
        /** @var Website $websiteRef */
        $websiteRef = $this->coreLocator->em()->getReference(Website::class, $websiteId);

        return $websiteRef;
    }

    /**
     * Add Zone.
     */
    public function addZone(Layout\Layout $layout, int $position, bool $fullSize = false, bool $noPadding = false): Layout\Zone
    {
        $zone = new Layout\Zone();
        $zone->setFullSize($fullSize);
        $zone->setPosition($position);

        if ($noPadding) {
            $zone->setPaddingTop('pt-0');
            $zone->setPaddingBottom('pb-0');
        }

        if (!empty($this->user)) {
            $zone->setCreatedBy($this->user);
        }

        $layout->addZone($zone);

        return $zone;
    }

    /**
     * Add Col.
     */
    public function addCol(Layout\Zone $zone, int $position = 1, int $size = 12): Layout\Col
    {
        $col = new Layout\Col();
        $col->setPosition($position);
        $col->setSize($size);
        $zone->addCol($col);

        if (!empty($this->user)) {
            $col->setCreatedBy($this->user);
        }

        return $col;
    }

    /**
     * Add header.
     */
    private function addHeader(Layout\Col $col, string $adminName, Website $website): void
    {
        $col->setPaddingLeft('ps-0');
        $col->setPaddingRight('pe-0');

        $intl = new Layout\BlockIntl();
        $intl->setTitle($adminName);
        $intl->setLocale(self::DEFAULT_LOCALE);
        $intl->setTitleForce(1);
        $intl->setWebsite($website);

        $zone = $col->getZone();
        $zone->setPaddingTop('pt-0');
        $zone->setPaddingBottom('pb-0');

        if (!empty($this->user)) {
            $intl->setCreatedBy($this->user);
        }

        $block = $this->addBlock($col, 'title-header');
        $block->addIntl($intl);
        $block->setPaddingLeft('ps-0');
        $block->setPaddingRight('pe-0');
    }

    /**
     * Add Block.
     */
    public function addBlock(
        Layout\Col $col,
        ?string $blockTypeSlug = null,
        ?string $actionSlug = null,
        ?int $actionFilter = null,
        int $position = 1,
        int $size = 12,
        bool $maxTablet = false,
    ): Layout\Block {

        $block = new Layout\Block();
        $block->setPosition($position);
        $block->setSize($size);

        if (!empty($this->user)) {
            $block->setCreatedBy($this->user);
        }

        if ('form-submit' === $blockTypeSlug) {
            $block->setColor('btn-primary');
        }

        if ($maxTablet) {
            $block->setTabletSize($size);
            $block->setMiniPcSize($size);
        }

        $col->addBlock($block);

        $this->addAction($block, $blockTypeSlug, $actionSlug, $actionFilter);

        return $block;
    }

    /**
     * Add Action.
     */
    private function addAction(Layout\Block $block, ?string $blockTypeSlug = null, ?string $actionSlug = null, ?int $actionFilter = null): void
    {
        if ($blockTypeSlug) {
            $blockType = $this->coreLocator->em()->getRepository(Layout\BlockType::class)->findOneBy(['slug' => $blockTypeSlug]);
            $block->setBlockType($blockType);
        }

        if ($actionSlug) {
            $action = $this->coreLocator->em()->getRepository(Layout\Action::class)->findOneBy(['slug' => $actionSlug]);
            $block->setAction($action);
        }

        if ($actionFilter) {
            $actionIntl = new Layout\ActionIntl();
            $actionIntl->setLocale(self::DEFAULT_LOCALE);
            $actionIntl->setBlock($block);
            $actionIntl->setActionFilter($actionFilter);
            $block->addActionIntl($actionIntl);
        }
    }
}