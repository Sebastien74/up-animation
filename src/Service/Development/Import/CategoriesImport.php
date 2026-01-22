<?php

declare(strict_types=1);

namespace App\Service\Development\Import;

use App\Entity\Core\Website;
use App\Entity\Module\Catalog\Category;
use App\Service\Core\Urlizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Filesystem\Filesystem;

/**
 * CategoriesImport.
 *
 * Create / update catalog categories from contents.json "categories" bucket.
 *
 * Rules:
 * - adminName is taken from metas.json "title" for the matching category URL
 * - slug is Urlizer::urlize(adminName)
 * - upsert by (slug + website)
 * - position is set only when the category is newly created (append after current max)
 *
 * Notes:
 * - metas.json is expected at var/crawler/metas.json by default.
 * - URL matching is tolerant:
 *   - ignores trailing slashes
 *   - normalizes pagination (/page/<n>/) to the root category URL
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AutoconfigureTag('app.contents_importer')]
class CategoriesImport implements ContentsImporterInterface
{
    private const string DEFAULT_METAS_PATH = 'var/crawler/metas.json';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $projectDir,
    ) {
    }

    public function supports(string $bucket): bool
    {
        return $bucket === 'categories';
    }

    /**
     * @param array<string, mixed> $bucketPayload map(url => payload)
     */
    public function import(string $bucket, array $bucketPayload, SymfonyStyle $io, ProgressBar $progressBar, bool $dryRun = false): void
    {
        $websiteId = $this->getFirstWebsiteId();
        $website = $this->getWebsiteRef($websiteId);

        $metasIndex = $this->buildMetasIndex($this->absPath(self::DEFAULT_METAS_PATH));

        $created = 0;
        $updated = 0;

        $maxPos = $this->getMaxCategoryPosition($website);
        $nextPos = $maxPos + 1;

        $batchSize = 50;
        $i = 0;

        foreach (array_keys($bucketPayload) as $url) {
            $progressBar->setMessage('Importing categories');

            if (!is_string($url) || trim($url) === '') {
                $progressBar->advance();
                continue;
            }

            $url = trim($url);
            $normalizedUrl = $this->normalizeUrlKey($url);

            $titleHtml = $metasIndex[$normalizedUrl]['title'] ?? '';
            $adminName = $this->adminNameFromTitleHtml($titleHtml);

            // Fallback if no title available
            if ($adminName === '') {
                $adminName = $this->adminNameFromUrl($normalizedUrl);
            }

            if ($adminName === '') {
                $progressBar->advance();
                continue;
            }

            $slug = Urlizer::urlize($adminName);
            if ($slug === '') {
                $progressBar->advance();
                continue;
            }

            /** @var Category|null $category */
            $category = $this->entityManager->getRepository(Category::class)->findOneBy([
                'slug' => $slug,
                'website' => $website,
            ]);

            $isNew = false;
            if (!$category) {
                $category = new Category();
                $category->setWebsite($website);

                // Position only on creation
                $category->setPosition($nextPos);
                $nextPos++;

                $isNew = true;
            }

            // Upsert fields
            $category->setAdminName($adminName);
            $category->setSlug($slug);

            if (!$dryRun) {
                $this->entityManager->persist($category);
            }

            $isNew ? $created++ : $updated++;

            $progressBar->advance();

            if (!$dryRun) {
                $i++;
                if (($i % $batchSize) === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();

                    // Re-hydrate Website reference after clear
                    $website = $this->getWebsiteRef($websiteId);
                }
            }
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        $io->writeln(sprintf('Categories: created=%d, updated=%d%s', $created, $updated, $dryRun ? ' (dry-run)' : ''));
    }

    /**
     * Build an index from metas.json with normalized URL keys.
     *
     * @param string $metasPath absolute path
     *
     * @return array<string, array<string, mixed>>
     */
    private function buildMetasIndex(string $metasPath): array
    {
        $fs = new Filesystem();
        if (!$fs->exists($metasPath)) {
            return [];
        }

        $raw = @file_get_contents($metasPath);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        if (!is_array($decoded)) {
            return [];
        }

        $index = [];
        foreach ($decoded as $url => $payload) {
            if (!is_string($url) || trim($url) === '' || !is_array($payload)) {
                continue;
            }

            $norm = $this->normalizeUrlKey(trim($url));

            // We only need title/titleType for this importer, but keep the whole payload
            $index[$norm] = $payload;
        }

        return $index;
    }

    /**
     * Normalize URL key for matching metas/contents:
     * - trim + remove trailing slash
     * - collapse pagination: /page/<n>/ => base category URL
     */
    private function normalizeUrlKey(string $url): string
    {
        $url = trim($url);
        $url = rtrim($url, '/');

        // Normalize ".../page/<n>" patterns
        $url = preg_replace('~(/page/\d+)$~', '', $url) ?? $url;
        $url = rtrim($url, '/');

        return $url;
    }

    /**
     * Build adminName from a metas.json "title" field (innerHTML).
     * We keep it safe for DB by stripping tags and decoding entities.
     */
    private function adminNameFromTitleHtml(string $titleHtml): string
    {
        $titleHtml = trim($titleHtml);
        if ($titleHtml === '') {
            return '';
        }

        $plain = strip_tags($titleHtml);
        $plain = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = preg_replace('~\s+~u', ' ', $plain) ?? $plain;

        return trim($plain);
    }

    /**
     * Fallback adminName from URL path (best-effort).
     */
    private function adminNameFromUrl(string $url): string
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

        $label = str_replace(['-', '_'], ' ', $slug);
        $label = preg_replace('~\s+~', ' ', $label) ?? $label;

        return mb_convert_case($label, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Get max category position for a given website.
     */
    private function getMaxCategoryPosition(Website $website): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('MAX(c.position)')
            ->from(Category::class, 'c')
            ->where('c.website = :website')
            ->setParameter('website', $website);

        $max = $qb->getQuery()->getSingleScalarResult();

        return is_numeric($max) ? (int) $max : 0;
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

        $id = $qb->getQuery()->getOneOrNullResult();

        $websiteId = 0;
        if (is_array($id) && isset($id['id'])) {
            $websiteId = (int) $id['id'];
        } elseif (is_numeric($id)) {
            $websiteId = (int) $id;
        }

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
     * Convert relative paths to absolute paths from projectDir.
     */
    private function absPath(string $path): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        // Already absolute?
        if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('~^[A-Za-z]:\\\\~', $path) === 1) {
            return $path;
        }

        return $this->projectDir . DIRECTORY_SEPARATOR . $path;
    }
}
