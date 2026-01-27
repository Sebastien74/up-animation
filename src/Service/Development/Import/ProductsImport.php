<?php

declare(strict_types=1);

namespace App\Service\Development\Import;

use App\Entity\Core\Website;
use App\Entity\Layout\Layout;
use App\Entity\Module\Catalog\Catalog;
use App\Entity\Module\Catalog\Product;
use App\Entity\Module\Catalog\ProductIntl;
use App\Entity\Seo\Seo;
use App\Entity\Seo\Url;
use App\Service\Core\Urlizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * ProductsImport.
 *
 * Create 2 catalogs (animation, location) if missing (slug-based), then upsert products.
 *
 * Rules:
 * - Catalogs are created only if they do not exist (checked by slug + website).
 * - Catalog adminName is set and position is set only on creation.
 * - For each product URL in contents.json:
 *   - If last path segment starts with "location-" => catalog "location", else "animation"
 *   - Product is upserted by slug + website
 *   - Product adminName is set
 *   - Position is set only when the product is newly created (per catalog, append after current max)
 *   - If product has no Url yet:
 *       - Create Url (locale=fr, code=path)
 *       - Create Seo and attach it to Url
 *       - Add Url to Product
 *
 * Notes:
 * - We do not keep managed entities as class properties across flush/clear.
 * - After EntityManager::clear(), all entities are detached => rebuild references (Website + Catalogs).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AutoconfigureTag('app.contents_importer')]
class ProductsImport implements ContentsImporterInterface
{
    private const string CATALOG_ANIMATION = 'animation';
    private const string CATALOG_LOCATION  = 'location';

    private const string DEFAULT_LOCALE = 'fr';

    private const array CATALOGS = [
        self::CATALOG_ANIMATION => ['adminName' => 'Animation', 'position' => 1],
        self::CATALOG_LOCATION  => ['adminName' => 'Location',  'position' => 2],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $bucket): bool
    {
        return $bucket === 'products';
    }

    /**
     * @param array<string, mixed> $bucketPayload map(url => payload)
     */
    public function import(string $bucket, array $bucketPayload, array $metas, SymfonyStyle $io, ProgressBar $progressBar, bool $dryRun = false): void
    {
        $websiteId = $this->getFirstWebsiteId();
        $websiteRef = $this->getWebsiteRef($websiteId);

        // 1) Ensure catalogs exist and are managed
        $catalogs = $this->ensureCatalogs($websiteRef, $dryRun);

        // 2) Prepare "next position" for new products per catalog
        $nextPosByCatalogSlug = $this->computeNextPositions($catalogs, $websiteRef);

        $batchSize = 50;
        $i = 0;

        $created = 0;
        $updated = 0;
        $urlsCreated = 0;

        foreach ($bucketPayload as $url => $payload) {

            $progressBar->setMessage('Importing products');

            if (!is_string($url) || trim($url) === '') {
                $progressBar->advance();
                continue;
            }

            $url = trim($url);

            $catalogSlug = $this->resolveCatalogSlugFromUrl($url);
            $catalog = $catalogs[$catalogSlug] ?? null;

            if (!$catalog instanceof Catalog) {
                $progressBar->advance();
                continue;
            }

            $productSlug = $this->slugFromUrl($url);
            if ($productSlug === '') {
                $progressBar->advance();
                continue;
            }

            $adminName = $this->adminNameFromSlug($productSlug);

            /** @var Product|null $product */
            $product = $this->entityManager->getRepository(Product::class)->findOneBy([
                'slug' => $productSlug,
                'website' => $websiteRef,
            ]);

            $isNew = false;

            if (!$product) {
                $product = new Product();
                $product->setWebsite($websiteRef);
                // Position only if new
                $product->setPosition($nextPosByCatalogSlug[$catalogSlug] ?? 1);
                $nextPosByCatalogSlug[$catalogSlug] = ($nextPosByCatalogSlug[$catalogSlug] ?? 1) + 1;
                $isNew = true;
            }

            // Upsert fields
            $product->setSlug($productSlug);
            $product->setAdminName($adminName);
            $product->setNoSeo(empty($metas['meta-title']));

            $intl = $product->getIntls()->isEmpty() ? new ProductIntl() : $product->getIntls()->first();
            if ($product->getIntls()->isEmpty()) {
                $product->addIntl($intl);
            }
            $intl->setLocale(self::DEFAULT_LOCALE);
            $intl->setTitle($adminName);
            $intl->setWebsite($websiteRef);

            // IMPORTANT: ensure association is set on both sides
            $catalog->addProduct($product);

            $stringUrl = $url;

            // Create Url+Seo only if product has none
            if ($product->getUrls()->isEmpty()) {

                $code = $this->codeFromUrl($url);

                $urlEntity = new Url();
                $urlEntity->setWebsite($websiteRef);
                $urlEntity->setLocale(self::DEFAULT_LOCALE);
                $urlEntity->setCode($code);
                $urlEntity->setOnline(true);

                $seo = new Seo();

                // Owning side is Url::seo
                $urlEntity->setSeo($seo);

                // Product has cascade persist/remove on urls
                $product->addUrl($urlEntity);

                $urlsCreated++;
            }

            $url = $product->getUrls()->first();
            $url->setOldUrl($stringUrl);

            if (!$dryRun) {
                $this->entityManager->persist($product);
            }

            $isNew ? $created++ : $updated++;
            $progressBar->advance();

            if ($dryRun) {
                continue;
            }

            $i++;

            if (($i % $batchSize) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();

                // Rebuild managed references after clear
                $websiteRef = $this->getWebsiteRef($websiteId);

                // Reload catalogs as managed entities
                $catalogs = $this->reloadCatalogs($websiteRef);

                // Safety: if catalogs missing (should not happen), recreate them
                foreach (array_keys(self::CATALOGS) as $slug) {
                    if (!$catalogs[$slug] instanceof Catalog) {
                        $catalogs = $this->ensureCatalogs($websiteRef, false);
                        $nextPosByCatalogSlug = $this->computeNextPositions($catalogs, $websiteRef);
                        break;
                    }
                }
            }
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        $io->writeln(sprintf(
            'Products: created=%d, updated=%d, urlsCreated=%d%s',
            $created,
            $updated,
            $urlsCreated,
            $dryRun ? ' (dry-run)' : ''
        ));
    }

    /**
     * Resolve catalog slug based on URL path.
     *
     * Location product URLs are like: "/location-xxx" (NOT "/location/xxx").
     */
    private function resolveCatalogSlugFromUrl(string $url): string
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
        $path = trim($path, '/');

        if ($path === '') {
            return self::CATALOG_ANIMATION;
        }

        $segments = explode('/', $path);
        $last = (string) end($segments);

        // e.g. "location-borne-a-selfie"
        if (str_starts_with($last, 'location-')) {
            return self::CATALOG_LOCATION;
        }

        return self::CATALOG_ANIMATION;
    }

