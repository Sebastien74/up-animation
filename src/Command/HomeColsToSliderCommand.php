<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Core\Website;
use App\Entity\Layout\Action;
use App\Entity\Layout\ActionIntl;
use App\Entity\Layout\Block;
use App\Entity\Layout\BlockType;
use App\Entity\Layout\Page;
use App\Entity\Module\Slider\Slider;
use App\Entity\Module\Slider\SliderMediaRelation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Converts each home column made only of image blocks (>= 2) into a real Slider
 * entity (module Slider, editable in the back-office) wired to the column through
 * a single core-action block (slider-view). The images are moved into the slider;
 * the individual image blocks are removed.
 *
 * Idempotent: a column already holding a core-action block is skipped. Dry-run
 * unless --force.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(
    name: 'app:home:cols-to-slider',
    description: 'Transforme les colonnes multi-images de la home en Sliders liés à un bloc.',
)]
final class HomeColsToSliderCommand extends Command
{
    private const string PAGE_SLUG = 'home';
    private const string SLIDER_ACTION_SLUG = 'slider-view';
    private const string CORE_ACTION_SLUG = 'core-action';
    private const string MEDIA_SLUG = 'media';

    private SymfonyStyle $io;
    private bool $force = false;

    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Applique réellement (sinon dry-run).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->force = (bool) $input->getOption('force');
        $this->io->title($this->force ? 'Home — colonnes multi-images → Sliders' : 'Home — colonnes multi-images → Sliders (DRY-RUN)');

        $page = $this->em->getRepository(Page::class)->findOneBy(['slug' => self::PAGE_SLUG]);
        $layout = $page?->getLayout();
        if (!$layout) {
            $this->io->error('Page « home » ou son layout introuvable.');

            return Command::FAILURE;
        }

        $action = $this->em->getRepository(Action::class)->findOneBy(['slug' => self::SLIDER_ACTION_SLUG]);
        $coreActionType = $this->em->getRepository(BlockType::class)->findOneBy(['slug' => self::CORE_ACTION_SLUG]);
        if (!$action instanceof Action || !$coreActionType instanceof BlockType) {
            $this->io->error('Action « slider-view » ou BlockType « core-action » introuvable.');

            return Command::FAILURE;
        }

        $converted = 0;
        foreach ($layout->getZones() as $zone) {
            foreach ($zone->getCols() as $col) {
                $blocks = $this->visibleBlocks($col);
                $mediaBlocks = array_values(array_filter($blocks, fn (Block $b) => self::MEDIA_SLUG === $b->getBlockType()?->getSlug()));

                // Colonne « de ce type » : au moins 2 blocs, tous des images.
                if (count($blocks) < 2 || count($mediaBlocks) !== count($blocks)) {
                    continue;
                }
                // Idempotence : déjà convertie (un bloc core-action présent) ?
                foreach ($blocks as $b) {
                    if (self::CORE_ACTION_SLUG === $b->getBlockType()?->getSlug()) {
                        continue 2;
                    }
                }

                $this->convertColumn($col, $mediaBlocks, $action, $coreActionType);
                ++$converted;
            }
        }

        if ($this->force) {
            $this->em->flush();
            $this->io->success(sprintf('%d colonne(s) convertie(s) en Slider.', $converted));
        } else {
            $this->io->text(sprintf('%d colonne(s) seraient converties.', $converted));
            $this->io->note('Dry-run : aucune écriture. Relancer avec --force pour appliquer.');
        }

