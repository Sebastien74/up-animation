<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Entity\Core;
use App\Entity\Layout;
use App\Entity\Media;
use App\Model\Core\ConfigurationModel;
use App\Model\Core\WebsiteModel;
use App\Model\EntityModel;
use App\Model\Layout\BlockModel;
use App\Model\ViewModel;
use App\Service\Content\ThumbService;
use App\Service\Interface\CoreLocatorInterface;
use App\Twig\Translation\i18nRuntime;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Exception;
use Psr\Cache\InvalidArgumentException;
use ReflectionException;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * LayoutRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LayoutRuntime implements RuntimeExtensionInterface
{
    private const bool CHECK_ZONE_BLOCKS = false;
    private const bool AOS_ON_MOBILE = true;
    private ?string $screen;
    private ?string $zoneClasses = '';

    private array $cache = [];

    /**
     * LayoutRuntime constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly Environment $templating,
        private readonly i18nRuntime $i18nRuntime,
        private readonly BrowserRuntime $browserRuntime,
        private readonly MediaRuntime $mediaRuntime,
        private readonly ThumbService $thumbService,
        private readonly bool $isDebug,
    ) {
        $this->screen = $browserRuntime->screen();
    }

    /**
     * To get Layout relations.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    public function layoutRelations(?Layout\Layout $layout = null): array
    {
        $relations = [];
        if ($layout) {
            $relations['zones'] = [
                'medias' => $this->mediaRuntime->mediasWithFilename(Layout\Zone::class, $layout),
                'intls' => $this->i18nRuntime->intlsWithContent(Layout\Zone::class, $layout),
            ];
        }

        return $relations;
    }

    /**
     * Get style classes.
     */
    public function styleClass(mixed $entity, array $default = []): string
    {
        $fontSize = $this->getValue($entity, 'fontSize');
        $class = $fontSize ? 'fz-'.$fontSize.' ' : (isset($default['fontSize']) ? $default['fontSize'].' ' : '');
        $fontWeight = $this->getValue($entity, 'fontWeight');
        $class .= $fontWeight ? 'fw-'.$fontWeight.' ' : (isset($default['fontWeight']) ? $default['fontWeight'].' ' : '');
        $fontFamily = $this->getValue($entity, 'fontFamily');
        $class .= $fontFamily ? 'ff-'.$fontFamily.' ' : (isset($default['fontFamily']) ? $default['fontFamily'].' ' : '');
        $color = $this->getValue($entity, 'color');
        $class .= $color ? 'text-'.$color.' ' : (isset($default['color']) ? $default['color'].' ' : '');
        $uppercase = $this->getValue($entity, 'uppercase');
        $class .= $uppercase || isset($default['uppercase']) ? 'text-uppercase ' : '';
        $italic = $this->getValue($entity, 'italic');
        $class .= $italic || isset($default['italic']) ? 'text-italic ' : '';
        $zIndex = self::getValue('zIndex', $entity);
        $class .= $zIndex ? 'z-index'.$zIndex.' ' : (isset($default['zIndex']) ? $default['zIndex'].' ' : '');

        return trim($class);
    }

    /**
     * To get block render.
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws NonUniqueResultException
     */
    public function renderBlock(array $options = []): string|Response|null
    {
        if (!$this->isDebug) {
            try {
                return $this->getRenderBlock($options);
            } catch (LoaderError|RuntimeError|SyntaxError|Exception $e) {
                return null;
            }
        } else {
            return $this->getRenderBlock($options);
        }
    }

    private array $transitionsCache = [];

    /**
     * Get render block.
     *
     * @throws LoaderError|RuntimeError|SyntaxError|InvalidArgumentException|ReflectionException|NonUniqueResultException|Exception
     */
    private function getRenderBlock(array $options = []): string|Response|null
    {
        if ($options['website'] instanceof Core\Website) {
            $websiteEntity = $options['website'];
            $websiteId = $websiteEntity->getId();
            $website = $this->cache['website_model'][$websiteId] ??= WebsiteModel::fromEntity($websiteEntity, $this->coreLocator);
        } else {
            $website = $options['website'];
        }
        $zone = $options['zone'];
        $block = $options['block'];
        $seo = !empty($options['seo']) ? $options['seo'] : false;
        $entity = !empty($options['entity']) ? $options['entity'] : null;
        $blockType = $block->getBlockType();
        $blockTemplate = $slugBlock = $blockType->getSlug();
        $configuration = $website->configuration;
        $websiteTemplate = $configuration->template;
        $mediasSizes = $zone->isStandardizeMedia() ? $this->mediasSizes($website, $zone, $this->coreLocator->request()->getLocale()) : [];

        $entityTemplate = $entity && is_object($entity) && $blockTemplate && str_contains($blockTemplate, 'layout') && !method_exists($entity, 'getTemplate') && property_exists($entity, 'template') ? $entity->template : null;
        $template = $entityTemplate && $this->templateExists($entityTemplate) ? $entityTemplate : 'front/'.$websiteTemplate.'/blocks/'.$blockTemplate.'/'.$block->getTemplate().'.html.twig';
        $template = !$this->templateExists($template) ? 'front/'.$websiteTemplate.'/actions/vendor/include/'.$blockTemplate.'.html.twig' : $template;
        if (!$this->templateExists($template)) {
            throw new \RuntimeException('Template '.$template." doesn't exist !!");
        }

        /** Get Block Transition[] */
        if (!isset($this->transitionsCache[$blockTemplate])) {
            $blockTransitions = [];
            foreach ($configuration->transitions as $transition) {
                if ($transition->activeForBlock && str_contains($transition->section, 'block-'.$blockTemplate)) {
                    $blockTransitions[$transition->slug] = $transition;
                }
            }
            $this->transitionsCache[$blockTemplate] = $blockTransitions;
        }
        $blockTransitions = $this->transitionsCache[$blockTemplate];

        $thumbSlug = $block->isLarge() && 'title-header' === $slugBlock ? $slugBlock.'-large' : $slugBlock;

        $arguments = array_merge($options, (array) BlockModel::fromEntity($block, $this->coreLocator), [
            $options['interfaceName'] => !empty($options['entity']) ? EntityModel::fromEntity($options['entity'], $this->coreLocator, ['disabledMedias' => true, 'disabledLayout' => true])->response : null,
            'logos' => $website->configuration->logos,
            'isIndex' => $options['isIndex'],
            'website' => $website,
            'mediaWidth' => $mediasSizes ? $mediasSizes['width'] : null,
            'mediaHeight' => $mediasSizes ? $mediasSizes['height'] : null,
            'configuration' => $configuration,
            'websiteTemplate' => $websiteTemplate,
            'thumbConfiguration' => $this->thumbService->thumbConfiguration($website, Layout\Block::class, 'block', $block->getId(), $thumbSlug),
            'blockTransitions' => $blockTransitions,
            'url' => $seo ? $seo['url'] : null,
            'seo' => $seo,
            'asBlock' => true,
        ]);
        ksort($arguments);

        return $this->templating->render($template, $arguments);
    }

    private function templateExists(string $template): bool
    {
        return $this->cache['template_exists'][$template] ??= $this->templating->getLoader()->exists($template);
    }

    /**
     * Get body classes.
     */
    public function bodyClasses(
        ConfigurationModel $configuration,
        ?string $customBodyClass = null,
        mixed $entity = null,
    ): string {

        $classes = 'screen-'.$this->browserRuntime->screen().' browser-'.$this->browserRuntime->browser();
        if ($configuration->preloader) {
            $classes .= ' overflow-hidden';
        }
        if ($customBodyClass) {
            $classes .= ' '.$customBodyClass;
        }
        if (!$configuration->fullWidth) {
            $classes .= ' content-body-box';
        }
        if (!empty($_SERVER['HTTP_ACCEPT']) && preg_match('/image\/webp/', $_SERVER['HTTP_ACCEPT'])) {
            $classes .= ' webp-support';
        }
        if ($entity && method_exists($entity, 'getCategory') && $entity->getCategory()) {
            $classes .= ' body-'.$entity->getCategory()->getSlug();
        } elseif ($entity && property_exists($entity, 'category') && $entity->category) {
            $classes .= ' body-'.$entity->category->slug;
        }

        return $classes;
    }

    /**
     * Get Zone classes.
     */
    public function zoneClasses(mixed $zone, bool $defaultBg = true): string
    {
        $backgroundColor = $this->getValue($zone, 'backgroundColor');
        $zIndex = $this->getValue($zone, 'zIndex');
        $transition = $this->getValue($zone, 'transition');
        $position = $this->getValue($zone, 'position');
        $customClass = $this->customClasses($zone);
        $shadowClass = $this->shadowClasses($zone);
        $alignment = $this->getValue($zone, 'alignment');

        $class = $backgroundColor != ('' and 'transparent') && $this->getValue($zone, 'backgroundFullSize') ? ' '.$backgroundColor.' ' : '';
        $class .= ' position-'.$this->getValue($zone, 'position');
        $class .= $this->getHiddenClasses($zone);
        $class .= $customClass ? ' '.$customClass : '';
        $class .= $shadowClass ? ' '.$shadowClass : '';
        $class .= $this->getValue($zone, 'standardizeMedia') ? ' standardize-medias' : '';
        $class .= $this->getValue($zone, 'backgroundFixed') ? ' bg-fixed' : '';
        $class .= $this->getValue($zone, 'backgroundParallax') ? ' parallax' : '';
        $class .= $this->getValue($zone, 'fullSize') ? ' full-size' : ' container-size';
        $class .= $this->getValue($zone, 'colToEnd') ? ' d-flex align-items-end' : '';
        $class .= $this->getValue($zone, 'radius') ? ' radius' : '';
        $class .= $this->getValue($zone, 'colToRight') ? ' as-fluid-right w-100' : '';
        $class .= $zIndex ? ' z-index-'.$zIndex : '';
        $class .= $alignment ? ' text-'.$alignment.' ' : '';

        if (!empty($transition)) {
            $aosActive = 'mobile' !== $this->screen || self::AOS_ON_MOBILE;
            $class .= $this->getValue($transition, 'aosEffect') && $aosActive ? ' aos' : '';
            $class .= $this->getValue($transition, 'laxPreset') ? ' lax' : '';
        }

        if ($class && str_contains($class, 'video-bg')) {
            $class .= ' bg-primary';
        }

        if ($defaultBg && !str_contains($class, 'bg-')) {
            $class .= $position == 2 ? ' bg-beige' : ' bg-white';
        }

        $this->zoneClasses = preg_replace('/\s+/', ' ', trim($class));

        return $this->zoneClasses;
    }

    /**
     * Get Col classes by size and grid.
     */
    public function colClasses(mixed $col, mixed $zone, ?string $grid = null): string
    {
        $backgroundColorFullHeight = $this->getValue($col, 'backgroundFullHeight');
        $backgroundColor = $backgroundColorFullHeight ? $this->getValue($col, 'backgroundColor') : null;
        $zIndex = $this->getValue($col, 'zIndex');
        $customClass = $this->customClasses($col);
        $shadowClass = $this->shadowClasses($col);
        $alignment = $this->getValue($col, 'alignment');
        $transition = $this->getValue($col, 'transition');
        $backgroundFullSize = $this->getValue($col, 'backgroundFullSize');

        $class = $this->getValue($zone, 'standardizeElements') && 'mobile' !== $this->screen ? 'col-center col-12 col-md-6 col-lg' : $this->elementSizes($col, $grid);
        $class .= $this->getValue($col, 'endAlign') ? ' d-flex align-items-end' : '';
        $class .= $this->getValue($col, 'sticky') ? ' col-sticky' : '';
        $class .= $backgroundFullSize && $backgroundColor !== (null && 'transparent') ? ' '.$backgroundColor.' ' : '';
        $class .= $this->getValue($col, 'reverse') ? ' mobile-first' : '';
        $class .= $this->getValue($col, 'radius') ? ' radius' : '';
        $class .= $customClass ? ' '.$customClass : '';
        $class .= $shadowClass ? ' '.$shadowClass : '';
        $class .= $zIndex ? ' z-index-'.$zIndex : '';
        $class .= $alignment ? ' text-'.$alignment.' ' : '';
        $class .= $this->getHiddenClasses($col);
        $class .= $this->elementOrders($col);

        if (!empty($transition)) {
            $aosActive = 'mobile' !== $this->screen || self::AOS_ON_MOBILE;
            $class .= $this->getValue($transition, 'aosEffect') && $aosActive ? ' aos' : '';
            $class .= $this->getValue($transition, 'laxPreset') ? ' lax' : '';
            $transitionSlug = $this->getValue($transition, 'slug');
            $class .= str_contains($transitionSlug, '-parallax') ? ' '.$transitionSlug : '';
        }

        return preg_replace('/\s+/', ' ', trim($class));
    }

    /**
     * Get Block classes.
     */
    public function blockClasses(mixed $block, mixed $col, ?string $colClasses = null): string
    {
        $blockType = $this->getValue($block, 'blockType');
        $blockTypeSlug = $blockType ? $this->getValue($blockType, 'slug') : false;
        $transition = $this->getValue($block, 'transition');
        $backgroundColor = $this->getValue($block, 'backgroundColor');
        $backgroundColor = $backgroundColor !== (null && 'transparent') ? $backgroundColor : false;
        $backgroundFullSize = $this->getValue($block, 'backgroundFullSize');
        $haveBackground = $backgroundColor || $this->getValue($block, 'hexadecimalCode');
        $zIndex = $this->getValue($block, 'zIndex');
        $customClass = $this->customClasses($block);
        $shadowClass = $this->shadowClasses($block);

        $class = $blockTypeSlug ? 'layout-block-type-'.$blockTypeSlug : '';
        $class .= $this->getValue($block, 'verticalAlign') ? ' my-auto' : '';
        $class .= $this->getValue($block, 'endAlign') ? ' d-flex align-items-end' : '';
        $class .= $this->getValue($block, 'reverse') ? ' mobile-first' : '';
        $class .= $this->getValue($block, 'radius') ? ' radius' : '';
        $class .= $zIndex ? ' z-index-'.$zIndex : '';
        $class .= $customClass ? ' '.$customClass : '';
        $class .= $shadowClass ? ' '.$shadowClass : '';
        $class .= $backgroundFullSize && $backgroundColor ? ' '.$backgroundColor.' ' : '';
        $class .= $this->getAlignments($block);
        $class .= $this->getHiddenClasses($block);
        $class .= $this->getValue($col, 'standardizeElements') ? ' col-sm-12 col-md-6 col-lg' : $this->elementSizes($block);
        $class .= $this->elementOrders($block);

        if (!empty($transition)) {
            $aosActive = 'mobile' !== $this->screen || self::AOS_ON_MOBILE;
            $class .= $this->getValue($transition, 'aosEffect') && $aosActive ? ' aos' : '';
            $class .= $this->getValue($transition, 'laxPreset') ? ' lax' : '';
            $transitionSlug = $this->getValue($transition, 'slug');
            $class .= str_contains($transitionSlug, '-parallax') ? ' '.$transitionSlug : '';
        }

        $class .= $colClasses && str_contains($colClasses, 'col-full-size') ? ' h-100' : '';

        $paddings = !$haveBackground || $backgroundFullSize ? $this->paddings($block) : false;
        if ($paddings) {
            $class .= ' '.$paddings;
        }

        if ($this->zoneClasses && str_contains($this->zoneClasses, 'as-fluid-right')) {
            $excludes = ['core-action'];
            if (!in_array($blockTypeSlug, $excludes) && !str_contains($class, 'pe-')) {
                $class .= ' pe-3';
            }
        }

        return preg_replace('/\s+/', ' ', trim($class));
    }

    /**
     * Get Block alignment.
     */
    public function blockAlignment(mixed $block): string
    {
        $class = '';
        $elementsAlignment = $this->getValue($block, 'elementsAlignment');
        if ($elementsAlignment) {
            $class .= ' '.$elementsAlignment;
        }

        return rtrim($class);
    }

    /**
     * Get element orders.
     */
    public function elementOrders(mixed $element): string
    {
        /** Desktop */
        $desktopPosition = $this->getValue($element, 'position');
        $desktopOrder = 'order-xl-'.$desktopPosition;
        $class = $desktopOrder;

        /** Laptop */
        $laptopPosition = $this->getValue($element, 'laptopPosition');
        $laptopPosition = $laptopPosition ?: $desktopPosition;
        $laptopOrder = 'order-lg-'.$laptopPosition;
        if (!str_contains($class, $laptopOrder) && 'order-lg-' != $laptopOrder) {
            $class .= ' '.$laptopOrder;
        }

        /** Tablet */
        $tabletPosition = $this->getValue($element, 'tabletPosition');
        $tabletPosition = $tabletPosition ?: $laptopPosition;
        $tabletOrder = 'order-md-'.$tabletPosition;
        if (!str_contains($class, $tabletOrder) && 'order-md-' != $tabletOrder) {
            $class .= ' '.$tabletOrder;
        }

        /** Mobile */
        $mobilePosition = $this->getValue($element, 'mobilePosition');
        $mobilePosition = $mobilePosition ?: $tabletPosition;
        $mobileOrder = 'order-'.$mobilePosition;
        if (!str_contains($class, $mobileOrder) && 'order-' != $mobileOrder) {
            $class .= ' '.$mobileOrder;
        }

        if ($class === $desktopOrder) {
            $class = ' ';
        }

        return ' '.$class;
    }

    /**
     * Get element sizes.
     */
    public function elementSizes(mixed $element, $grid = null): string
    {
        /** Configure grid */
        $grids = [];
        $grids[6]['6-6'] = 'col-12 col-lg-6';
        $grids[3]['3-3-3-3'] = 'col-12 col-lg-6 col-xl-3';
        $grids[2]['2-2-2-2-2-2'] = 'col-6 col-xl-2';
        $grids[8]['2-8-2'] = 'col-12 col-xl-8';
        $grids[5]['1-5-1-4-1'] = 'col-12 col-lg-6 col-xl-5';
        $grids[4]['1-5-1-4-1'] = 'col-12 col-lg-6 col-xl-4';
        $grids[10]['1-10-1'] = 'col-12 col-xl-10';

        /** Default */
        $desktopDefaultSize = $this->getValue($element, 'size');
        $size = $desktopDefaultSize ?: 12;

        /** Mobile */
        $mobileSize = $this->getValue($element, 'mobileSize');
        $mobileSize = $mobileSize ?: 12;
        $mobileSizeClass = 'col-'.$mobileSize;

        /** Tablet */
        $tabletSize = $this->getValue($element, 'tabletSize');
        $tabletSizeClass = $tabletSize ? 'col-md-'.$tabletSize : 'col-md-'.$mobileSize;

        /** Tablet */
        $laptopSize = $this->getValue($element, 'laptopSize');
        $laptopSizeClass = $laptopSize ? 'col-lg-'.$laptopSize : 'col-lg-'.$size;

        /** Desktop */
        $desktopSizeClass = $laptopSize ? 'col-xl-'.$size : ($laptopSizeClass !== 'col-lg-'.$size ? 'col-lg-'.$size : '');

        return $grid && !empty($grids[$size][$grid]) && !$mobileSizeClass && !$tabletSizeClass && !$laptopSizeClass
            ? $grids[$size][$grid] : trim($mobileSizeClass.' '.$tabletSizeClass.' '.$laptopSizeClass.' '.$desktopSizeClass);
    }

    /**
     * Get effects attributes.
     *
     * @throws NonUniqueResultException
     */
    public function effectsAttrs(mixed $entity = null): string
    {
        $attributes = '';
        $transition = is_object($entity) && method_exists($entity, 'getTransition')
            ? $entity->getTransition() : (is_array($entity) && !empty($entity['transition']) ? $entity['transition'] : null);

        if ($transition) {
            $laxAttributes = $this->laxEffects($transition);
            if ($laxAttributes) {
                $attributes .= $laxAttributes;
            }

            $aosAttributes = $this->aosEffect($transition, $entity);
            $aosActive = 'mobile' !== $this->screen || self::AOS_ON_MOBILE;
            if (!$laxAttributes && $aosAttributes && $aosActive) {
                $attributes .= ' '.$aosAttributes;
            }

            $animateAttributes = $this->animateEffect($transition, $entity);
            if (!$laxAttributes && !$aosAttributes && $animateAttributes) {
                $attributes .= ' '.$animateAttributes;
            }
        }

        return rtrim($attributes);
    }

    /**
     * Get standardize Media[] height.
     */
    public function mediasSizes(WebsiteModel $website, Layout\Zone $zone, string $locale): array
    {
        $isSet = false;
        $width = 0;
        $height = $initHeight = 100000000000;
        $mediasInfos = [];

        $thumbRepository = $this->coreLocator->em()->getRepository(Media\ThumbAction::class);
        $thumbAction = $thumbRepository->findForEntity($website->entity, Layout\Block::class, null, null, 'media');
        $thumbConfiguration = $thumbAction ? $thumbAction['configuration'] : [];

        foreach ($zone->getCols() as $col) {
            foreach ($col->getBlocks() as $block) {
                foreach ($block->getMediaRelations() as $mediaRelation) {
                    if ($mediaRelation->getLocale() === $locale) {
                        $isSet = true;
                        $media = $mediaRelation->getMedia();
                        if ($media instanceof Media\Media && $media->getOriginalName()) {
                            $fileInfo = $this->coreLocator->fileInfo()->file($website, $media->getOriginalName());
                            if ($mediaRelation->getMaxHeight() > 0 && $mediaRelation->getMaxHeight() < $height) {
                                $height = $mediaRelation->getMaxHeight();
                            }
                            if ($fileInfo->getHeight() < $height) {
                                $height = $fileInfo->getHeight();
                            }
                            if ($thumbConfiguration) {
                                $thumb = null;
                                foreach ($media->getThumbs() as $mediaThumb) {
                                    if ($mediaThumb->getConfiguration() === $thumbConfiguration) {
                                        $thumb = $mediaThumb;
                                        break;
                                    }
                                }
                                if ($thumb instanceof Media\Thumb && $thumb->getHeight() < $height) {
                                    $height = $thumb->getHeight();
                                }
                            }
                        }
                    }
                }
            }
        }

        return [
            'width' => null,
            'height' => $isSet && $height !== $initHeight ? $height : null,
        ];
    }

    /**
     * Get Layout main title.
     *
     * @throws NonUniqueResultException|MappingException|QueryException|InvalidArgumentException
     */
    public function mainLayoutTitle(mixed $layout, ?string $locale = null, bool $all = false): mixed
    {
        $locale = $locale ?: $this->coreLocator->request()->getLocale();
        $layoutId = is_object($layout) ? $layout->getId() : $layout['id'];
        $cacheKey = 'layout-'.$layoutId.'-'.$locale.'-'.($all ? 'all' : 'one');

        // array_key_exists, not isset: a cached null must short-circuit too.
        if (array_key_exists($cacheKey, $this->cache['main_title'] ?? [])) {
            return $this->cache['main_title'][$cacheKey];
        }

        $repository = $this->coreLocator->em()->getRepository(Layout\Block::class);
        $block = $repository->findByBlockTypeAndLocaleLayout($layout, 'title', $locale, [
            'asThumb' => true,
            'haveContent' => true,
            'titleForce' => 1,
        ]);
        $intl = $block ? ViewModel::fromEntity($block, $this->coreLocator, ['disabledMedias' => true])->intl : null;
        $title = $intl && $intl->title ? $intl->title : null;

        if (!$title) {
            $title = $repository->findTitleByForceAndLocaleLayout($layout, $locale, 1, $all);
            if (!$title) {
                $title = $repository->findTitleByForceAndLocaleLayout($layout, $locale, 2, $all);
            }
        }

        return $this->cache['main_title'][$cacheKey] = $title;
    }

    /**
     * Batch-warm the mainLayoutTitle() cache so per-layout calls become cache hits.
     *
     * @throws NonUniqueResultException|MappingException|QueryException|InvalidArgumentException
     */
    public function preloadMainLayoutTitles(array $layouts, ?string $locale = null): void
    {
        $locale = $locale ?: $this->coreLocator->request()->getLocale();

        $pending = [];
        foreach ($layouts as $layout) {
            $layoutId = is_object($layout) ? $layout->getId() : ($layout['id'] ?? null);
            if (null === $layoutId || array_key_exists($layoutId.'-'.$locale.'-one', $this->cache['main_title'] ?? [])) {
                continue;
            }
            $pending[$layoutId] = $layout;
        }
        if (count($pending) < 2) {
            return;
        }

        $repository = $this->coreLocator->em()->getRepository(Layout\Block::class);
        $ids = array_keys($pending);

        $titles = [];
        $blocks = $repository->findByBlockTypeAndLayouts($ids, 'title', $locale, ['asThumb' => true, 'haveContent' => true, 'titleForce' => 1]);
        foreach ($ids as $layoutId) {
            $block = $blocks[$layoutId] ?? null;
            $intl = $block ? ViewModel::fromEntity($block, $this->coreLocator, ['disabledMedias' => true])->intl : null;
            $titles[$layoutId] = $intl && $intl->title ? $intl->title : null;
        }

        foreach ([1, 2] as $force) {
            $missing = array_keys(array_filter($titles, static fn ($title) => !$title));
            if (!$missing) {
                break;
            }
            $forced = $repository->findTitleByForceAndLayouts($missing, $locale, $force);
            foreach ($missing as $layoutId) {
                if (!empty($forced[$layoutId])) {
                    $titles[$layoutId] = $forced[$layoutId];
                }
            }
        }

        foreach ($ids as $layoutId) {
            $this->cache['main_title'][$layoutId.'-'.$locale.'-one'] = $titles[$layoutId] ?? null;
        }
    }

    /**
     * Get block type by Layout and slug.
     *
     * @throws NonUniqueResultException|MappingException
     */
    public function layoutBlockType(mixed $layout, ?string $slug = null, ?string $locale = null): mixed
    {
        $locale = $locale ?: $this->coreLocator->request()->getLocale();
        $layoutId = is_object($layout) ? $layout->getId() : $layout['id'];
        $cacheKey = $layoutId.'-'.$slug.'-'.$locale;

        if (isset($this->cache['block_type'][$cacheKey])) {
            return $this->cache['block_type'][$cacheKey];
        }

        $repository = $this->coreLocator->em()->getRepository(Layout\Block::class);
        $haveContent = 'text' === $slug;
        $blockType = null;

        if ('media' === $slug || 'text' === $slug || 'title' === $slug) {
            $blockType = $repository->findByBlockTypeAndLocaleLayout($layout, $slug, $locale, [
                'asThumb' => true,
                'haveContent' => 'media' !== $slug,
            ]);
        }

        if (!$blockType) {
            $blockType = $repository->findByBlockTypeAndLocaleLayout($layout, $slug, $locale, [
                'haveContent' => $haveContent,
            ]);
        }

        return $this->cache['block_type'][$cacheKey] = ($blockType ? BlockModel::fromEntity($blockType, $this->coreLocator) : null);
    }

    /**
     * Get a margin element.
     */
    public function margins(mixed $entity = null, ?string $orientation = 'all'): string
    {
        $classes = '';
        $asObject = is_object($entity);
        $isZone = $entity instanceof Layout\Zone || !$asObject && isset($entity['cols']);
        $isCol = $entity instanceof Layout\Col || !$asObject && isset($entity['blocks']);
        $elementName = $isZone ? 'zone' : ($isCol ? 'col' : 'block');
        $fullSize = ($isZone || $isCol) && $this->getValue($entity, 'fullSize');
        $sides = ['top', 'right', 'bottom', 'left'];
        $screens = ['' => '', 'laptop' => 'laptop', 'tablet' => 'tablet', 'mobile' => 'mobile'];

        foreach ($sides as $side) {
            $getter = 'margin'.ucfirst($side);
            $margin = $this->getValue($entity, $getter);
            if ($margin && str_contains($margin, 'neg') && !$classes) {
                $classes .= ' negative-margin';
            }
        }

        if ($fullSize) {
            $classes .= $elementName .'-full-size';
        } else {
            foreach ($screens as $screen => $suffix) {
                $suffix = $suffix ? '-'.$suffix : ' ';
                $marginRight = $this->getValue($entity, 'marginRight'.ucfirst($screen));
                $className = $marginRight.$suffix;
                if ($marginRight && !preg_match('/'.$className.'/', $classes)) {
                    $classes .= ' '.$className;
                }
                $marginLeft = $this->getValue($entity, 'marginLeft'.ucfirst($screen));
                $className = $marginLeft.$suffix;
                if ($marginLeft && !preg_match('/'.$className.'/', $classes)) {
                    $classes .= ' '.$className;
                }
            }
        }

        foreach ($screens as $screen => $suffix) {
            $suffix = $suffix ? '-'.$suffix : ' ';
            $marginTop = $this->getValue($entity, 'marginTop'.ucfirst($screen));
            $className = $marginTop.$suffix;
            if ($marginTop && !preg_match('/'.$className.'/', $classes)) {
                $classes .= ' '.$className;
            }
            $marginBottom = $this->getValue($entity, 'marginBottom'.ucfirst($screen));
            $className = $marginBottom.$suffix;
            if ($marginBottom && !preg_match('/'.$className.'/', $classes)) {
                $classes .= ' '.$className;
            }
        }

        return !$classes ? '' : preg_replace('/\s+/', ' ', $classes).' ';
    }

    /**
     * Get padding element.
     */
    public function paddings(mixed $entity = null, ?string $orientation = null): string
    {
        $classes = '';
        $asObject = is_object($entity);
        $asZone = $entity instanceof Layout\Zone || !$asObject && isset($entity['cols']);
        $asBlock = $asObject && $entity instanceof Layout\Block || !$asObject && !isset($entity['blockType']);
        $position = $asObject ? $entity->getPosition() : $entity['position'];
        $screens = ['' => '', 'laptop' => 'laptop', 'tablet' => 'tablet', 'mobile' => 'mobile'];
        $sizes = ['0', 'xs', 'sm', 'md', 'lg', 'xl', 'xxl'];

        if (!$orientation || 'horizontal' === $orientation) {
            foreach ($screens as $screen => $suffix) {
                $suffix = $suffix ? '-'.$suffix : '';
                $paddingRight = $this->getValue($entity, 'paddingRight'.ucfirst($screen));
                $className = $paddingRight ? ' '.$paddingRight.$suffix : (!$asBlock || $this->getValue($entity, 'size') < 12 ? ' pe-sm' : ' ');
                $className = !$paddingRight && str_contains($className, 'pe-') && ' pe-sm' === $className ? ' ' : $className;
                $className = $suffix && $paddingRight ? str_replace('pe-', 'pe'.$suffix.'-', $paddingRight) : $className;
                if (!preg_match('/'.$className.'/', $classes)) {
                    $classes .= ' '.$className;
                }
                $paddingLeft = $this->getValue($entity, 'paddingLeft'.ucfirst($screen));
                $className = $paddingLeft ? ' '.$paddingLeft.$suffix : (!$asBlock || $this->getValue($entity, 'size') < 12 ? ' ps-sm' : ' ');
                $className = !$paddingLeft && str_contains($className, 'ps-') && ' ps-sm' === $className ? ' ' : $className;
                $className = $suffix && $paddingLeft ? str_replace('ps-', 'ps'.$suffix.'-', $paddingLeft) : $className;
                if (!preg_match('/'.$className.'/', $classes)) {
                    $classes .= ' '.$className;
                }
            }
        }

        if (!$orientation || 'vertical' === $orientation) {
            foreach ($screens as $screen => $suffix) {
                $suffix = $suffix ? '-'.$suffix : '';
                $paddingTop = $this->getValue($entity, 'paddingTop'.ucfirst($screen));
                $className = $suffix && $paddingTop ? str_replace('pt-', 'pt'.$suffix.'-', $paddingTop) : $paddingTop.$suffix;
                if ($paddingTop && !preg_match('/'.$className.'/', $classes)) {
                    $classes .= ' '.$className;
                }
                $paddingBottom = $this->getValue($entity, 'paddingBottom'.ucfirst($screen));
                $className = $suffix && $paddingBottom ? str_replace('pb-', 'pb'.$suffix.'-', $paddingBottom) : $paddingBottom.$suffix;
                if ($paddingBottom && !preg_match('/'.$className.'/', $classes)) {
                    $classes .= ' '.$className;
                }
            }
        }

        if (str_contains($classes, 'pe-0') && str_contains($classes, 'pe-sm')) {
            $classes = str_replace('pe-sm', '', $classes);
        }

        if (str_contains($classes, 'ps-0') && str_contains($classes, 'ps-sm')) {
            $classes = str_replace('ps-sm', '', $classes);
        }

        $havePaddingTop = false;
        foreach ($sizes as $size) {
            if (str_contains($classes, 'pt-'.$size)) {
                $havePaddingTop = true;
                break;
            }
        }
        if ($position > 1 && $asZone && !$havePaddingTop) {
            $classes .= ' pt-lg';
        }

        $havePaddingBottom = false;
        foreach ($sizes as $size) {
            if (str_contains($classes, 'pb-'.$size)) {
                $havePaddingBottom = true;
                break;
            }
        }
        if ($position > 1 && $asZone && !$havePaddingBottom) {
            $classes .= ' pb-lg';
        }

        return !$classes ? '' : preg_replace('/\s+/', ' ', $classes).' ';
    }

    /**
     * Check if Zone display.
     */
    private function getZoneDisplay(mixed $zone): bool
    {
        if (!self::CHECK_ZONE_BLOCKS) {
            return true;
        }

        $elCount = 0;
        $asObject = $zone instanceof Layout\Zone;
        $cols = $asObject ? $zone->getCols() : $zone['cols'];
        foreach ($cols as $col) {
            $count = $asObject ? $col->getBlocks()->count() : count($col['blocks']);
            if ($count > 0) {
                ++$elCount;
                break;
            }
        }

        return $elCount > 0;
    }

    /**
     * To set alignments classes.
     */
    private function getAlignments(mixed $entity): string
    {
        $alignment = $this->getValue($entity, 'alignment');
        $alignmentLaptop = $this->getValue($entity, 'alignmentLaptop');
        $alignmentTablet = $this->getValue($entity, 'alignmentTablet');
        $alignmentMobile = $this->getValue($entity, 'alignmentMobile');

        $alignmentClasses = '';
        if ($alignment && !$alignmentLaptop && !$alignmentTablet && !$alignmentMobile) {
            $alignmentClasses = 'text-'.$alignment.' ';
        } elseif ($alignment && $alignmentLaptop && !$alignmentTablet && !$alignmentMobile) {
            $alignmentClasses = 'text-'.$alignment.' text-lg-'.$alignmentLaptop.' text-xl-'.$alignment;
        } elseif ($alignment && $alignmentLaptop && $alignmentTablet && !$alignmentMobile) {
            $alignmentClasses = 'text-'.$alignment.' text-md-'.$alignmentTablet.' text-lg-'.$alignmentLaptop.' text-xl-'.$alignment;
        } elseif (!$alignment && $alignmentLaptop && !$alignmentTablet && !$alignmentMobile) {
            $alignmentClasses = 'text-lg-'.$alignmentLaptop.' text-xl-start';
        } elseif (!$alignment && !$alignmentLaptop && $alignmentTablet && !$alignmentMobile) {
            $alignmentClasses = 'text-md-'.$alignmentTablet.' text-lg-start';
        } elseif (!$alignment && !$alignmentLaptop && !$alignmentTablet && $alignmentMobile) {
            $alignmentClasses = 'text-'.$alignmentMobile.' text-md-start';
        } elseif (!$alignment && $alignmentLaptop && !$alignmentTablet && $alignmentMobile) {
            $alignmentClasses = 'text-'.$alignmentMobile.' text-md-start text-lg-'.$alignmentLaptop.' text-xl-start';
        } elseif ($alignment && !$alignmentLaptop && $alignmentTablet && !$alignmentMobile) {
            $alignmentClasses = 'text-'.$alignment.' text-md-'.$alignmentTablet.' text-lg-'.$alignment;
        } elseif ($alignment && !$alignmentLaptop && !$alignmentTablet && $alignmentMobile) {
            $alignmentClasses = 'text-'.$alignmentMobile.' text-md-'.$alignment;
        } elseif (!$alignment && !$alignmentLaptop && $alignmentTablet && $alignmentMobile) {
            $alignmentClasses = 'text-'.$alignmentMobile.' text-md-'.$alignmentTablet.' text-lg-start';
        } elseif (!$alignment && !$alignmentLaptop && !$alignmentTablet && $alignmentMobile) {
            $alignmentClasses = 'text-'.$alignmentMobile.' text-md-start';
        } elseif ($alignment && $alignmentLaptop && $alignmentTablet && $alignmentMobile) {
            $alignmentClasses = 'text-'.$alignmentMobile.' text-md-'.$alignmentTablet.' text-lg-'.$alignmentLaptop.' text-xl-'.$alignment;
        }

        return ' '.$alignmentClasses.' ';
    }

    /**
     * To set hidden classes.
     */
    private function getHiddenClasses(mixed $entity): string
    {
        $isZone = $entity instanceof Layout\Zone || is_array($entity) && isset($entity['cols']);

        if ($this->getValue($entity, 'hide') || $isZone && !$this->getZoneDisplay($entity)) {
            return ' d-none';
        }

        $hideMobile = $this->getValue($entity, 'hideMobile');
        $hideTablet = $this->getValue($entity, 'hideTablet');
        $hideLaptop = $this->getValue($entity, 'hideLaptop');
        $hideDesktop = $this->getValue($entity, 'hideDesktop');

        if (!$hideMobile && !$hideTablet && !$hideLaptop && !$hideDesktop) {
            return '';
        }

        if ($hideMobile && $hideTablet && $hideLaptop && $hideDesktop) {
            return 'd-none ';
        }

        if ($hideMobile && !$hideTablet && !$hideLaptop && !$hideDesktop) {
            return ' d-none d-md-inline-flex ';
        }

        if (!$hideMobile && $hideTablet && !$hideLaptop && !$hideDesktop) {
            return ' d-md-none d-lg-inline-flex ';
        }

        if (!$hideMobile && !$hideTablet && $hideLaptop && !$hideDesktop) {
            return ' d-lg-none d-xl-inline-flex ';
        }

        if (!$hideMobile && !$hideTablet && !$hideLaptop && $hideDesktop) {
            return ' d-xl-none ';
        }

        if ($hideMobile && $hideTablet && !$hideLaptop && !$hideDesktop) {
            return ' d-none d-lg-inline-flex ';
        }

        if ($hideMobile && $hideTablet && $hideLaptop && !$hideDesktop) {
            return ' d-none d-xl-inline-flex ';
        }

        if (!$hideMobile && $hideTablet && $hideLaptop && !$hideDesktop) {
            return ' d-md-none d-xl-inline-flex ';
        }

        if (!$hideMobile && $hideTablet && $hideLaptop && $hideDesktop) {
            return ' d-md-none ';
        }

        if (!$hideMobile && !$hideTablet && $hideLaptop && $hideDesktop) {
            return ' d-lg-none ';
        }

        if ($hideMobile && !$hideTablet && $hideLaptop && !$hideDesktop) {
            return ' d-none d-md-inline-flex d-lg-flex d-xl-inline-flex ';
        }

        if ($hideMobile && !$hideTablet && $hideLaptop && $hideDesktop) {
            return ' d-none d-md-inline-flex d-lg-flex ';
        }

        if ($hideMobile && !$hideTablet && !$hideLaptop && $hideDesktop) {
            return ' d-none d-md-inline-flex d-xl-flex ';
        }

        if ($hideMobile && $hideTablet && $hideLaptop && !$hideDesktop) {
            return ' d-none d-xl-inline-flex ';
        }

        return '';
    }

    /**
     * Get AOS effect attributes.
     */
    private function aosEffect(mixed $transition = null, mixed $entity = null): string
    {
        $attributes = '';
        $asObject = is_object($entity);
        $aosActive = 'mobile' !== $this->screen || self::AOS_ON_MOBILE;
        if ($aosActive && $transition && !empty($entity)) {
            $animation = $this->getValue($transition, 'aosEffect');
            if ($animation) {
                $attributes = 'data-aos="'.$animation.'"';
                $durationExist = $asObject && method_exists($entity, 'getDuration') || is_array($entity) && !empty($entity['duration']);
                $duration = $durationExist && $this->getValue($entity, 'duration') > 0 ? $this->getValue($entity, 'duration')
                    : ($this->getValue($transition, 'duration') > 0 ? $this->getValue($transition, 'duration') : null);
                if ($duration) {
                    $attributes .= ' data-aos-duration="'.$duration.'"';
                }

                $delayExist = $asObject && method_exists($entity, 'getDelay') || is_array($entity) && !empty($entity['delay']);
                $delay = $delayExist && $this->getValue($entity, 'delay') > 0 ? $this->getValue($entity, 'delay')
                    : ($this->getValue($transition, 'delay') > 0 ? $this->getValue($transition, 'delay') : null);
                if ($delay) {
                    $attributes .= ' data-aos-delay="'.$delay.'"';
                }
            }
        }

        return rtrim($attributes);
    }

    /**
     * Get ANIMATE CSS effect attributes.
     */
    private function animateEffect(mixed $transition = null, mixed $entity = null): string
    {
        $attributes = '';
        if ($transition && !empty($entity)) {
            $animation = $this->getValue($transition, 'animateEffect');
            if ($animation) {
                $matches = explode('-', $animation);
                $attributes = 'data-animation="'.$matches[0].'" data-'.end($matches).'="1"';
                $delay = $transition->getDelay() ? $transition->getDelay() : 1000;
                $attributes .= ' data-delay="'.$delay.'"';
            }
        }

        return rtrim($attributes);
    }

    /**
     * Get Lax effects attributes.
     */
    private function laxEffects(mixed $transition = null): ?string
    {
        $attributes = '';
        $effects = $transition ? $this->getValue($transition, 'laxPreset') : [];
        foreach ($effects as $effect) {
            $attributes .= $effect.' ';
        }

        return $attributes ? 'data-lax-anchor="self" data-lax-preset="'.rtrim($attributes).'"' : null;
    }

    /**
     * Get transition attributes.
     */
    public function transitionsAttributes(array $transitionsDb = [], array $transitions = []): array
    {
        $response = [];
        foreach ($transitions as $property => $slug) {
            $response[$property] = $this->transitionAttributes($slug, $transitionsDb);
        }
        $main = !empty($response[array_key_first($response)]) ? $response[array_key_first($response)] : null;
        foreach ($response as $property => $transition) {
            if (!$transition['display'] && !empty($main)) {
                $response[$property] = $main;
            }
        }

        return $response;
    }

    /**
     * Get transition attributes.
     */
    public function transitionAttributes(?string $slug = null, ?array $transitions = []): array
    {
        $attributes = [];
        $attributes['class'] = '';
        $attributes['attr'] = '';

        if ($slug && !empty($transitions[$slug])) {
            $transition = $transitions[$slug];

            /* AOS */
            if ($transition->aosEffect) {
                $attributes['attr'] .= ' data-aos="'.$transition->aosEffect.'" data-aos-once="false"';
            }
            if ($transition->aosEffect && $transition->delay) {
                $attributes['attr'] .= ' data-aos-delay="'.$transition->delay.'"';
            }
            if ($transition->aosEffect && $transition->offsetData) {
                $attributes['attr'] .= ' data-aos-offset="'.$transition->offsetData.'"';
            }

            /* Lax */
            if ($transition->laxPreset || $transition->parameters) {
                $attributes['class'] .= ' lax';
                $attributes['attr'] .= ' data-lax-anchor="self"';
            }
            if ($transition->laxPreset) {
                $preset = '';
                foreach ($transition->laxPreset as $effect) {
                    $preset .= ' '.$effect;
                }
                $attributes['attr'] .= ' data-lax-preset="'.trim($preset).'"';
            }
            if ($transition->parameters) {
                $attributes['attr'] .= $transition->parameters;
            }
        }

        $attributes['display'] = !empty($attributes['class']) || !empty($attributes['attr']);

        return $attributes;
    }

    /**
     * Get Entity value.
     */
    private function getValue(mixed $entity, string $property): mixed
    {
        if (is_object($entity)) {
            $getter = method_exists($entity, 'get'.ucfirst($property)) ? 'get'.ucfirst($property) : 'is'.ucfirst($property);
            if (method_exists($entity, $getter)) {
                return $entity->$getter();
            }

            return null;
        }

        return !isset($entity[$property]) ? null : $entity[$property];
    }

    /**
     * Get custom classes.
     */
    private function customClasses(mixed $entity): string
    {
        $prefix = $entity instanceof Layout\Zone ? 'zone' : ($entity instanceof Layout\Col ? 'col' : 'block');
        $customClass = '';
        $customClasses = $this->getValue($entity, 'customClass');
        $customClassMatches = $customClasses ? explode(' ', $customClasses) : [];
        foreach ($customClassMatches as $customClassMatch) {
            if ($customClassMatch) {
                $customClassMatch = str_replace('blue-light', 'info-light', $customClassMatch);
                $customClassMatch = str_replace('red', 'secondary', $customClassMatch);
                $customClassMatch = str_replace('blue', 'primary', $customClassMatch);
                $customClassMatch = str_replace('green', 'success', $customClassMatch);
                $customClassMatch = str_replace('gray', 'light', $customClassMatch);
                $customClass .= ' '.$prefix.'-'.$customClassMatch;
            }
        }

        if ($customClass && str_contains($customClass, 'full-size-stripes-top')) {
            $customClass .= ' '.$prefix.'-full-size-stripes-top '.$customClass;
        }

        return $customClass;
    }

    /**
     * Get shadow classes.
     */
    private function shadowClasses(mixed $entity): string
    {
        $shadowClasses = '';
        $prefix = $entity instanceof Layout\Zone ? 'zone' : ($entity instanceof Layout\Col ? 'col' : 'block');

        $shadowClass = $this->getValue($entity, 'shadow');
        if ($shadowClass) {
            $matches = explode('-', $shadowClass);
            $shadowClasses .= 'shadow-box shadow-'.$matches[1].' '.$prefix.'-'.$shadowClass;
        }

        $shadowClass = $this->getValue($entity, 'shadowMobile');
        if ($shadowClass) {
            $matches = explode('-', $shadowClass);
            if (!str_contains($shadowClasses, 'shadow-box')) {
                $shadowClasses .= ' shadow-box';
            }
            $shadowClasses .= ' shadow-'.$matches[1].'-mobile '.$prefix.'-'.$shadowClass.'-mobile';
        }

        return $shadowClasses;
    }
}
