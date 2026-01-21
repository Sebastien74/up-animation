<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Layout;
use App\Entity\Seo\Url;
use App\Model\Layout\BlockModel;
use App\Service\Core\Urlizer;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query\QueryException;
use Exception;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\Link;

/**
 * ViewModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class ViewModel extends BaseModel
{
    private static array $cache = [];

    /**
     * ViewModel constructor.
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $slug = null,
        public readonly ?string $adminName = null,
        public readonly mixed $entity = null,
        public readonly ?string $route = null,
        public readonly ?string $url = null,
        public readonly ?bool $online = null,
        public readonly ?object $urlEntity = null,
        public readonly ?string $urlCode = null,
        public readonly ?string $urlIndex = null,
        public readonly ?array $interface = null,
        public readonly ?string $interfaceName = null,
        public readonly mixed $category = null,
        public readonly ?string $categorySlug = null,
        public readonly ?iterable $categories = null,
        public readonly ?IntlModel $intl = null,
        public readonly ?string $mainTitle = null,
        public readonly ?object $intlCard = null,
        public readonly ?bool $haveContent = null,
        public readonly ?bool $pastDate = null,
        public readonly ?\DateTime $date = null,
        public readonly array $dates = [],
        public readonly ?string $formatDate = null,
        public readonly ?string $author = null,
        public readonly ?array $medias = [],
        public readonly ?object $mainMedia = null,
        public readonly ?MediaModel $mainImage = null,
        public readonly ?array $mediasWithoutMain = null,
        public readonly bool $haveMedias = false,
        public readonly bool $haveMainMedia = false,
        public readonly ?object $headerMedia = null,
        public readonly ?array $videos = null,
        public readonly bool $haveVideos = false,
        public readonly object|bool $mainVideo = false,
        public readonly ?array $files = null,
        public readonly bool $haveFiles = false,
        public readonly ?object $mainFile = null,
        public readonly ?string $pictogram = null,
        public readonly ?bool $showGdprVideoBtn = null,
        public readonly ?array $mediasAndVideos = null,
        public readonly ?bool $mainMediaInHeader = null,
        public readonly ?bool $haveLayout = null,
        public readonly ?object $layout = null,
        public readonly ?bool $haveStickyCol = null,
        public readonly ?bool $asCustomLayout = null,
        public readonly ?bool $showImage = null,
        public readonly ?bool $showTitle = null,
        public readonly ?bool $showSubTitle = null,
        public readonly ?bool $showCategory = null,
        public readonly ?bool $showIntro = null,
        public readonly ?bool $showBody = null,
        public readonly ?bool $showDate = null,
        public readonly ?bool $showLinkCard = null,
        public readonly ?int $position = null,
        public readonly ?string $template = null,
        public readonly ?array $preloadFiles = [],
    ) {
    }

    /**
     * fromEntity.
     *
     * @throws NonUniqueResultException|MappingException|QueryException|Exception
     */
    public static function fromEntity(mixed $entity, CoreLocatorInterface $coreLocator, array $options = []): self
    {
        $entity = $entity && property_exists($entity, 'entity') && !method_exists($entity, 'getEntity') ? $entity->entity : $entity;
        $entitiesIds = !empty($options['entitiesIds']) ? $options['entitiesIds'] : [];

        if ($entity && method_exists($entity, 'getId')) {
            $entityId = $entity->getId();
            $classname = get_class($entity);
            $cacheKey = $classname . '-' . $entityId . '-' . md5(serialize($options));
            if (isset(self::$cache['view_model'][$cacheKey])) {
                return self::$cache['view_model'][$cacheKey];
            }
        }

        self::setLocator($coreLocator);
        if ($entity) {
            self::$coreLocator->interfaceHelper()->setInterface(get_class($entity));
        }

        $titleInfos = isset($options['titleInfos']) && $options['titleInfos'];
        $disabledLayout = isset($options['disabledLayout']) && $options['disabledLayout'];
        $disabledIntl = isset($options['disabledIntl']) && $options['disabledIntl'];
        $disabledMedias = isset($options['disabledMedias']) && $options['disabledMedias'];
        $disabledUrl = isset($options['disabledUrl']) && $options['disabledUrl'];
        $disabledCategories = isset($options['disabledCategories']) && $options['disabledCategories'];
        $disabledCategory = isset($options['disabledCategory']) && $options['disabledCategory'];
        $interface = $coreLocator->interfaceHelper()->getInterface();
        $listingClass = !empty($interface['listingClass']) ? $interface['listingClass'] : null;
        $configEntity = !empty($options['configEntity']) ? $options['configEntity'] : null;
        $configFields = self::getContent('fields', $configEntity, false, true);
        $urlsIndex = !empty($options['urlsIndex']) ? $options['urlsIndex'] : ($listingClass ? self::$coreLocator->listingService()->indexesPages($entity, self::$coreLocator->locale(), $listingClass, get_class($entity)) : []);
        $locale = !empty($options['locale']) ? $options['locale'] : self::$coreLocator->locale();
        $intl = $entitiesIds ? IntlModel::fromEntities($entity, $coreLocator, $options['entitiesIds'], $locale) : [];
        $intl = $intl ?: (!$disabledIntl ? IntlModel::fromEntity($entity, $coreLocator, false, $options) : null);
        $intlVideo = !$disabledIntl && $intl && $intl->video ? (object) ['type' => 'video', 'videoLink' => $intl->video, 'path' => $intl->video, 'locale' => $intl->locale] : null;
        $haveIntlVideo = !empty($intlVideo);
        $medias = $entitiesIds ? MediasModel::fromEntities($entity, $coreLocator, $options['entitiesIds']) : [];
        $medias = $medias ?: (!$disabledMedias ? MediasModel::fromEntity($entity, $coreLocator, $locale, false, $options) : null);
        $mediasAndVideos = !$disabledMedias && $medias && $medias->mediasAndVideos ? $medias->mediasAndVideos : [];
        $categories = !$disabledCategories ? self::getContent('categories', $entity, false, true, true) : [];
        $category = !$disabledCategory ? self::category($entity) : null;
        $category = $category ?: (!empty($categories) ? $categories[0] : null);
        $layout = !$disabledLayout ? self::layout($entity, $interface, $category) : null;
        $url = !$disabledUrl ? self::url($entity, $interface, $locale, $urlsIndex, $intl) : (object) [];
        $preloadFiles = !$disabledLayout ? self::preload($entity, $layout) : [];
        $date = self::date($entity);
        $dates = self::dates($entity);
        $haveVideos = (!$disabledMedias && $medias && !empty($medias->videos)) || $haveIntlVideo;
        $titleInfos = $titleInfos && $layout ? self::titleInfos($medias, $layout->entity) : false;

        $viewModel = new self(
            id: self::getContent('id', $entity),
            slug: self::getContent('slug', $entity),
            adminName: self::getContent('adminName', $entity),
            entity: $entity,
            route: self::$coreLocator->request() ? self::$coreLocator->request()->attributes->get('_route') : null,
            url: !$disabledUrl && $url ? $url->path : null,
            online: !$disabledUrl && $url ? $url->online : null,
            urlEntity: !$disabledUrl && $url ? $url->entity : null,
            urlCode: !$disabledUrl && $url ? $url->code : null,
            urlIndex: $entity && !empty($urlsIndex[$entity->getId()]) ? $urlsIndex[$entity->getId()] : (self::$coreLocator->request() ? self::$coreLocator->request()->attributes->get('pageUrl') : null),
            interface: $interface,
            interfaceName: !empty($interface['name']) ? $interface['name'] : null,
            category: $category,
            categorySlug: $category ? self::getContent('slug', $category) : null,
            categories: $categories,
            intl: $intl,
            mainTitle: $titleInfos ? $titleInfos->title : null,
            intlCard: !$disabledLayout && $layout ? self::intlCard($layout, $locale, $intl, $medias) : null,
            haveContent: $intl && ($intl->body || $intl->introduction),
            pastDate: $dates['startDate'] && $dates['startDate'] <= new \DateTime('now', new \DateTimeZone('Europe/Paris')),
            date: $date,
            dates: $dates,
            formatDate: self::getContent('formatDate', $entity),
            author: self::getContent('author', $entity),
            medias: !$disabledMedias && $medias ? $medias->list : [],
            mainMedia: !$disabledMedias && $medias ? $medias->main : null,
            mainImage: $titleInfos ? $titleInfos->media : null,
            mediasWithoutMain: !$disabledMedias && $medias ? $medias->withoutMain : [],
            haveMedias: !$disabledMedias && $medias ? $medias->haveMedias : false,
            haveMainMedia: !$disabledMedias && $medias ? $medias->haveMain : false,
            headerMedia: !$disabledMedias && $medias ? $medias->header : null,
            videos: !$disabledMedias && $medias && $medias->videos && !$intlVideo ? $medias->videos : ($intlVideo && !$disabledMedias && $medias && $medias->videos ? array_merge([$intlVideo], $medias->videos) : ($haveIntlVideo ? [$intlVideo] : [])),
            haveVideos: $haveVideos,
            mainVideo: !$disabledMedias && $medias && $medias->mainVideo ? $medias->mainVideo : ($haveIntlVideo ? $intlVideo : false),
            files: !$disabledMedias && $medias && $medias->files ? $medias->files : [],
            haveFiles: !$disabledMedias && $medias ? $medias->haveFiles : false,
            mainFile: !$disabledMedias && $medias && $medias->mainFile ? $medias->mainFile : (object) [],
            pictogram: self::getContent('pictogram', $entity),
            showGdprVideoBtn: $haveVideos && $medias && self::gdprVideo($medias->videos),
            mediasAndVideos: $mediasAndVideos && !$intlVideo ? $mediasAndVideos : ($intlVideo && $mediasAndVideos ? array_merge([$intlVideo], $mediasAndVideos) : ($intlVideo ? [$intlVideo] : [])),
            mainMediaInHeader: self::mainMediaInHeader($entity, $category, $layout) || self::mainMediaInHeader($category, $category, $layout),
            haveLayout: $layout ? $layout->haveLayout : false,
            layout: $layout ? $layout->entity : null,
            haveStickyCol: $layout ? $layout->haveStickyCol : false,
            asCustomLayout: $layout ? $layout->asCustom : false,
            showImage: in_array('image', $configFields),
            showTitle: in_array('title', $configFields),
            showSubTitle: in_array('sub-title', $configFields),
            showCategory: in_array('category', $configFields),
            showIntro: in_array('introduction', $configFields),
            showBody: in_array('body', $configFields),
            showDate: in_array('date', $configFields),
            showLinkCard: in_array('card-link', $configFields),
            position: self::getContent('position', $entity),
            template: self::getContent('template', $entity),
            preloadFiles: $preloadFiles,
        );

        if (isset($cacheKey)) {
            self::$cache['view_model'][$cacheKey] = $viewModel;
        }

        return $viewModel;
    }

    /**
     * Get url.
     */
    public static function url(mixed $entity, array $interface = [], ?string $locale = null, array $urlsIndex = [], ?IntlModel $intl = null): bool|object
    {
        if (!$entity) {
            return false;
        }

        $locale = $locale ?: self::$coreLocator->locale();
        $request = self::$coreLocator->request();
        $path = null;
        $url = null;

        if ($entity->getId() && !empty(self::$cache[get_class($entity)]['url'][$entity->getId()])) {
            return self::$cache[get_class($entity)]['url'][$entity->getId()];
        }

        if (method_exists($entity, 'getUrls')) {
            $urlCode = null;
            foreach ($entity->getUrls() as $url) {
                if ($locale === $url->getLocale()) {
                    $urlCode = $url->getCode();
                    break;
                }
            }
            $urlCode = self::fixUrl($url, $urlCode, $intl);
            if ($entity instanceof Layout\Page) {
                $path = self::$coreLocator->router()->generate('front_index', ['url' => $entity->isAsIndex() ? null : $urlCode], 0);
            } else {
                $pageUrl = array_key_exists($entity->getId(), $urlsIndex) ? $urlsIndex[$entity->getId()] : ($request && $request->get('pageUrl') ? $request->get('pageUrl')
                    : ($request ? trim($request->getPathInfo(), '/') : null));
                $path = $pageUrl && $urlCode && !str_contains($pageUrl, '/') && self::$coreLocator->checkRoute('front_'.$interface['name'].'_view.'.$locale)
                    ? self::$coreLocator->router()->generate('front_'.$interface['name'].'_view', ['pageUrl' => $pageUrl, 'url' => $urlCode], 0) : null;
                if (!$path) {
                    $existingOnlyRoute = self::$coreLocator->checkRoute('front_'.$interface['name'].'_view_only.'.$locale);
                    $path = $existingOnlyRoute && $urlCode ? self::$coreLocator->router()->generate('front_'.$interface['name'].'_view_only', ['url' => $urlCode], 0) : null;
                }
            }
        }

        self::$cache[get_class($entity)]['url'][$entity->getId()] = (object) [
            'path' => $path ?: ($request && $request->get('_route') && str_contains($request->get('_route'), '_view') ? $request?->getUri() : null),
            'entity' => $url,
            'code' => $url ? $url->getCode() : null,
            'online' => ($url && $url->isOnline()) || (method_exists($entity, 'isInfill') && $entity->isInfill()),
        ];

        return self::$cache[get_class($entity)]['url'][$entity->getId()];
    }

    /**
     * Get Layout.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private static function layout(?object $entity, array $interface, ?object $category = null, bool $callback = false): object
    {
        $layout = $entity instanceof Layout\Layout ? $entity : self::getLayout($entity);
        $zones = self::getContent('zones', $layout);
        $haveZones = $zones instanceof PersistentCollection && !$zones->isEmpty();
        $masterField = !empty($interface['masterField']) ? $interface['masterField'] : null;
        $masterFieldGetter = $masterField && 'website' !== $masterField ? 'get'.ucfirst($masterField) : null;

        if (!$callback && !$haveZones && $category && method_exists($category->entity, 'getLayout') && method_exists($category->entity, 'getCategoryTemplate') && $category->entity->getCategoryTemplate()) {
            return self::$cache['layout'][get_class($category->entity->getCategoryTemplate())][$category->entity->getCategoryTemplate()->getId()] ?? self::layout($category->entity->getCategoryTemplate(), $interface, null, true);
        }

        if (!$callback && !$haveZones && $category) {
            if (empty(self::$cache['layout'][get_class($category->entity)][$category->id])) {
                self::$cache['layout'][get_class($category->entity)][$category->id] = self::layout($category->entity, $interface, null, true);
            }

            return self::$cache['layout'][get_class($category->entity)][$category->id];
        }

        if (!$callback && !$haveZones && $masterFieldGetter && method_exists($entity, $masterFieldGetter) && $entity->$masterFieldGetter() && method_exists($entity->$masterFieldGetter(), 'getLayout')) {
            return self::$cache['layout'][get_class($entity->$masterFieldGetter())][$entity->$masterFieldGetter()->getId()] ?? self::layout($entity->$masterFieldGetter(), $interface, null, true);
        }

        $haveStickyCol = false;
        if ($haveZones) {
            foreach ($zones as $zone) {
                foreach ($zone->getCols() as $col) {
                    if ($col->isSticky()) {
                        $haveStickyCol = true;
                        break;
                    }
                }
            }
        }

        return (object) [
            'entity' => $haveZones ? $layout : null,
            'haveLayout' => $haveZones,
            'haveStickyCol' => $haveStickyCol,
            'asCustom' => $entity && method_exists($entity, 'isCustomLayout') && $haveZones ? $entity->isCustomLayout() : false,
        ];
    }

    /**
     * Get category.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    private static function category(mixed $entity = null): ?object
    {
        $stop = !empty($options['stop']) || !is_object($entity);
        if ($stop) {
            return null;
        }

        $category = self::getContent('category', $entity);
        $category = !$category ? self::getContent('mainCategory', $entity) : $category;

        if ($category) {
            if (isset(self::$cache['category'][get_class($category)][$category->getId()])) {
                return self::$cache['category'][get_class($category)][$category->getId()];
            }
            $qb = self::$coreLocator->em()->getRepository(get_class($category))
                ->createQueryBuilder('c')
                ->andWhere('c.id =  :id')
                ->setParameter('id', $category->getId());
            if (method_exists($category, 'getIntls')) {
                $qb->leftJoin('c.intls', 'i')
                    ->addSelect('i');
            }
            if (method_exists($category, 'getMediaRelations')) {
                $qb->leftJoin('c.mediaRelations', 'mr')
                    ->addSelect('mr');
            }
            $category = $qb->getQuery()->getOneOrNullResult();
            if (is_object($category)) {
                self::$cache['category'][get_class($category)][$category->getId()] = ViewModel::fromEntity($category, self::$coreLocator, ['stop' => true]);

                return self::$cache['category'][get_class($category)][$category->getId()];
            }
        }

        return $category;
    }

    /**
     * intlCard.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private static function intlCard(object $layout, string $locale, ?IntlModel $intl = null, ?MediasModel $medias = null): ?object
    {
        $title = $intl?->title;
        $titleForce = $intl?->titleForce;
        $subTitle = $intl?->subTitle;
        $intro = $intl?->introduction;
        $body = $intl?->body;

        if ($layout->asCustom) {
            $repository = self::$coreLocator->em()->getRepository(Layout\Block::class);
            $blockTitle = $repository->findByBlockTypeAndLocaleLayout($layout->entity, 'title', $locale, [
                'asThumb' => true,
                'haveContent' => true,
            ]);
            $intTitle = $blockTitle ? IntlModel::fromEntity($blockTitle, self::$coreLocator, false) : $intl;
            $title = $intTitle->title ?: $title;
            $subTitle = $intTitle->subTitle ?: $subTitle;
            $blockText = $repository->findByBlockTypeAndLocaleLayout($layout->entity, 'text', $locale, [
                'asThumb' => true,
                'haveContent' => true,
            ]);
            $intl = $blockText ? IntlModel::fromEntity($blockText, self::$coreLocator, false) : $intl;
            $intro = $intl && $intl->introduction ? $intl->introduction : $intro;
            $body = $intl && $intl->body ? $intl->body : $body;
            $blockMedia = $repository->findByBlockTypeAndLocaleLayout($layout->entity, 'media', $locale, [
                'asThumb' => true,
                'haveContent' => false,
            ]);
            $medias = $blockMedia ? MediasModel::fromEntity($blockMedia, self::$coreLocator, self::$coreLocator->locale(), false) : $medias;
        }

        return (object) [
            'title' => $title,
            'titleForce' => $titleForce,
            'subTitle' => $subTitle,
            'text' => $intro ?: $body,
            'intro' => $intro,
            'body' => $body,
            'medias' => $medias ? $medias->list : [],
            'mainMedia' => $medias?->main,
            'headerMedia' => $medias?->header,
        ];
    }

    /**
     * To get mainMediaInHeader.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private static function mainMediaInHeader(mixed $entity = null, mixed $category = null, mixed $layout = null): ?bool
    {
        if ($layout && $layout->haveLayout && $layout->asCustom) {
            return self::getContent('mainMediaInHeader', $entity, true);
        } elseif ($category) {
            return self::getContent('mainMediaInHeader', $category, true);
        }

        return false;
    }

    /**
     * To check whether cookies should be enabled.
     */
    private static function gdprVideo(array $videos): bool
    {
        $enabled = false;
        $platforms = ['youtube', 'vimeo', 'dailymotion'];
        $cookiesRequest = self::$coreLocator->request()->cookies->get('axeptio_cookies');

        if (!empty($cookiesRequest)) {
            $serializer = new Serializer([new GetSetMethodNormalizer()], ['json' => new JsonEncoder()]);
            $cookies = $serializer->decode($cookiesRequest, 'json');
            foreach ($videos as $video) {
                foreach ($platforms as $platform) {
                    if ($video->videoLink && str_contains($video->videoLink, $platform) && is_array($cookies) && isset($cookies[$platform]) && $cookies[$platform]) {
                        $enabled = false;
                    } elseif ($video->videoLink && str_contains($video->videoLink, $platform)) {
                        $enabled = true;
                    }
                }
            }
        }

        return $enabled;
    }

    /**
     * To get date.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private static function date(mixed $entity): ?object
    {
        $publicationDate = self::getContent('publicationDate', $entity);
        $publicationStart = self::getContent('publicationStart', $entity);
        $startDate = self::getContent('startDate', $entity);

        return $startDate ?: ($publicationDate ?: $publicationStart);
    }

    /**
     * To get dates.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private static function dates(mixed $entity): array
    {
        return [
            'publicationDate' => self::getContent('publicationDate', $entity),
            'publicationStart' => self::getContent('publicationStart', $entity),
            'publicationEnd' => self::getContent('publicationEnd', $entity),
            'startDate' => self::getContent('startDate', $entity),
            'endDate' => self::getContent('endDate', $entity),
        ];
    }

    /**
     * To preload resources.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private static function preload(mixed $entity, mixed $layout): array
    {
        if (!empty(self::$cache['preloads'])) {
            return self::$cache['preloads'];
        }

        $files = $videosFiles = [];
        $route = self::$coreLocator->request() ? self::$coreLocator->request()->attributes->get('_route') : null;
        $preload = ('front_index' === $route && $entity instanceof Layout\Page) || ($route && str_contains($route, '_view'));

        if ($preload && is_object($layout) && property_exists($layout, 'entity')) {

            $layout = $layout->entity;
            $firstZone = $layout ? $layout->getZones()->first() : null;
            $firstCol = $firstZone instanceof Layout\Zone ? $firstZone->getCols()->first() : null;
            $firstBlock = $firstCol instanceof Layout\Col ? $firstCol->getBlocks()->first() : null;
            if ($firstBlock instanceof Layout\Block) {
                if ($firstBlock->getBlockType() && 'core-action' === $firstBlock->getBlockType()->getSlug()) {
                    foreach ($firstBlock->getActionIntls() as $intl) {
                        if (self::$coreLocator->locale() === $intl->getLocale()) {
                            $entity = self::$coreLocator->emQuery()->findOneBy($firstBlock->getAction()->getEntity(), is_numeric($intl->getActionFilter()) ? 'id' : 'slug', $intl->getActionFilter());
                            $medias = $entity ? MediasModel::fromEntity($entity, self::$coreLocator) : [];
                            $mediasList = $entity ? $medias->list : [];
                            if ($medias instanceof MediasModel && $medias->videoAsFirst && $medias->mainVideo) {
                                $video = $medias->mainVideo;
                                if ($video->videoLink) {
                                    $videosFiles[] = $video->videoLink;
                                    $linkProvider = self::$coreLocator->request()->attributes->get('_links', new GenericLinkProvider());
                                    self::$coreLocator->request()->attributes->set('_links', $linkProvider->withLink(
                                        (new Link('preload', $video->videoLink))->withAttribute('as', 'video')
                                    ));
                                }
                            } elseif (!empty($mediasList[0])) {
                                $thumbConfiguration = self::$coreLocator->thumbService()->thumbConfiguration(self::$coreLocator->website(), $firstBlock->getAction()->getEntity(), $firstBlock->getAction()->getAction(), $entity);
                                if (!$thumbConfiguration) {
                                    $thumbConfiguration = self::$coreLocator->thumbService()->thumbConfiguration(self::$coreLocator->website(), $firstBlock->getAction()->getEntity(), $firstBlock->getAction()->getAction());
                                }
                                $files = self::$coreLocator->thumbService()->preload($mediasList[0], $thumbConfiguration);
                            }
                        }
                    }
                } else {
                    $block = BlockModel::fromEntity($firstBlock, self::$coreLocator);
                    if ($block->media) {
                        $thumbConfiguration = self::$coreLocator->thumbService()->thumbConfiguration(self::$coreLocator->website(), Layout\Block::class, 'block', $firstBlock->getId(), $firstBlock->getBlockType()->getSlug());
                        if ($block->media->media) {
                            $files = self::$coreLocator->thumbService()->preload($block->media, $thumbConfiguration);
                        }
                    }
                }
            }
        }

        self::$cache['preloads'] = array_merge(['images' => $files], ['videos' => $videosFiles]);

        return self::$cache['preloads'];
    }

    /**
     * @throws NonUniqueResultException|MappingException
     */
    public static function titleInfos(?MediasModel $medias, ?Layout\Layout $layout): object
    {
        $block = self::$coreLocator->em()->getRepository(Layout\Block::class)->findHeaderByLayout($layout);
        $block = $block ? BlockModel::fromEntity($block, self::$coreLocator) : false;
        $media = $medias?->main;
        $media = !$media && $block && $block->haveMedia ? $block->media : $media;

        return (object) [
            'title' => $block ? $block->intl->title : null,
            'media' => $media,
        ];
    }

    /**
     * To fix Url.
     */
    private static function fixUrl(?Url $url = null, ?string $urlCode = null, ?IntlModel $intl = null): ?string
    {
        if ($url instanceof Url) {
            $flush = false;
            if (!$urlCode && $intl && $intl->title) {
                $url->setCode(Urlizer::urlize($intl->title));
                $urlCode = $url->getCode();
                $flush = true;
            }
            if ($flush) {
                self::$coreLocator->em()->persist($url);
                self::$coreLocator->em()->flush();
            }
        }

        return $urlCode;
    }
}