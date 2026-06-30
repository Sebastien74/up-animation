<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Core\Website;
use App\Entity\Module\Catalog\Catalog;
use App\Entity\Module\Catalog\Category;
use App\Entity\Module\Catalog\CategoryIntl;
use App\Entity\Module\Catalog\Product;
use App\Entity\Module\Catalog\ProductIntl;
use App\Entity\Seo\Seo;
use App\Entity\Seo\Url;
use App\Service\Core\Urlizer;
use App\Service\DataFixtures\UploadedFileFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:import:live-products', description: 'Import scraped live products into the catalog')]
final class ImportLiveProductsCommand extends Command
{
    private const WEBSITE_ID = 1;
    private const CATALOG_ID = 6; // Prestations

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UploadedFileFixtures $uploadedFileFixtures,
        private readonly RequestStack $requestStack,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('file', null, 'Path to targets.json');
        $this->addOption('apply', null, InputOption::VALUE_NONE, 'Write to DB (default: dry-run)');
        $this->addOption('tmp', null, InputOption::VALUE_REQUIRED, 'Temp dir for image downloads', sys_get_temp_dir());
        $this->addOption('report', null, InputOption::VALUE_REQUIRED, 'Write a JSON report of created/filled ids to this path');
        $this->addOption('catalog', null, InputOption::VALUE_REQUIRED, 'Catalog id for NEW products', (string) self::CATALOG_ID);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        // Some services call requestStack->getSession() during persist/flush; provide one in CLI.
        $request = Request::create('/');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $this->requestStack->push($request);
        $apply = (bool) $input->getOption('apply');
        $tmpDir = rtrim((string) $input->getOption('tmp'), '/\\');
        $targets = json_decode((string) file_get_contents($input->getArgument('file')), true);
        if (!is_array($targets)) {
            $io->error('Cannot read targets file.');
            return Command::FAILURE;
        }

        $website = $this->em->getRepository(Website::class)->find(self::WEBSITE_ID);
        $catalog = $this->em->getRepository(Catalog::class)->find((int) $input->getOption('catalog'));
        $io->title(($apply ? 'APPLY' : 'DRY-RUN').' import: '.count($targets).' targets');

        // 1) Ensure event-type categories exist
        $catLabels = [];
        foreach ($targets as $t) {
            foreach ($t['categories'] ?? [] as $c) {
                $catLabels[$c] = true;
            }
        }
        $categoryMap = [];
        foreach (array_keys($catLabels) as $label) {
            $categoryMap[$label] = $this->ensureCategory($label, $website, $apply, $io);
        }

        $report = ['filled' => [], 'created' => [], 'images' => 0, 'skippedImages' => 0];

        foreach ($targets as $t) {
            $isNew = ($t['action'] ?? 'new') === 'new';
            $product = null;
            if (!$isNew && !empty($t['productId'])) {
                $product = $this->em->getRepository(Product::class)->find($t['productId']);
            }
            if (!$product) {
                $product = $this->em->getRepository(Product::class)->findOneBy(['adminName' => $t['title']]);
            }
            $creating = false;
            if (!$product) {
                $creating = true;
                $product = new Product();
                $product->setCatalog($catalog);
                $product->setWebsite($website);
                $product->setAdminName($t['title']);
                $product->setPosition(count($catalog->getProducts()) + count($report['created']) + 1);
            }

            // Intl FR
            $intl = $this->getIntl($product, 'fr');
            if (!$intl) {
                $intl = new ProductIntl();
                $intl->setWebsite($website);
                $intl->setLocale('fr');
                $product->addIntl($intl);
            }
            if ($creating || !$intl->getTitle()) {
                $intl->setTitle($t['title']);
            }
            [$intro, $body] = $this->buildContent($t);
            $intl->setIntroduction($intro);
            $intl->setBody($body);

            // URL + SEO (fr)
            $url = $this->getUrl($product, 'fr');
            if (!$url) {
                $url = new Url();
                $url->setWebsite($website);
                $url->setLocale('fr');
                $url->setCode(Urlizer::urlize(strip_tags($t['title'])));
                $url->setOnline(true);
                $seo = new Seo();
                $seo->setMetaTitle($t['title']);
                $url->setSeo($seo);
                $product->addUrl($url);
            }

            // Categories
            foreach ($product->getCategories() as $c) {
                $product->removeCategory($c);
            }
            foreach ($t['categories'] ?? [] as $label) {
                if (!empty($categoryMap[$label])) {
                    $product->addCategory($categoryMap[$label]);
                }
                if (empty($product->getMainCategory()) && !empty($categoryMap[$label])) {
                    $product->setMainCategory($categoryMap[$label]);
                }
            }

            if ($apply) {
                $this->em->persist($product);
                $this->em->flush();
            }

            // Images (only if product has none yet -> idempotent)
            $existingMedia = $product->getMediaRelations()->count();
            $imgImported = 0;
            if ($existingMedia === 0) {
                $position = 0;
                foreach ($t['images'] ?? [] as $imgUrl) {
                    if (!$apply) { $imgImported++; continue; }
                    $localPath = $this->download($imgUrl, $tmpDir);
                    if (!$localPath) { $report['skippedImages']++; continue; }
                    $media = $this->uploadedFileFixtures->uploadedFile($website, $localPath, 'fr', $product);
                    @unlink($localPath);
                    if ($media) { $imgImported++; }
                }
                // set first relation as main + positions
                if ($apply && $imgImported > 0) {
                    $pos = 0;
                    foreach ($product->getMediaRelations() as $rel) {
                        $rel->setPosition($pos);
                        if (method_exists($rel, 'setMain')) { $rel->setMain($pos === 0); }
                        $pos++;
                    }
                    $this->em->flush();
                }
            } else {
                $report['skippedImages'] += count($t['images'] ?? []);
            }
            $report['images'] += $imgImported;

            $entry = ['id' => $product->getId(), 'title' => $t['title'], 'sources' => $t['sources'] ?? [], 'categories' => $t['categories'] ?? [], 'images' => $imgImported];
            if ($creating) { $report['created'][] = $entry; } else { $report['filled'][] = $entry; }
            $io->writeln(sprintf('%s%s #%s "%s" | cats:%d | imgs:%d', $apply ? '' : '[dry] ', $creating ? 'NEW' : 'FILL', $product->getId() ?? '?', $t['title'], count($t['categories'] ?? []), $imgImported));
        }