    /**
     * Build Url.code from a full URL: use path only, without query/fragment, no leading slash.
     */
    private function codeFromUrl(string $url): string
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
        $path = trim($path, '/');

        return $path;
    }

    /**
     * Ensure both catalogs exist and return them as managed entities.
     *
     * @return array<string, Catalog>
     */
    private function ensureCatalogs(Website $websiteRef, bool $dryRun): array
    {
        $catalogs = $this->reloadCatalogs($websiteRef);

        foreach (self::CATALOGS as $slug => $cfg) {

            if ($catalogs[$slug] instanceof Catalog) {
                $catalog = $catalogs[$slug];
                if (!$catalog->getLayout()) {
                    $layout = new Layout();
                    $layout->setWebsite($websiteRef);
                    $layout->setAdminName((string) $cfg['adminName']);
                    $catalog->setLayout($layout);
                    if (!$dryRun) {
                        $this->entityManager->persist($catalog);
                        $this->entityManager->flush();
                    }
                }
                continue;
            }

            $catalog = new Catalog();
            $catalog->setWebsite($websiteRef);
            $catalog->setSlug($slug);
            $catalog->setAdminName((string) $cfg['adminName']);
            $catalog->setPosition((int) $cfg['position']);

            $layout = new Layout();
            $layout->setWebsite($websiteRef);
            $layout->setAdminName((string) $cfg['adminName']);
            $catalog->setLayout($layout);

            if (!$dryRun) {
                $this->entityManager->persist($catalog);
                $this->entityManager->flush();
            }

            $catalogs[$slug] = $catalog;
        }

        return $catalogs;
    }

    /**
     * Reload catalogs from DB as managed entities.
     *
     * @return array<string, Catalog|null>
     */
    private function reloadCatalogs(Website $websiteRef): array
    {
        return [
            self::CATALOG_ANIMATION => $this->entityManager->getRepository(Catalog::class)->findOneBy([
                'slug' => self::CATALOG_ANIMATION,
                'website' => $websiteRef,
            ]),
            self::CATALOG_LOCATION => $this->entityManager->getRepository(Catalog::class)->findOneBy([
                'slug' => self::CATALOG_LOCATION,
                'website' => $websiteRef,
            ]),
        ];
    }

    /**
     * Compute next position to assign for newly created products per catalog.
     *
     * @param array<string, Catalog> $catalogs
     *
     * @return array<string, int>
     */
    private function computeNextPositions(array $catalogs, Website $websiteRef): array
    {
        $out = [];

        foreach ($catalogs as $slug => $catalog) {
            if (!$catalog instanceof Catalog) {
                $out[$slug] = 1;
                continue;
            }

            $out[$slug] = $this->getMaxProductPosition($catalog, $websiteRef) + 1;
        }

        return $out;
    }

    /**
     * Get the first Website id from database (id ASC).
     */
    private function getFirstWebsiteId(): int
    {
        $qb = $this->entityManager->createQueryBuilder();
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
        $websiteRef = $this->entityManager->getReference(Website::class, $websiteId);

        return $websiteRef;
    }

    /**
     * Get max product position for a given catalog/website.
     */
    private function getMaxProductPosition(Catalog $catalog, Website $websiteRef): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('MAX(p.position)')
            ->from(Product::class, 'p')
            ->where('p.catalog = :catalog')
            ->andWhere('p.website = :website')
            ->setParameter('catalog', $catalog)
            ->setParameter('website', $websiteRef);

        $max = $qb->getQuery()->getSingleScalarResult();

        return is_numeric($max) ? (int) $max : 0;
    }

    /**
     * Extract slug from a product URL (last path segment, urlized).
     */
    private function slugFromUrl(string $url): string
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
        $path = trim($path, '/');

        if ($path === '') {
            return '';
        }

        $segments = explode('/', $path);
        $slug = (string) end($segments);
        $slug = trim($slug);

        if ($slug === '') {
            return '';
        }

        return Urlizer::urlize($slug);
    }

    /**
     * Convert a slug (kebab-case) into a readable adminName.
     */
    private function adminNameFromSlug(string $slug): string
    {
        $slug = trim($slug);
        if ($slug === '') {
            return '';
        }

        $label = str_replace(['-', '_'], ' ', $slug);
        $label = preg_replace('~\s+~', ' ', $label) ?? $label;

        return mb_convert_case($label, MB_CASE_TITLE, 'UTF-8');
    }
}