        return Command::SUCCESS;
    }

    /**
     * @param Block[] $mediaBlocks
     */
    private function convertColumn(object $col, array $mediaBlocks, Action $action, BlockType $coreActionType): void
    {
        $website = $mediaBlocks[0]->getCol()?->getZone()?->getLayout()?->getWebsite() ?? $this->firstWebsite();
        $colId = $col->getId();
        $this->io->section(sprintf('Colonne #%d (%d images)', $colId, count($mediaBlocks)));

        // 1. Slider.
        $slug = 'home-carrousel-col-'.$colId;
        $slider = new Slider();
        $slider->setWebsite($website);
        $slider->setAdminName('Home — carrousel colonne '.$colId);
        $slider->setSlug($slug);
        $slider->setTemplate('bootstrap');
        $slider->setItemsPerSlide(1);
        $slider->setAutoplay(true);
        $slider->setControl(true);
        $slider->setPause(true);
        $slider->setPopup(false);
        if (method_exists($slider, 'setEffect')) {
            $slider->setEffect('fade');
        }
        if (method_exists($slider, 'setIntervalDuration')) {
            $slider->setIntervalDuration(5000);
        }
        if (method_exists($slider, 'setCreatedBy') && method_exists($mediaBlocks[0], 'getCreatedBy')) {
            $slider->setCreatedBy($mediaBlocks[0]->getCreatedBy());
        }

        // 2. Médias : réutilise les fichiers Media des blocs image, par locale.
        $positions = [];
        foreach ($mediaBlocks as $block) {
            foreach ($block->getMediaRelations() as $sourceRelation) {
                $media = $sourceRelation->getMedia();
                if (!$media) {
                    continue;
                }
                $locale = $sourceRelation->getLocale();
                $positions[$locale] = ($positions[$locale] ?? 0) + 1;

                $relation = new SliderMediaRelation();
                $relation->setSlider($slider);
                $relation->setMedia($media);
                $relation->setLocale($locale);
                $relation->setPosition($positions[$locale]);
                foreach (['CategorySlug', 'Title', 'Body'] as $prop) {
                    $get = 'get'.$prop;
                    $set = 'set'.$prop;
                    if (method_exists($sourceRelation, $get) && method_exists($relation, $set)) {
                        $relation->$set($sourceRelation->$get());
                    }
                }
                $slider->addMediaRelation($relation);
                if ($this->force) {
                    $this->em->persist($relation);
                }
            }
        }

        if ($this->force) {
            $this->em->persist($slider);
            $this->em->flush(); // besoin de l'id du slider pour l'actionFilter
        }

        // 3. Bloc core-action → slider-view, en tête de colonne.
        $block = new Block();
        $block->setCol($col);
        $block->setBlockType($coreActionType);
        $block->setAction($action);
        $block->setPosition(1);
        if (method_exists($block, 'setUpdatedAt')) {
            $block->setUpdatedAt(new \DateTimeImmutable());
        }
        $col->addBlock($block);

        $locales = array_keys($positions) ?: ['fr'];
        foreach ($locales as $locale) {
            $actionIntl = new ActionIntl();
            $actionIntl->setLocale($locale);
            $actionIntl->setBlock($block);
            $actionIntl->setActionFilter($this->force ? $slider->getId() : 0);
            $block->addActionIntl($actionIntl);
            if ($this->force) {
                $this->em->persist($actionIntl);
            }
        }

        if ($this->force) {
            $this->em->persist($block);
        }

        // 4. Suppression des blocs image d'origine (les fichiers Media sont conservés).
        foreach ($mediaBlocks as $mediaBlock) {
            $this->io->text(sprintf('  - retrait du bloc image #%d', $mediaBlock->getId()));
            if ($this->force) {
                $this->em->remove($mediaBlock);
            }
        }
        $this->io->text(sprintf('  + slider « %s » + bloc core-action slider-view', $slug));
    }

    /**
     * @return Block[]
     */
    private function visibleBlocks(object $col): array
    {
        $blocks = [];
        foreach ($col->getBlocks() as $block) {
            $hideLocales = method_exists($block, 'getHideLocales') ? $block->getHideLocales() : [];
            if (!$block->isHide() && !in_array('fr', $hideLocales, true)) {
                $blocks[] = $block;
            }
        }

        return $blocks;
    }

    private function firstWebsite(): ?Website
    {
        $all = $this->em->getRepository(Website::class)->findAll();

        return $all[0] ?? null;
    }
}
