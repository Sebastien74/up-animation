<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Entity\Core\Domain;
use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use App\Entity\Module\Catalog\Product;
use App\Entity\Seo\Url;
use App\Model\Core\WebsiteModel;
use App\Service\Core\InterfaceHelper;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

/**
 * LocaleService.
 *
 * Manage switch route by locale
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LocaleService
{
    private ?Request $request;
    private array $localesWebsites = [];
    private array $cache = [];

    /**
     * LocaleService constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly EntityManagerInterface $entityManager,
        private readonly RouterInterface $router,
        private readonly InterfaceHelper $interfaceHelper,
        private readonly RequestStack $requestStack,
    ) {
        $this->setRequest();
    }

    /**
     * To set request.
     */
    private function setRequest(): void
    {
        $this->request = $this->requestStack?->getMainRequest();
    }

    /**
     * Execute service.
     *
     * @throws NonUniqueResultException
     */
    public function execute(WebsiteModel $website, $entity = null, mixed $url = null, ?string $pageUrl = null): array
    {
        $this->localesWebsites = $this->getLocalesWebsites($website);
        $routeName = $this->request->get('_route');
        $requestUrl = $url instanceof Url ? $url->getCode() : (!empty($url['code']) ? $url['code'] : $this->request->get('url'));
        $configuration = $website->configuration;
        $locales = $configuration->onlineLocales;
        $classname = is_object($entity) ? get_class($entity) : null;
        $interface = $classname ? $this->interfaceHelper->generate($classname) : null;
        $interfaceName = !empty($interface['name']) ? $interface['name'] : null;
        $asCard = isset($interface['card']) && $interface['card'];

        $entity = $entity ?: null;
        $urlCodes = [];
        $urlIndexCodes = [];

        if ('front_index' === $routeName && !$requestUrl) {
            return $this->localesWebsites;
        }

        if ('front_index' === $routeName) {
            $entity = $this->getEntity(Page::class, $requestUrl, $website->entity);
            $urlCodes = $this->getUrlCodes($locales, $entity);
        } elseif ($routeName === 'front_'.$interfaceName.'_view_only' || $routeName !== 'front_'.$interfaceName.'_view' && $asCard && !$pageUrl) {
            $urlCodes = $this->getUrlCodes($locales, $entity);
        } elseif ($routeName === 'front_'.$interfaceName.'_view' || $asCard) {
            $pageUrl = $pageUrl ?: $this->request->get('pageUrl');
            $pageIndex = $this->getEntity(Page::class, $pageUrl, $website->entity);
            $urlIndexCodes = $this->getUrlCodes($locales, $pageIndex);
            $entity = $this->getEntity($classname, $requestUrl, $website->entity);
            $urlCodes = $this->getUrlCodes($locales, $entity);
        }

        return $entity ? $this->getUrls($locales, $entity, $urlIndexCodes, $urlCodes, $routeName) : $this->localesWebsites;
    }

    /**
     * Set locales.
     */
    public function getLocalesWebsites(?WebsiteModel $website = null): array
    {
        $protocol = $_ENV['APP_PROTOCOL'].'://';
        $localesWebsites = [];
        $dirname = $this->coreLocator->cacheDir().'/domains.cache.json';
        $filesystem = new Filesystem();

        if ($filesystem->exists($dirname)) {
            $jsonDomains = (array) json_decode(file_get_contents($dirname));
            $configurationId = $website->configuration->id;
            $configurationDomains = isset($jsonDomains[$configurationId]) ? (array) $jsonDomains[$configurationId] : [];
            foreach ($configurationDomains as $locale => $domains) {
                foreach ($domains as $domain) {
                    if ($domain->asDefault) {
                        $localesWebsites[$locale] = $protocol.$domain->name;
                    }
                }
            }
        } elseif ($website instanceof WebsiteModel) {
            $configuration = !empty($this->cache['configuration']) ? $this->cache['configuration'] : null;
            if (!$configuration) {
                $configuration = $this->cache['configuration'] = $website->configuration;
            }
            $domainsDb = $this->entityManager->getRepository(Domain::class)->findByConfiguration($configuration->entity);
            foreach ($domainsDb as $domain) {
                if ($domain->isAsDefault()) {
                    $localesWebsites[$domain->getLocale()] = $protocol.$domain->getName();
                }
            }
        }

        return $localesWebsites;
    }

    /**
     * Get entity by url code and current request locale.
     */
    private function getEntity(string $classname, string $code, Website $website): mixed
    {
        $entities = $this->entityManager->createQueryBuilder()->select('e')
            ->from($classname, 'e')
            ->leftJoin('e.urls', 'u')
            ->andWhere('u.code = :code')
            ->andWhere('u.online = :online')
            ->andWhere('u.locale = :locale')
            ->andWhere('e.website = :website')
            ->setParameter('code', $code)
            ->setParameter('online', true)
            ->setParameter('locale', $this->request->getLocale())
            ->setParameter('website', $website)
            ->addSelect('u')
            ->getQuery()
            ->getResult();

        $entity = !empty($entities[0]) ? $entities[0] : [];
        if ($entity) {
            $this->entityManager->refresh($entity);
            $entity = $this->entityManager->getRepository($classname)->findOneBy(['id' => $entity->getId()]);
        }

        return $entity;
    }

    /**
     * Get codes URL.
     */
    private function getUrlCodes(array $locales, mixed $entity): array
    {
        $urlCodes = [];
        foreach ($locales as $locale) {
            if (is_object($entity) && method_exists($entity, 'getUrls')) {
                foreach ($entity->getUrls() as $url) {
                    /** @var Url $url */
                    if ($url->getLocale() === $locale && $url->isOnline()) {
                        $urlCodes[$locale] = $entity instanceof Page && $entity->isAsIndex() ? null : $url->getCode();
                        break;
                    }
                }
            }
        }

        return $urlCodes;
    }

    /**
     * Get locales URLS.
     */
    private function getUrls(array $locales, mixed $entity, array $urlIndexCodes, array $urlCodes, ?string $routeName = null): array
    {
        $urls = [];
        foreach ($locales as $locale) {
            $domain = !empty($this->localesWebsites[$locale]) ? $this->localesWebsites[$locale] : null;
            if ($domain && $entity instanceof Page && !empty($urlCodes[$locale])) {
                $urls[$locale] = $domain.$this->router->generate('front_index', ['url' => $urlCodes[$locale]]);
            } elseif ($domain && $routeName && str_contains($routeName, 'front_') && str_contains($routeName, '_view') && !empty($urlIndexCodes[$locale]) && !empty($urlCodes[$locale])) {
                $urls[$locale] = $domain.$this->router->generate($routeName, ['_locale' => $locale, 'pageUrl' => $urlIndexCodes[$locale], 'url' => $urlCodes[$locale]]);
            } elseif ($domain && $routeName && str_contains($routeName, 'front_') && str_contains($routeName, '_view_only') && empty($urlIndexCodes[$locale]) && !empty($urlCodes[$locale]) && $entity instanceof Product) {
                $referPageUrl = $this->getUrlCodes([$this->request->getLocale()], $entity->getCatalog())[$this->request->getLocale()];
                $urls[$locale] = $domain.$this->router->generate($routeName, ['_locale' => $locale, 'customerUrl' => $referPageUrl, 'url' => $urlCodes[$locale]]);
            } elseif ($domain && $routeName && str_contains($routeName, 'front_') && str_contains($routeName, '_view_only') && empty($urlIndexCodes[$locale]) && !empty($urlCodes[$locale])) {
                $urls[$locale] = $domain.$this->router->generate($routeName, ['_locale' => $locale, 'url' => $urlCodes[$locale]]);
            }
            //            elseif ($domain) {
            //                $urls[$locale] = $domain;
            //            }
        }

        return $urls;
    }
}
