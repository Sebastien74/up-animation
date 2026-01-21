<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Entity\Layout;
use App\Entity\Media\Media;
use App\Entity\Module\Slider\Slider;
use App\Entity\Security\User;
use App\Model\Core\WebsiteModel;
use App\Model\Layout\BlockModel;
use App\Model\MediasModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Faker\Factory;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * ComponentRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ComponentRuntime implements RuntimeExtensionInterface
{
    /**
     * ComponentRuntime constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly Environment $templating,
    )
    {
    }

    /**
     * Check if is Admin User.
     */
    public function isComponentUser(): bool
    {
        $tokenStorage = $this->coreLocator->tokenStorage();
        $currentUser = method_exists($tokenStorage, 'getToken') && $tokenStorage->getToken()
            ? $tokenStorage->getToken()->getUser() : null;

        return $currentUser instanceof User;
    }

    /**
     * Generate & render Zone.
     *
     * @throws MappingException|NonUniqueResultException
     */
    public function newZone(WebsiteModel $website, array $options = []): void
    {
        $template = $website->configuration->template;
        $zone = $this->addZone($options);

        if (!empty($options['cols'])) {
            foreach ($options['cols'] as $position => $colConfig) {
                $col = $this->addCol($position, $colConfig);
                $zone->addCol($col);
                if (!empty($colConfig['blocks'])) {
                    foreach ($colConfig['blocks'] as $blockPosition => $configuration) {
                        foreach ($configuration as $blockTemplate => $blockOptions) {
                            try {
                                $block = $this->newBlock($website, $blockPosition, $blockTemplate, $blockOptions, $zone);
                                if ($block instanceof Layout\Block) {
                                    $col->addBlock($block);
                                }
                            } catch (LoaderError|SyntaxError|RuntimeError $exception) {
                                dd($exception);
                            }
                        }
                    }
                }
            }
        }

        try {
            echo $this->templating->render('front/'.$template.'/include/zone.html.twig', [
                'disabledEditTools' => true,
                'forceContainer' => !$zone->isFullSize(),
                'transitions' => [],
                'seo' => $options['seo'],
                'website' => $website,
                'zone' => $zone,
            ]);
        } catch (LoaderError|RuntimeError|SyntaxError $exception) {
            dd($exception);
        }
    }

    /**
     * Generate & render block.
     *
     * @throws LoaderError|RuntimeError|SyntaxError|MappingException|NonUniqueResultException
     */
    public function newBlock(WebsiteModel $website, int $position, string $blockTemplate, array $options = [], ?Layout\Zone $zone = null): Layout\Block|string|null
    {
        $block = $this->addBlock($website, $position, $blockTemplate, $options);
        $blockEntity = $block['block']->block;
        $template = $website->configuration->template;

        if ('title-header' === $blockTemplate) {
            $zone->setPaddingTop(null);
            $zone->setPaddingBottom(null);
            $zone->setPaddingLeft('ps-0');
            $zone->setPaddingRight('pe-0');
        }

        if ('title' === $blockTemplate) {
            $block['block']->block->setMarginBottom('mb-sm');
        }

        $arguments = array_merge([
            'template' => $template,
            'websiteTemplate' => $template,
            'thumbConfiguration' => [],
            'block' => $block['block'],
            'intl' => $block['block']->intl,
            'website' => $website,
        ], $options);

        if ('core-action' === $blockTemplate) {
            $zone->setHide(true);
            $arguments[$options['module']] = $block['entity'];
            if ('slider' === $options['module']) {
                $arguments['medias'] = MediasModel::fromEntity($block['entity'], $this->coreLocator)->list;
            }
            echo $this->templating->render('front/'.$template.'/actions/'.$options['module'].'/'.$options['template'].'.html.twig', $arguments);
        }

        if (!$zone) {
            echo $this->templating->render('front/'.$template.'/blocks/'.$blockTemplate.'/default.html.twig', $arguments);
        } else {
            return $blockEntity;
        }

        return null;
    }

    /**
     * Add Zone.
     */
    private function addZone(array $options = []): Layout\Zone
    {
        $fullSize = $options['fullSize'] ?? false;
        $position = $options['position'] ?? 1;
        $background = $options['background'] ?? null;
        $paddingTop = $options['paddingTop'] ?? 'pt-lg';
        $paddingBottom = $options['paddingBottom'] ?? 'pb-lg';

        $zone = new Layout\Zone();
        $zone->setFullSize($fullSize);
        $zone->setPosition($position);
        $zone->setBackgroundColor($background);
        $zone->setPaddingTop($paddingTop);
        $zone->setPaddingBottom($paddingBottom);

        return $zone;
    }

    /**
     * Add Col.
     */
    private function addCol(int $position, array $configuration): Layout\Col
    {
        $paddingLeft = $configuration['paddingLeft'] ?? null;
        $paddingRight = $configuration['paddingRight'] ?? null;
        $size = $configuration['size'] ?? 12;

        $col = new Layout\Col();
        $col->setPosition($position);
        $col->setPaddingLeft($paddingLeft);
        $col->setPaddingRight($paddingRight);
        $col->setSize($size);

        return $col;
    }

    /**
     * Add Block.
     *
     * @throws MappingException|NonUniqueResultException
     */
    private function addBlock(WebsiteModel $website, int $position, string $blockTemplate, array $options = []): array
    {
        $titleForce = $options['titleForce'] ?? 2;
        $color = $options['color'] ?? null;

        /** @var Layout\BlockType $blockType */
        $blockType = $this->coreLocator->em()->getRepository(Layout\BlockType::class)->findOneBy(['slug' => $blockTemplate]);
        $intl = $this->addIntl(Layout\BlockIntl::class, $titleForce);

        $block = new Layout\Block();
        $block->setColor($color);
        $block->addIntl($intl);
        $block->setBlockType($blockType);
        $block->setAdminName('force-intl');
        $block->setPaddingLeft('ps-0');
        $block->setPaddingRight('pe-0');
        $block->setPosition($position);

        if (!str_contains($blockTemplate, 'title')) {
            $block->setMarginBottom('mb-md');
        }

        if ('media' === $blockTemplate) {
            $this->addMedias($website, $block, 1, $options);
        } elseif ('core-action' === $blockTemplate) {
            $action = $this->coreLocator->em()->getRepository(Layout\Action::class)->findOneBy(['slug' => $options['action']]);
            $block->setAction($action);
            $entity = null;
            if ('slider-view' === $options['action']) {
                $entity = $this->addSlider($website);
            }
            $this->addMedias($website, $entity, 5);
        }

        return [
            'block' => BlockModel::fromEntity($block, $this->coreLocator),
            'entity' => !empty($entity) ? $entity : null,
        ];
    }

    /**
     * Add Slider.
     */
    private function addSlider(WebsiteModel $website): Slider
    {
        $faker = Factory::create();
        $slider = new Slider();
        $slider->setAdminName('Components carrousel');
        $slider->setWebsite($website->entity);
        $slider->setSlug('components-carrousel'.$faker->slug());
        $slider->setIndicators(true);
        $slider->setPopup(true);

        return $slider;
    }

    /**
     * Add intl.
     */
    private function addIntl(string $intlClassname, int $titleForce = 2): mixed
    {
        $faker = Factory::create();

        $body = $faker->text(600);
        $body .= '<br><br><strong><span class="text-underline">Strong text</span> '.$faker->text(10).'</strong>';
        $body .= '<br><b><span class="text-underline">Bold text</span> '.$faker->text(10).'</b>';
        $body .= '<br><small><span class="text-underline">Small text</span> '.$faker->text(10).'</small>';
        $body .= '<br><a href="'.$this->coreLocator->request()->getSchemeAndHttpHost().'">Link : '.$faker->text(10).'</a>';
        $body .= '<br><br><ul><li>'.$faker->text(10).'</li><li>'.$faker->text(10).'</li><li>'.$faker->text(10).'</li></ul>';

        $intl = new $intlClassname();
        $intl->setTitleForce($titleForce);
        $intl->setLocale($this->coreLocator->request()->getLocale());
        $intl->setTitle('H'.$titleForce.'. '.$faker->text(35));
        $intl->setBody($body);
        $intl->setIntroduction($faker->text(600));
        $intl->setSubTitle('Sous-titre '.$faker->text(15));
        $intl->setTargetLink($this->coreLocator->request()->getSchemeAndHttpHost());
        $intl->setTargetLabel($faker->text(10));

        return $intl;
    }

    /**
     * Add Media[].
     *
     * @throws MappingException
     */
    private function addMedias(WebsiteModel $website, mixed $entity, int $count, array $options = []): void
    {
        $faker = Factory::create();
        $type = $options['type'] ?? 'image';

        for ($i = 1; $i <= $count; ++$i) {

            $asImage = 'image' === $type;
            $filename = $asImage ? 'image-'.$i.'.jpg' : 'file.pdf';
            $extension = $asImage ? 'jpg' : 'pdf';
            $mediaRelationData = $this->coreLocator->metadata($entity, 'mediaRelations');
            $mediaRelation = new ($mediaRelationData->targetEntity)();
            $intlData = $this->coreLocator->metadata($mediaRelation, 'intl');
            $intl = new ($intlData->targetEntity)();
            $title = $asImage ? 'Titre de votre image' : 'Titre de votre fichier';
            $targetLabel = $asImage ? $intl->getTargetLabel() : 'Télécharger';

            $intl->setTitle($title);
            $intl->setTargetLabel($targetLabel);

            $media = new Media();
            $media->setWebsite($website->entity);
            $media->setCategory('cms-component');
            $media->setName($filename);
            $media->setFilename('/medias/components/'.$filename);
            $media->setCopyright($faker->company);
            $media->setExtension($extension);
            $media->setNotContractual(true);

            $mediaRelation->setMedia($media);
            $mediaRelation->setLocale($this->coreLocator->request()->getLocale());
            $mediaRelation->setCategorySlug('cms-component');
            $mediaRelation->setPopup(true);
            $mediaRelation->setDownloadable(true);
            $mediaRelation->setPosition($i);
            $mediaRelation->setIntl($intl);

            $entity->addMediaRelation($mediaRelation);
        }
    }
}