        if ($reportPath = $input->getOption('report')) {
            $report['categories'] = array_map(static fn ($c) => $c ? ['id' => $c->getId(), 'title' => $c->getAdminName()] : null, $categoryMap);
            $report['mode'] = $apply ? 'apply' : 'dry-run';
            file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $io->section('Catégories');
        $io->listing(array_keys($categoryMap));
        $io->success(sprintf('%s | rempli: %d | créé: %d | images: %d (ignorées: %d)',
            $apply ? 'IMPORT TERMINÉ' : 'DRY-RUN (aucune écriture)',
            count($report['filled']), count($report['created']), $report['images'], $report['skippedImages']));

        return Command::SUCCESS;
    }

    private function ensureCategory(string $label, Website $website, bool $apply, SymfonyStyle $io): ?Category
    {
        $intl = $this->em->getRepository(CategoryIntl::class)->findOneBy(['title' => $label, 'locale' => 'fr']);
        if ($intl) {
            return $intl->getCategory();
        }
        $category = new Category();
        $category->setWebsite($website);
        $category->setAdminName($label);
        $category->setSlug(Urlizer::urlize($label));
        $category->setPosition(count($this->em->getRepository(Category::class)->findAll()) + 1);
        $ci = new CategoryIntl();
        $ci->setWebsite($website);
        $ci->setLocale('fr');
        $ci->setTitle($label);
        $category->addIntl($ci);
        if ($apply) {
            $this->em->persist($category);
            $this->em->flush();
        }
        $io->writeln('  + catégorie: '.$label);
        return $category;
    }

    private function buildContent(array $t): array
    {
        $body = trim((string) ($t['body'] ?? ''));
        $paras = array_values(array_filter(array_map('trim', preg_split('/\n{2,}/', $body))));
        $intro = $paras[0] ?? '';
        $html = '';
        foreach ($paras as $p) {
            $html .= '<p>'.htmlspecialchars($p, ENT_QUOTES, 'UTF-8').'</p>';
        }
        $infos = [];
        if (!empty($t['capacity'])) { $infos[] = '<li><strong>Nombre de personnes :</strong> '.htmlspecialchars($t['capacity']).'</li>'; }
        if (!empty($t['duration'])) { $infos[] = '<li><strong>Durée :</strong> '.htmlspecialchars($t['duration']).'</li>'; }
        if ($infos) {
            $html .= '<h3>Infos pratiques</h3><ul>'.implode('', $infos).'</ul>';
        }
        return [$intro, $html];
    }

    private function getIntl(Product $product, string $locale): ?ProductIntl
    {
        foreach ($product->getIntls() as $intl) {
            if ($intl->getLocale() === $locale) { return $intl; }
        }
        return null;
    }

    private function getUrl(Product $product, string $locale): ?Url
    {
        foreach ($product->getUrls() as $url) {
            if ($url->getLocale() === $locale) { return $url; }
        }
        return null;
    }

    private function download(string $url, string $tmpDir): ?string
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)) ?: 'jpg';
        $dest = $tmpDir.'/imp_'.substr(md5($url), 0, 12).'.'.$ext;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'Mozilla/5.0 import-bot',
        ]);
        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($data === false || $code >= 400 || strlen($data) < 500) {
            return null;
        }
        file_put_contents($dest, $data);
        return $dest;
    }
}
