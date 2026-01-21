<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Entity\Core as CoreEntities;
use App\Entity\Layout\Page;
use App\Entity\Seo\Redirection;
use App\Form\Interface\SeoFormManagerInterface;
use App\Model\Core\ConfigurationModel;
use App\Model\Core\WebsiteModel;
use App\Service\Core\Urlizer;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

/**
 * RedirectionService.
 *
 * Front redirection management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => RedirectionService::class, 'key' => 'redirection_service'],
])]
class RedirectionService
{
    private const array IPS_DEV = ['::1', '127.0.0.1', 'fe80::1', '194.51.155.21', '195.135.16.88', '176.135.112.19', '2a02:8440:5341:81fb:fd04:6bf3:c8c7:1edb', '88.173.106.115', '2001:861:43c3:ce70:bd5f:81d1:7710:888b', '2001:861:43c3:ce70:45e7:2aa7:ab50:c245'];
    private string $protocol;
    private static ?PhpArrayAdapter $redirectionCache = null;

    /**
     * RedirectionService constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly SeoFormManagerInterface $seoLocator,
    ) {
        $this->protocol = $_ENV['APP_PROTOCOL'].'://';
    }

    /**
     * To execute service.
     *
     * @throws NonUniqueResultException|InvalidArgumentException|MappingException
     */
    public function execute(Request $request): array
    {
        $host = $request->getHost();
        $repository = $this->coreLocator->em()->getRepository(CoreEntities\Website::class);
        $websiteId = $request->get('website') ? intval($request->get('website')) : null;
        $website = preg_match('/\/preview\//', $request->getUri())
            ? $repository->findObject($websiteId)
            : $repository->findOneByHost($host);

        $configuration = null;
        $domainRedirection = false;
        $urlRedirection = false;
        $inBuild = false;

        if (!$_POST) {
            $configuration = $website->configuration ?: null;
            if ($configuration) {
                $domain = $this->getDomain($configuration, $host);
                $locale = $domain ? $domain->locale : $configuration->locale;
                $domainRedirection = $this->domainRedirection($request, $website, $configuration, $domain);
                $urlRedirection = $this->urlRedirection($request, $website, $locale, $domainRedirection);
                $inBuild = $this->inBuild($request, $website, $configuration);
                $request->setLocale($locale);
                $request->getSession()->set('_locale', $locale);
            }
        }

        return [
            'website' => $website,
            'domainRedirection' => $domainRedirection,
            'urlRedirection' => $urlRedirection,
            'banRedirection' => $this->isBan($request, $configuration),
            'inBuildRedirection' => $inBuild,
        ];
    }

    /**
     * To get current DomainModel.
     */
    private function getDomain(ConfigurationModel $configuration, string $host): ?object
    {
        $domain = null;
        foreach ($configuration->domains as $configurationDomain) {
            if ($host === $configurationDomain->name) {
                $domain = $configurationDomain;
                break;
            }
        }

        return $domain ?: null;
    }

    /**
     * To redirect WebsiteModel DomainModel if not defined has default.
     */
    private function domainRedirection(
        Request $request,
        WebsiteModel $website,
        ConfigurationModel $configuration,
        ?object $domain = null): bool|string
    {
        $redirection = false;
        if (!$domain || !$domain->asDefault && $configuration->domain) {
            $defaultDomain = $configuration->domain;
            if ($defaultDomain && !preg_match('/\/uploads\/'.$website->uploadDirname.'/', $request->getUri())) {
                $domainName = str_contains($defaultDomain->name, 'http') ? $defaultDomain->name : $this->protocol.$defaultDomain->name;
                $redirection = rtrim($domainName.$request->getRequestUri(), '/');
            }
        }

        return $redirection;
    }

    /**
     * To redirect Url.
     *
     * @throws InvalidArgumentException
     */
    private function urlRedirection(Request $request, WebsiteModel $website, string $locale, mixed $domainRedirection = null): bool|string
    {
        $matches = explode('?', $request->getRequestUri());
        $uri = is_array($matches) && isset($matches[0]) ? $matches[0] : null;
        if (($uri && '/' !== $uri) || ($domainRedirection && $request->getSchemeAndHttpHost() !== $domainRedirection)) {
            $domain = str_replace(['http://', 'https://'], '', $request->getSchemeAndHttpHost());
            $filesystem = new Filesystem();
            $dirname = $this->coreLocator->cacheDir().DIRECTORY_SEPARATOR.'redirections.cache';
            if (self::$redirectionCache === null && $filesystem->exists($dirname)) {
                self::$redirectionCache = new PhpArrayAdapter($dirname, new FilesystemAdapter());
            }

            if (self::$redirectionCache) {
                $cache = self::$redirectionCache;
                $currentRequestSSLUri = 'https://'.$domain.$request->getRequestUri();
                $item = $cache->getItem('redirection.'.$locale.'.'.$website->id.'.'.Urlizer::urlize($currentRequestSSLUri));
                if ($item->isHit()) {
                    return $item->get();
                }
                $currentRequestNoSLLUri = $domain.$request->getRequestUri();
                $item = $cache->getItem('redirection.'.$locale.'.'.$website->id.'.'.Urlizer::urlize($currentRequestNoSLLUri));
                if ($item->isHit()) {
                    return $item->get();
                }
                if ($request->getRequestUri()) {
                    $item = $cache->getItem('redirection.'.$locale.'.'.$website->id.'.'.Urlizer::urlize($request->getRequestUri()));
                    if ($item->isHit()) {
                        return $item->get();
                    }
                }
            } elseif (!$filesystem->exists($dirname)) {
                $redirections = $this->coreLocator->em()->getRepository(Redirection::class)->findBy(['website' => $website->id]);
                $this->seoLocator->redirection()->setCache($redirections);
            }
        }

        return false;
    }

    /**
     * Check if WebsiteModel is in build.
     */
    public function inBuild(Request $request, WebsiteModel $website, ConfigurationModel $configuration): bool|string
    {
        $configurationEntity = new CoreEntities\Configuration();
        $ipsDev = $configurationEntity->getAllIPS($configuration->ipsDev);
        $ipsCustomer = $configurationEntity->getAllIPS([], $configuration->ipsCustomer);

        $inMaintenance = (isset($_ENV['UNDER_MAINTENANCE']) && $_ENV['UNDER_MAINTENANCE']) || !$configuration->onlineStatus;
        $envIPS = !empty($_ENV['MAINTENANCE_ALLOWED_IPS']) ? $_ENV['MAINTENANCE_ALLOWED_IPS'] : [];
        $IPS = !$request->get('disabled_ips') ? array_unique(array_merge(self::IPS_DEV, $ipsDev, $ipsCustomer, $envIPS)) : [];
        $allowedIP = $this->checkIP($IPS);

        $allowedPath = false;
        if ($inMaintenance) {
            $paths = ['media/cache/resolve'];
            foreach ($paths as $path) {
                if (str_contains($request->getRequestUri(), $path)) {
                    $allowedPath = true;
                    break;
                }
            }
        }

        if ($inMaintenance && !$allowedIP && !$allowedPath) {
            $buildPages = $this->coreLocator->em()->getRepository(Page::class)->findBy([
                'website' => $website->id,
                'template' => 'build.html.twig',
            ]);
            $uri = trim($request->getRequestUri(), '/');
            $buildPage = !empty($buildPages[0]) ? $buildPages[0] : null;
            if ($buildPage instanceof Page) {
                foreach ($buildPage->getUrls() as $url) {
                    if ($url->isOnline() && $url->getLocale() === $request->getLocale() && $url->getCode() !== $uri) {
                        return $this->coreLocator->router()->generate('front_index', ['url' => $url->getCode()]);
                    }
                }
            }

            return 'front_build_page' !== $request->get('_route') ? $this->coreLocator->router()->generate('front_build_page') : false;
        }

        return false;
    }

    /**
     * Check if current user is baned.
     */
    public function isBan(Request $request, ?ConfigurationModel $configuration = null): ?string
    {
        if ($configuration) {
            if ($this->checkIP($this->getIps('ipsBan', $configuration))) {
                if (!$this->checkIP($this->getIps('ipsDev', $configuration))) {
                    return $request->getSchemeAndHttpHost().'/denied.php';
                }
            }
        }

        return null;
    }

    /**
     * Get ips array.
     */
    private function getIps(string $type, ConfigurationModel $configuration): array
    {
        $ips = [];
        $ipsConfiguration = $configuration->$type;
        foreach ($ipsConfiguration as $ip) {
            $matches = explode(',', $ip);
            foreach ($matches as $match) {
                $ips[] = $match;
            }
        }

        return $ips;
    }

    /**
     * To check IP.
     */
    private function checkIP(array $IPS = []): bool
    {
        return (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && in_array($_SERVER['HTTP_X_FORWARDED_FOR'], $IPS, true))
            || (isset($_SERVER['HTTP_X_REAL_IP']) && in_array($_SERVER['HTTP_X_REAL_IP'], $IPS, true))
            || in_array(@$_SERVER['REMOTE_ADDR'], $IPS, true);
    }
}
