<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Core\Website;
use App\Entity\Media\Media;
use App\Entity\Media\MediaIntl;
use App\Entity\Media\MediaRelationIntl;
use App\Entity\Module\Catalog\Product;
use App\Entity\Module\Catalog\ProductMediaRelation;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AsCommand(name: 'app:import:repair-media', description: 'Make imported media relations CMS-conformant (size, MediaIntl, per-locale relations)')]
final class ImportRepairMediaCommand extends Command
{
    private const WEBSITE_ID = 1;
    private const CATALOG_ID = 6;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CoreLocatorInterface $coreLocator,
        private readonly RequestStack $requestStack,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('apply', null, InputOption::VALUE_NONE, 'Write to DB (default: dry-run)');
        $this->addOption('catalog', null, InputOption::VALUE_REQUIRED, 'Catalog id to repair', (string) self::CATALOG_ID);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $request = Request::create('/');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $this->requestStack->push($request);
        $apply = (bool) $input->getOption('apply');

        $website = $this->em->getRepository(Website::class)->find(self::WEBSITE_ID);
        $locales = $website->getConfiguration()->getAllLocales();
        $defaultLocale = $website->getConfiguration()->getLocale();
        $uploadBase = rtrim($this->coreLocator->uploadDir(), '/\\').'/'.$website->getUploadDirname();

        $products = $this->em->getRepository(Product::class)->findBy(['catalog' => $this->em->getReference(\App\Entity\Module\Catalog\Catalog::class, (int) $input->getOption('catalog'))]);
        $io->title(($apply ? 'APPLY' : 'DRY-RUN').' repair media | locales: '.implode(',', $locales));

        $stats = ['media' => 0, 'mediaIntl' => 0, 'relIntl' => 0, 'localeRel' => 0, 'sized' => 0];

        foreach ($products as $product) {
            $relations = $product->getMediaRelations();
            if ($relations->isEmpty()) {
                continue;
            }
            // index existing relations by position+locale, and collect media by position
            $byPosLocale = [];
            $mediaByPos = [];
            foreach ($relations as $rel) {
                $byPosLocale[$rel->getPosition()][$rel->getLocale()] = $rel;
                if ($rel->getMedia()) {
                    $mediaByPos[$rel->getPosition()] = $rel->getMedia();
                }
            }

            foreach ($mediaByPos as $position => $media) {
                // 1) size + mimeType on the Media
                if (!$media->getSize()) {
                    $file = $uploadBase.'/'.$media->getName().'.'.$media->getExtension();
                    if (is_file($file)) {
                        $media->setSize(filesize($file) ?: null);
                        $media->setMimeType(@mime_content_type($file) ?: null);
                        $stats['sized']++;
                    }
                }
                // 2) MediaIntl per locale
                foreach ($locales as $locale) {
                    $has = false;
                    foreach ($media->getIntls() as $mi) {
                        if ($mi->getLocale() === $locale) { $has = true; break; }
                    }
                    if (!$has) {
                        $mi = new MediaIntl();
                        $mi->setLocale($locale);
                        $mi->setTitle($media->getName());
                        $mi->setWebsite($website);
                        $media->addIntl($mi);
                        $stats['mediaIntl']++;
                    }
                }
                if ($apply) { $this->em->persist($media); }

                // 3) relation intl + 4) per-locale relations
                $template = $byPosLocale[$position][$defaultLocale] ?? reset($byPosLocale[$position]);
                foreach ($locales as $locale) {
                    $rel = $byPosLocale[$position][$locale] ?? null;
                    if (!$rel) {
                        $rel = new ProductMediaRelation();
                        $rel->setLocale($locale);
                        $rel->setMedia($media);
                        $rel->setPosition($position);
                        $rel->setMain($template->isMain());
                        $rel->setHeader($template->isHeader());
                        $rel->setCategorySlug($template->getCategorySlug());
                        $rel->setInit(true);
                        $product->addMediaRelation($rel);
                        $byPosLocale[$position][$locale] = $rel;
                        $stats['localeRel']++;
                    }
                    if (!$rel->getIntl()) {
                        $intl = new MediaRelationIntl();
                        $intl->setLocale($locale);
                        $intl->setWebsite($website);
                        $rel->setIntl($intl);
                        $stats['relIntl']++;
                    }
                }
                $stats['media']++;
            }

            if ($apply) {
                $this->em->persist($product);
                $this->em->flush();
            }
            $io->writeln(sprintf('%s#%d %s | medias:%d', $apply ? '' : '[dry] ', $product->getId(), $product->getAdminName(), count($mediaByPos)));
        }

        $io->success(sprintf('%s | medias traités: %d | size set: %d | MediaIntl+: %d | relIntl+: %d | relations locale+: %d',
            $apply ? 'RÉPARATION OK' : 'DRY-RUN', $stats['media'], $stats['sized'], $stats['mediaIntl'], $stats['relIntl'], $stats['localeRel']));

        return Command::SUCCESS;
    }
}
