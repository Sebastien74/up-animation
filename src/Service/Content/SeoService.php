<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Entity\Core\Domain;
use App\Entity\Core\Website;
use App\Entity\Layout;
use App\Entity\Media;
use App\Entity\Module\Catalog;
use App\Entity\Module\Menu\Link;
use App\Entity\Seo;
use App\Model\Core\InformationModel;
use App\Model\Core\WebsiteModel;
use App\Model\IntlModel;
use App\Model\Layout\BlockModel;
use App\Model\Seo\SeoConfigurationModel;
use App\Model\ViewModel;
use App\Service\Interface\CoreLocatorInterface;
use App\Twig\Content\LayoutRuntime;
use App\Twig\Translation\IntlRuntime;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query\QueryException;
use Psr\Cache\InvalidArgumentException;
use ReflectionException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Request;

/**
 * SeoService.
 *
 * Manage Seo page
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => SeoService::class, 'key' => 'seo_service'],
])]
class SeoService implements SeoInterface
{
    /** Preconized 155 */
    private const int DESC_LIMIT = 100000;
    private const array RELATIONS_MODELS = ['category', 'catalog'];
    private ?string $schemeAndHttpHost;
    private string $locale;
    private ?Layout\Layout $layout = null;
    private array $interface = [];
    private ?string $classname = null;
    private ?WebsiteModel $website = null;
    private ?SeoConfigurationModel $seoConfiguration = null;
    private array $logos = [];
    private ?ViewModel $entity = null;
    private ?Seo\Model $model = null;
    private array $localesWebsites = [];
    private ?string $canonicalPattern = null;
    private ?Seo\Seo $seo = null;
    private ?string $title = null;
    private ?string $titleH1 = null;
    private ?string $fullTitle = null;
    private ?string $titleSecond = null;
    private ?IntlModel $informationIntl = null;
    private ?string $ogTitle = null;
    private ?string $description = null;
    private bool $isHomePage = false;
    private mixed $intl = [];
    private ?array $indexUrlCodes = [];
    private array $cache = [];
    private ?PhpArrayAdapter $routeCache = null;

    /**
     * SeoService constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly LayoutRuntime $layoutRuntime,
        private readonly IntlRuntime $intlRuntime,
        private readonly ListingService $listingService,
        private readonly LocaleService $localeService,
    ) {
        $this->schemeAndHttpHost = $this->coreLocator->request() instanceof Request ? $this->coreLocator->request()->getSchemeAndHttpHost() : null;
        $this->locale = $this->coreLocator->request()->get('entitylocale')
            ? $this->coreLocator->request()->get('entitylocale')
            : ($this->coreLocator->request() instanceof Request ? $this->coreLocator->request()->getLocale() : '');
    }

    /**
     * Execute service.
     *
     * @throws InvalidArgumentException|NonUniqueResultException|MappingException|ReflectionException|QueryException
     */
    public function execute(
        ?Seo\Url $url = null,
        mixed $entity = null,
        ?string $locale = null,
        bool $onlyTitle = false,
        ?WebsiteModel $website = null,
        array $options = [],
        bool $asIndexMicrodata = false,
    ): bool|array {

        if (!$url) {
            return false;
        }

        if ($locale) {
            $this->locale = $locale;
        }

        $defaultOptions = ['disabledMedias' => true, 'disabledCategories' => true];
        $options = array_merge($defaultOptions, $options);

        $this->entity = $entity && !$entity instanceof ViewModel ? ViewModel::fromEntity($entity, $this->coreLocator, $options) : ($entity ?: $this->entity($url));
        $this->layout = is_object($this->entity) && property_exists($this->entity, 'layout') ? $this->entity->layout : null;
        $this->classname = $this->entity && $this->entity->entity ? str_replace('Proxies\__CG__\\', '', get_class($this->entity->entity)) : null;
        $this->interface = $this->classname ? $this->coreLocator->interfaceHelper()->generate($this->classname) : [];
        $this->website = $website instanceof WebsiteModel ? $website : WebsiteModel::fromEntity($url->getWebsite(), $this->coreLocator);
        $this->website = $this->website ?: $this->coreLocator->website();
        $this->seoConfiguration = $this->website?->seoConfiguration;
        $this->logos = $this->website->configuration->logos;
        $this->model = $this->getModel($url);
        $this->localesWebsites = $this->localeService->getLocalesWebsites($this->website);
        $this->intl = $this->entity->intl ?: $this->website->information->intl;

        $this->seo($url);

        return $this->getResponse($url, $onlyTitle, $asIndexMicrodata, $options);
    }

    /**
     * To get RELATIONS_MODELS.
     */
    public function getRelationsModels(): array
    {
        return self::RELATIONS_MODELS;
    }

    /**
     * Get Model card.
     */
    private function getModel(Seo\Url $url): ?Seo\Model
    {
        $website = $this->website ? $this->website->entity : ($url->getWebsite() ?: $this->coreLocator->website()->entity);
        $locale = $url->getLocale();
        $websiteId = $website->getId();

        $model = null;
        if ($this->classname) {
            $cacheKey = $this->classname.'-'.$locale.'-'.$websiteId;
            if (isset($this->cache['model'][$cacheKey])) {
                $model = $this->cache['model'][$cacheKey];
            } else {
                $model = $this->coreLocator->em()->getRepository(Seo\Model::class)->findByLocaleClassnameAndWebsite($locale, $this->classname, $website);
                $this->cache['model'][$cacheKey] = $model;
            }
        }

        foreach ($this->getRelationsModels() as $relationName) {
            $getter = 'get'.ucfirst($relationName);
            $entity = method_exists($this->entity, 'getId') ? $this->entity : $this->entity->entity;
            if ($entity && method_exists($entity, $getter) && $entity->$getter()) {
                $relationEntity = $entity->$getter();
                $relationClassname = get_class($relationEntity);
                $relationId = method_exists($relationEntity, 'getId') ? $relationEntity->getId() : null;
                if ($relationId) {
                    $relationCacheKey = $relationClassname.'-'.$relationId.'-'.$this->classname.'-'.$locale.'-'.$websiteId;
                    if (isset($this->cache['model'][$relationCacheKey])) {
                        $model = $this->cache['model'][$relationCacheKey] ?: $model;
                    } else {
                        $relationModel = $this->coreLocator->em()->getRepository(Seo\Model::class)->findByLocaleClassnameAndWebsite($locale, $relationClassname, $website, $this->classname, $relationId);
                        $this->cache['model'][$relationCacheKey] = $relationModel;
                        $model = $relationModel ?: $model;
                    }
                }
            }
        }

        return $model;
    }

    /**
     * Get Model card.
     */
    public function getLocalesModels(mixed $entity, WebsiteModel $websiteModel): array
    {
        $models = [];
        if ($entity && method_exists($entity, 'getUrls')) {
            foreach ($entity->getUrls() as $url) {
                $model = $this->getModel($url);
                if ($model && ($model->getMetaTitle() || $model->getMetaDescription())) {
                    $models[$url->getLocale()] = $this->getModel($url);
                }
            }
        }

        return $models;
    }

    /**
     * Get response.
     *
     * @throws InvalidArgumentException|NonUniqueResultException|MappingException|QueryException
     */
    private function getResponse(Seo\Url $url, bool $onlyTitle = false, bool $asIndexMicrodata = false): array
    {
        if ($onlyTitle) {
            $menuLink = $options['menuLink'] ?? false;
            return [
                'title' => $this->getTitle($menuLink),
                'breadcrumb' => $this->seo?->getBreadcrumbTitle(),
                'titleH1' => $this->titleH1,
            ];
        }

        $this->getInformation();
        $haveAfterDash = $this->haveAfterDash();

        return [
            'entity' => $this->entity,
            'isHomePage' => $this->isHomePage(),
            'interface' => $this->interface,
            'asCard' => !isset($this->interface['card']) ? false : $this->interface['card'],
            'haveIndexPage' => $this->getHaveIndexPage(),
            'url' => $url,
            'code' => $url->getCode(),
            'haveAfterDash' => $haveAfterDash,
            'index' => $url->isAsIndex() ? 'index' : 'noindex',
            'locale' => $this->locale,
            'afterDash' => $haveAfterDash ? $this->getTitleSecond() : null,
            'breadcrumb' => !$asIndexMicrodata && $this->seo instanceof Seo\Seo ? $this->seo->getBreadcrumbTitle() : null,
            'title' => $this->getTitle(),
            'titleH1' => $this->titleH1,
            'fullTitle' => $this->getFullTitle($haveAfterDash),
            'description' => $this->getDescription(),
            'author' => $this->getAuthor(),
            'keywords' => !$asIndexMicrodata ? $this->getKeywords() : null,
            'footerDescription' => !$asIndexMicrodata ? $this->getFooterDescription() : null,
            'canonical' => $this->getCanonical($url),
            'canonicalPattern' => $this->canonicalPattern,
            'createdAt' => $this->entity->entity && method_exists($this->entity->entity, 'getCreatedAt') ? $this->entity->entity->getCreatedAt() : null,
            'updatedAt' => $this->entity->entity && method_exists($this->entity->entity, 'getUpdatedAt') ? $this->entity->entity->getUpdatedAt() : null,
            'publishedDate' => $this->entity->entity && method_exists($this->entity->entity, 'getPublicationDate') && $this->entity->entity->getPublicationDate() ? $this->entity->entity->getPublicationDate()
                : ($this->entity->entity && method_exists($this->entity->entity, 'getPublicationStart') ? $this->entity->entity->getPublicationStart() : null),
            'ogTitle' => $this->getOgTitle(),
            'ogFullTitle' => $this->getOgFullTitle(),
            'ogDescription' => $this->getOgDescription(),
            'ogType' => 'website',
            'ogTwitterCard' => $this->seo instanceof Seo\Seo ? $this->seo->getMetaOgTwitterCard() : null,
            'ogTwitterHandle' => $this->seo instanceof Seo\Seo ? $this->seo->getMetaOgTwitterHandle() : null,
            'ogImage' => $this->getOgImage(),
            'microdata' => $this->getMicrodata($this->website),
            'localesAlternate' => $this->getLocalesAlternates($url, $asIndexMicrodata),
            'indexUrlCodes' => $this->indexUrlCodes,
            'model' => $this->model,
        ];
    }

    /**
     * To check if is homepage.
     */
    private function isHomePage(): bool
    {
        $this->isHomePage = false;
        if ($this->entity->entity instanceof Layout\Page && $this->entity->entity->isAsIndex()) {
            $this->isHomePage = true;
        }

        return $this->isHomePage;
    }

    /**
     * To set Information intl.
     */
    private function getInformation(): void
    {
        $this->informationIntl = $this->website->information->intl;
    }

    /**
     * To check if have index Page.
     */
    private function getHaveIndexPage(): bool
    {
        return !empty($this->interface['indexPage']);
    }

    /**
     * Get title.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    private function getTitle(bool $menuLink = false): ?string
    {
        $this->titleH1 = null;
        $this->title = $this->seo instanceof Seo\Seo ? $this->seo->getMetaTitle() : null;
        $entity = $this->entity->entity;
        $getMainTitle = $entity instanceof Layout\Page || ($entity && method_exists($entity, 'isCustomLayout') && $entity->isCustomLayout());
        $title = $getMainTitle && $this->layout ? $this->layoutRuntime->mainLayoutTitle($this->layout) : null;
        $this->titleH1 = $title && is_object($title) && 1 === $title->getTitleForce() ? $title->getTitle() : $title;

        if ($this->layout && !$this->title) {
            $this->title = is_object($title) ? $title->getTitle() : (is_string($title) ? $title : null);
            if (!$this->title && is_object($title) && method_exists($title, 'getIntls')) {
                foreach ($title->getIntls() as $intl) {
                    if ($intl->getLocale() === $this->locale) {
                        if (!$this->title) {
                            $this->title = $intl->getTitle();
                        }
                        if (!$this->titleH1) {
                            $this->titleH1 = $intl->getTitle();
                        }
                    }
                }
            }
        }

        if (!$this->title && $this->model instanceof Seo\Model) {
            $this->title = $this->parseModel($this->entity, $this->model->getMetaTitle());
        }

        if (!$this->title && $this->intl) {
            $this->title = $this->intl->title;
        }

        if (!$this->titleH1 && $this->intl) {
            $this->titleH1 = $this->intl->title;
        }

        if (!$this->titleH1 && $menuLink && $entity instanceof Layout\Page) {
            $link = $this->coreLocator->em()->getRepository(Link::class)->findByPageAndLocale($entity, $this->locale, null, true);
            $this->titleH1 = $link && $link->getIntl() ? $link->getIntl()->getTitle() : $this->intl->title;
        }

        if (!$this->titleH1 && $entity instanceof Layout\Page) {
            $this->titleH1 = $this->coreLocator->em()->getRepository(Layout\Block::class)->findTitleByForceAndLocalePage($entity, $this->locale, 1, false);
        }

//        if (!$this->titleH1 && method_exists($entity, 'getAdminName')) {
//            $this->titleH1 = $entity->getAdminName();
//        }

        if ($this->titleH1) {
            $this->titleH1 = str_replace(['<br>', '<br/>'], ' ', $this->titleH1);
        }

        return $this->title ? strip_tags(rtrim($this->title, '.')) : null;
    }

    /**
     * Get Full title with after dash.
     */
    private function getFullTitle(bool $haveAfterDash): ?string
    {
        $this->fullTitle = $this->title ?: $this->titleH1;
        if ($this->title && $haveAfterDash) {
            $secondTitle = $this->titleSecond ? trim($this->titleSecond) : $this->titleSecond;
            $firstChar = $secondTitle ? substr($secondTitle, 0, 1) : null;
            $this->fullTitle = '-' === $firstChar || '|' === $firstChar ? $this->title.' '.$secondTitle : $this->title.' | '.$secondTitle;
        } elseif ($this->title && $this->model instanceof Seo\Model && $this->model->getMetaTitleSecond()) {
            $secondTitle = trim($this->model->getMetaTitleSecond());
            $firstChar = substr($secondTitle, 0, 1);
            $this->fullTitle = '-' === $firstChar || '|' === $firstChar ? $this->title.' '.$secondTitle : $this->title.' | '.$secondTitle;
        }
        if (!$this->fullTitle && $this->titleSecond) {
            $this->fullTitle = $this->titleSecond;
        }

        $this->fullTitle = $this->fullTitle ? trim($this->fullTitle, ' |') : $this->fullTitle;

        return trim($this->fullTitle, '|');
    }

    /**
     * Get title.
     */
    private function getTitleSecond(): ?string
    {
        if ($this->model instanceof Seo\Model && $this->model->isNoAfterDash()) {
            return '';
        }
        if ($this->seo instanceof Seo\Seo) {
            $this->titleSecond = $this->seo->getMetaTitleSecond();
        }
        if (!$this->titleSecond && $this->model instanceof Seo\Model) {
            $this->titleSecond = $this->parseModel($this->entity, $this->model->getMetaTitleSecond());
        }
        /* Get Seo configuration */
        if (!$this->titleSecond) {
            $intl = $this->getIntl($this->seoConfiguration);
            $this->titleSecond = $intl ? $intl->getTitle() : null;
        }
        /* Get WebsiteModel configuration */
        if (!$this->titleSecond && $this->informationIntl) {
            $this->titleSecond = $this->informationIntl->title;
        }

        return $this->titleSecond ? strip_tags(rtrim($this->titleSecond, '.')) : $this->titleSecond;
    }

    /**
     * Get description.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private function getDescription(): ?string
    {
        $asQuery = false;
        $this->description = $this->seo instanceof Seo\Seo ? $this->seo->getMetaDescription() : null;
        $intl = $this->intl;

        $entity = $this->entity instanceof ViewModel ? $this->entity->entity : $this->entity;
        $getDescription = $entity instanceof Layout\Page || ($entity && method_exists($entity, 'isCustomLayout') && $entity->isCustomLayout());
        if ($getDescription && !$this->description && $this->layout) {
            $asQuery = true;
            $blockText = $this->layoutRuntime->layoutBlockType($this->layout, 'text', $this->locale);
            $intl = $blockText instanceof BlockModel ? $blockText->intl : null;
        }

        if (!$this->description && $this->model instanceof Seo\Model) {
            $this->description = $this->parseModel($this->entity, $this->model->getMetaDescription());
        }

        if (!$this->description && $intl) {
            $asQuery = true;
            if ($intl->introduction) {
                $this->description = $intl->introduction;
            }
            if (!$this->description && $intl->body) {
                $this->description = $intl->body;
            }
        }

        $result = $asQuery && $this->description
            ? substr(str_replace(["\r", "\n"], '', strip_tags($this->description)), 0, self::DESC_LIMIT)
            : $this->description;

        return $result ? strip_tags(rtrim(str_replace('"', "''", $result), '.')) : '';
    }

    /**
     * Get author.
     */
    private function getAuthor(): ?string
    {
        return $this->seo instanceof Seo\Seo ? $this->seo->getAuthor() : null;
    }

    /**
     * Get keywords.
     */
    private function getKeywords(): ?string
    {
        return $this->seo instanceof Seo\Seo && $this->seo->getKeywords() ? strip_tags(rtrim($this->seo->getKeywords())) : null;
    }

    /**
     * Get footer description.
     */
    private function getFooterDescription(): ?string
    {
        $description = $this->seo instanceof Seo\Seo ? $this->seo->getFooterDescription() : null;
        if (!$description && $this->model instanceof Seo\Model) {
            $description = $this->parseModel($this->model, $this->model->getFooterDescription());
        }

        return $description;
    }

    /**
     * Check if meta title have string after dash.
     */
    private function haveAfterDash(): bool
    {
        $result = true;
        if ($this->website instanceof WebsiteModel) {
            $configuration = $this->website->seoConfiguration;
            $disableAfterDash = !$configuration instanceof SeoConfigurationModel || $configuration->entity->isDisableAfterDash();
            if ($disableAfterDash) {
                return false;
            }
        }
        $metaTitle = $this->seo instanceof Seo\Seo && $this->seo->getMetaTitle() ? strip_tags($this->seo->getMetaTitle()) : '';
        if ($this->seo instanceof Seo\Seo && strlen($metaTitle) > 0) {
            $result = !$this->seo->isNoAfterDash();
        } elseif (!$this->seo instanceof Seo\Seo && $this->model instanceof Seo\Model
            || $this->seo instanceof Seo\Seo && 0 === strlen($metaTitle) && $this->model instanceof Seo\Model) {
            $result = !$this->model->isNoAfterDash();
        }

        return $result;
    }

    /**
     * Get canonical.
     *
     * @throws InvalidArgumentException|NonUniqueResultException|MappingException|QueryException
     */
    public function getCanonical(Seo\Url $url): ?string
    {
        $schemeAndHttpHost = !empty($this->localesWebsites[$url->getLocale()])
            ? $this->localesWebsites[$url->getLocale()]
            : $this->schemeAndHttpHost;

        $requestUri = $this->coreLocator->request()->getRequestUri();
        $canonical = !preg_match('/\/admin-'.$_ENV['SECURITY_TOKEN'].'/', $requestUri)
            ? rtrim($schemeAndHttpHost.$requestUri, '/')
            : $schemeAndHttpHost;

        if ($this->entity->entity instanceof Layout\Page && !$this->isHomePage) {
            $canonical = $schemeAndHttpHost.'/'.$url->getCode();
        } elseif ($this->entity instanceof ViewModel && !$this->isHomePage && $this->entity->url) {
            $canonical = $this->entity->url;
        } elseif ($this->model instanceof Seo\Model) {
            $canonical = $this->getAsCardUrl($url, $this->entity, get_class($this->entity), true);
        }

        $canonical = is_object($canonical) && property_exists($canonical, 'canonical') ? $canonical->canonical : $canonical;
        $matches = $canonical ? explode('/', $canonical) : [];
        $this->canonicalPattern = !$this->isHomePage && !is_bool(end($matches)) ? str_replace(end($matches), '', $canonical) : $canonical;

        return $canonical ? ltrim($canonical, '/') : ltrim($schemeAndHttpHost, '/');
    }

    /**
     * Get Url for entity as card.
     *
     * @throws InvalidArgumentException|NonUniqueResultException|MappingException|QueryException
     */
    public function getAsCardUrl(mixed $url, mixed $entity, string $classname, bool $asObject = false, array $interface = [], array $indexPagesCodes = []): object|string|null
    {
        $entity = !$entity instanceof ViewModel ? ViewModel::fromEntity($entity, $this->coreLocator, ['disabledMedias' => true, 'disabledCategories' => true, 'disabledLayout' => true]) : $entity;
        $this->indexUrlCodes = !empty($indexPagesCodes) ? $indexPagesCodes : $this->indexUrlCodes;
        $isObjectUrl = is_object($url);
        $protocol = $this->coreLocator->request()->isSecure() ? 'https://' : 'http://';
        $locale = $isObjectUrl ? $url->getLocale() : null;
        $domain = !empty($this->cache['domain'][$locale]) ? $this->cache['domain'][$locale] : null;
        if (!$domain && $isObjectUrl) {
            $domains = $this->coreLocator->em()->getRepository(Domain::class)->findBy([
                'configuration' => $url->getWebsite() ? $url->getWebsite()->getConfiguration() : $this->website->entity->getConfiguration(),
                'locale' => $locale,
                'asDefault' => true,
            ]);
            $domain = $this->cache['domain'][$locale] = !empty($domains) ? $domains[0] : null;
        }
        $canonical = $domain instanceof Domain ? $protocol.$domain->getName() : $this->schemeAndHttpHost;

        if (isset($this->cache['interface'][$classname])) {
            $interface = $this->cache['interface'][$classname];
        } else {
            $interface = !empty($interface) ? $interface : $this->coreLocator->interfaceHelper()->generate($classname);
            $this->cache['interface'][$classname] = $interface;
        }

        $methodCategory = !empty($interface['indexPage']) ? $interface['indexPage'] : null;
        $listingClass = !empty($interface['listingClass']) ? $interface['listingClass'] : null;
        $indexPage = $isObjectUrl ? $url->getIndexPage() : null;
        $code = $isObjectUrl ? $url->getCode() : null;
        $indexUrlCodes = !empty($this->indexUrlCodes[$classname][$entity->id]) ? $this->indexUrlCodes[$classname][$entity->id] : ($methodCategory && $listingClass
            ? $this->listingService->indexesPages($entity, $locale, $listingClass, $classname, [$entity], $interface) : null);

        if (empty($this->indexUrlCodes[$classname])) {
            $this->indexUrlCodes[$classname] = $indexUrlCodes;
        }

        $pageUrl = is_array($indexUrlCodes) && !empty($indexUrlCodes[$entity->id]) ? $indexUrlCodes[$entity->id]
            : (!is_array($indexUrlCodes) ? $indexUrlCodes : null);
        $uri = $pageUrl && $this->checkRoute('front_'.$interface['name'].'_view.'.$locale)
            ? $this->coreLocator->router()->generate('front_'.$interface['name'].'_view', ['pageUrl' => $pageUrl, 'url' => $code]) : null;

        if (!$uri) {
            $existingOnlyRoute = $this->checkRoute('front_'.$interface['name'].'_view_only.'.$locale);
            $uri = $existingOnlyRoute ? $this->coreLocator->router()->generate('front_'.$interface['name'].'_view_only', ['url' => $code]) : null;
        }

        if ($uri) {
            $canonical = $canonical.$uri;
            if ($asObject) {
                return (object) [
                    'methodCategory' => $methodCategory,
                    'indexPage' => $indexPage,
                    'uri' => $uri,
                    'canonical' => $canonical,
                ];
            }

            return $canonical;
        }

        return null;
    }

    /**
     * Get OG Image.
     */
    private function getOgImage(bool $getFirst = false): ?string
    {
        $mediaRelation = $this->seo instanceof Seo\Seo ? $this->seo->getMediaRelation() : null;
        if ($mediaRelation instanceof Media\MediaRelation) {
            $seoMedia = $mediaRelation->getMedia();
            $media = $seoMedia instanceof Media\Media && $seoMedia->getFilename() ? $seoMedia : null;
            $uploadDirname = $this->website->uploadDirname;
            /* Get first image of Page [disabled by default] */
            if (!$media && Layout\Page::class === $this->classname && $getFirst) {
                $repository = $this->coreLocator->em()->getRepository(Layout\Block::class);
                $media = $repository->findMediaByLocalePage($this->entity->entity, $this->locale);
            }
            if ($media && !preg_match('/'.$uploadDirname.'/', $media->getFilename())) {
                return $this->schemeAndHttpHost.'/uploads/'.$uploadDirname.'/'.$media->getFilename();
            }

            return $media ? $this->schemeAndHttpHost.'/uploads/'.$uploadDirname.'/'.$media->getFilename() : null;
        }

        return null;
    }

    /**
     * Get Microdata.
     */
    public function getMicrodata(WebsiteModel $websiteModel): array
    {
        $intl = $websiteModel->seoConfiguration->intl;
        $information = $websiteModel->information;
        $author = $this->seo instanceof Seo\Seo && $this->seo->getAuthor() ? $this->seo->getAuthor() : ($intl instanceof IntlModel ? $intl->author : null);
        $companyName = $intl instanceof IntlModel && $intl->title ? $intl->title : ($this->informationIntl instanceof IntlModel ? $this->informationIntl->title : null);

        return [
            'url' => $this->coreLocator->request()->getUri(),
            'companyType' => $intl instanceof IntlModel && $intl->placeholder ? $intl->placeholder : 'Organization',
            'name' => $this->fullTitle ?: $companyName,
            'companyName' => $companyName,
            'companyLogo' => !empty($this->logos['logo']) ? $this->schemeAndHttpHost.$this->logos['logo'] : null,
            'image' => !empty($this->logos['share']) ? $this->schemeAndHttpHost.$this->logos['share'] : null,
            'description' => $this->seo && $this->seo->getMetaDescription() ? $this->seo->getMetaDescription() : ($intl instanceof IntlModel && $intl->introduction ? $intl->introduction : null),
            'phone' => $information instanceof InformationModel && $information->phone ? $information->phone->getTagNumber() : null,
            'email' => $information instanceof InformationModel ? $information->email : null,
            'address' => $information instanceof InformationModel ? $information->address : null,
            'author' => $author ?: ($this->informationIntl instanceof IntlModel ? $this->informationIntl->title : null),
            'authorType' => $this->seo instanceof Seo\Seo && $this->seo->getAuthorType() ? $this->seo->getAuthorType()
                : ($intl instanceof IntlModel && $intl->authorType ? $intl->authorType : 'Organization'),
            'script' => $this->seo instanceof Seo\Seo && $this->seo->getMetadata() ? $this->seo->getMetadata() : null,
        ];
    }

    /**
     * Get OG title.
     */
    private function getOgTitle(): ?string
    {
        $this->ogTitle = $this->seo instanceof Seo\Seo ? $this->seo->getMetaOgTitle() : null;
        if (!$this->ogTitle && $this->model instanceof Seo\Model) {
            $this->ogTitle = $this->parseModel($this->entity, $this->model->getMetaOgTitle());
        }

        return $this->ogTitle ? strip_tags(rtrim($this->ogTitle, '.')) : '';
    }

    /**
     * Get OG title with after dash.
     */
    private function getOgFullTitle(): ?string
    {
        return $this->ogTitle ?: $this->fullTitle;
    }

    /**
     * Get OG description.
     */
    private function getOgDescription(): ?string
    {
        $ogDescription = $this->seo instanceof Seo\Seo ? $this->seo->getMetaOgDescription() : null;
        if (!$ogDescription && $this->model instanceof Seo\Model) {
            $ogDescription = $this->parseModel($this->entity, $this->model->getMetaOgDescription());
        }
        $result = $ogDescription ?: $this->description;

        return $result ? strip_tags(rtrim(str_replace('"', "''", $result), '.')) : '';
    }

    /**
     * Get intl.
     */
    private function getIntl(mixed $entity = null): mixed
    {
        if (!$entity) {
            return null;
        }

        if (is_object($entity) && method_exists($entity, 'getIntls')) {
            foreach ($entity->getIntls() as $intl) {
                if ($intl->getLocale() === $this->locale) {
                    return $intl;
                }
            }
        }

        return null;
    }

    /**
     * Set Seo.
     */
    private function seo(?Seo\Url $url = null): void
    {
        $this->seo = $url instanceof Seo\Url ? $url->getSeo() : null;
    }

    /**
     * Set Entity.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    private function entity(?Seo\Url $url = null): ?object
    {
        if (!$url) {
            return null;
        }

        if (isset($this->cache['url_entity'][$url->getId()])) {
            return $this->cache['url_entity'][$url->getId()];
        }

        $metasData = $this->coreLocator->em()->getMetadataFactory()->getAllMetadata();
        foreach ($metasData as $metadata) {
            if ($metadata->hasAssociation('urls')) {
                $classname = $metadata->getName();
                $result = $this->coreLocator->em()->createQueryBuilder()->select('e')
                    ->from($classname, 'e')
                    ->leftJoin('e.urls', 'u')
                    ->andWhere('u.id = :id')
                    ->setParameter('id', $url->getId())
                    ->addSelect('u')
                    ->getQuery()
                    ->getResult();
                if (!empty($result[0])) {
                    $viewModel = ViewModel::fromEntity($result[0], $this->coreLocator);
                    $this->cache['url_entity'][$url->getId()] = $viewModel;
                    return $viewModel;
                }
            }
        }

        return null;
    }

    /**
     * Parse model.
     */
    private function parseModel(mixed $entity, ?string $string = null, ?string $search = null): ?string
    {
        if (!$string || !str_contains($string, '[')) {
            return $string;
        }

        if (preg_match_all("/\[([0-9a-zA-Z\.]+)\]/", $string, $matches)) {
            foreach ($matches[1] as $match) {
                if ($search && $match !== $search) {
                    continue;
                }

                $methods = explode('.', $match);
                $property = '';

                foreach ($methods as $methodSEO) {
                    $method = 'get'.ucfirst($methodSEO);
                    /* To set Product Features */
                    if (str_contains($match, 'feature.')) {
                        $featureMatches = explode('.', $match);
                        $featureCode = end($featureMatches);
                        $featureString = '';
                        $entityToProcess = $entity instanceof ViewModel ? $entity->entity : $entity;
                        if (method_exists($entityToProcess, 'getValues')) {
                            foreach ($entityToProcess->getValues() as $value) {
                                /** @var Catalog\FeatureValueProduct $value */
                                $feature = $value->getFeature();
                                $featureValue = $value->getValue();
                                if ($feature instanceof Catalog\Feature && $featureValue instanceof Catalog\FeatureValue && $feature->getSlug() === $featureCode) {
                                    $intlValue = $this->getIntl($featureValue);
                                    $isIntl = is_object($intlValue) && str_ends_with(get_class($intlValue), 'Intl');
                                    $featureTitle = $isIntl && $intlValue->getTitle() ? $intlValue->getTitle() : $featureValue->getAdminName();
                                    $featureString .= $featureTitle.', ';
                                }
                            }
                        }
                        if ($featureString) {
                            $string = str_replace('['.$match.']', rtrim($featureString, ', '), $string);
                        }
                    }

                    if ($property instanceof PersistentCollection) {
                        if ($property->isEmpty() && 'title' === $methodSEO && method_exists($entity, 'getAdminName')) {
                            $property = $entity->getAdminName();
                        } else {
                            $locale = $this->coreLocator->request()->getLocale();
                            foreach ($property as $propertyCol) {
                                if (is_object($propertyCol) && str_ends_with(get_class($propertyCol), 'Intl')) {
                                    if ($propertyCol->getLocale() === $locale) {
                                        $property = method_exists($propertyCol, $method) ? $propertyCol->$method() : null;
                                        break;
                                    }
                                }
                            }
                        }
                    }

                    if (empty($property) && method_exists($entity, $method)) {
                        $property = $entity->$method();
                    } elseif (empty($property) && property_exists($entity, $methodSEO)) {
                        $property = $entity->$methodSEO;
                    } elseif (is_object($property) && method_exists($property, $method)) {
                        $property = $property->$method();
                    }

                    if ($property instanceof \DateTimeInterface) {
                        $locale = $this->coreLocator->request()->getLocale();
                        $formatter = new \IntlDateFormatter($locale, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT);
                        $formatter->setPattern('cccc dd MMMM yyyy');
                        $property = $formatter->format($property);
                    }
                    if ($property && !$property instanceof PersistentCollection && is_object($property) && str_contains($match, '.') && property_exists($entity, 'entity') && !$entity->entity instanceof $property) {
                        $relationMatch = str_replace($methodSEO.'.', '', $match);
                        $string = str_replace('['.$match.']', '['.$relationMatch.']', $string);
                        $string = $this->parseModel($property, $string, $relationMatch);
                    }
                }

                $isValidProperty = !$property instanceof PersistentCollection && !is_object($property);
                if ($isValidProperty) {
                    $string = $string && $property !== null ? trim(str_replace('['.$match.']', strip_tags((string) $property), $string)) : str_replace('['.$match.']', '', $string);
                }
            }
        }

        return $string ? rtrim(substr($string, 0, self::DESC_LIMIT), ':\ ') : $string;
    }

    /**
     * Get locales alternates URL.
     *
     * @throws NonUniqueResultException
     */
    private function getLocalesAlternates(Seo\Url $url, bool $asIndexMicrodata = false): array
    {
        $alternates = [];
        if ($this->website instanceof Website && count($this->website->getConfiguration()->getAllLocales()) > 1) {
            $alternates = $this->localeService->execute($this->website, $this->entity, $url);
        }
        $canonicalizeAlternates = [];
        foreach ($alternates as $locale => $alternate) {
            $canonicalizeAlternates[$this->intlRuntime->canonicalizeLocale($locale)] = $alternate;
        }

        return $canonicalizeAlternates;
    }

    /**
     * To check if route exist.
     *
     * @throws InvalidArgumentException
     */
    private function checkRoute(string $routeName): bool
    {
        if (!$this->routeCache) {
            $dirname = $this->coreLocator->cacheDir().'/routes.cache';
            $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
            $this->routeCache = new PhpArrayAdapter($dirname, new FilesystemAdapter());
        }

        return $this->routeCache->getItem('route.'.$routeName)->isHit();
    }

    /**
     * To get SEO alert for backoffice.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    public function seoAlert(mixed $entity, WebsiteModel $websiteModel): array
    {
        $seoAlert = [];
        if ($entity && method_exists($entity, 'getUrls')) {
            $this->entity = !$entity instanceof ViewModel ? ViewModel::fromEntity($entity, $this->coreLocator, ['disabledMedias' => true, 'disabledCategories' => true, 'disabledLayout' => true]) : $entity;
            $this->classname = str_replace('Proxies\__CG__\\', '', get_class($entity));
            $seoAlert['noTitle'] = [];
            $seoAlert['noDescription'] = [];
            foreach ($entity->getUrls() as $url) {
                if (in_array($url->getLocale(), $websiteModel->configuration->allLocales) && $url->isOnline()) {
                    $seoDb = $url->getSeo();
                    $title = false;
                    $description = false;
                    if ($seoDb) {
                        $title = $seoDb->getMetaTitle();
                        $description = $seoDb->getMetaDescription();
                    }
                    if (!$title || !$description) {
                        $model = $this->getModel($url);
                        $title = !$title && $model && $model->getMetaTitle() ? $model->getMetaTitle() : $title;
                        $description = !$description && $model && $model->getMetaDescription() ? $model->getMetaDescription() : $description;
                        if (!$title) {
                            $seoAlert['noTitle'][] = $url->getLocale();
                        }
                        if (!$description) {
                            $seoAlert['noDescription'][] = $url->getLocale();
                        }
                    }
                }
            }
        }

        return $seoAlert;
    }
}
