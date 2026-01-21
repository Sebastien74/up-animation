<?php

declare(strict_types=1);

namespace App\Model\Core;

use App\Entity\Core\Domain;
use App\Entity\Core\Security;
use App\Entity\Core\Website;
use App\Entity\Core\Website as WebsiteEntity;
use App\Entity\Layout\Page;
use App\Model\Api\ApiModel;
use App\Model\BaseModel;
use App\Model\Seo\SeoConfigurationModel;
use App\Model\ViewModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\Link;

/**
 * WebsiteModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class WebsiteModel extends BaseModel
{
    private static array $cache = [];

    /**
     * WebsiteModel constructor.
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $slug = null,
        public readonly ?string $companyName = null,
        public readonly ?WebsiteEntity $entity = null,
        public readonly ?string $uploadDirname = null,
        public readonly ?ConfigurationModel $configuration = null,
        public readonly ?SeoConfigurationModel $seoConfiguration = null,
        public readonly ?InformationModel $information = null,
        public readonly ?ApiModel $api = null,
        public readonly ?object $hosts = null,
        public readonly ?string $schemeAndHttpHost = null,
        public readonly ?Security $security = null,
        public readonly ?string $securityDashboardUrl = null,
        public readonly ?array $logos = null,
        public readonly ?string $logo = null,
        public readonly ?string $footerLogo = null,
        public readonly ?string $emailLogo = null,
        public readonly ?array $networks = null,
        public readonly ?array $addresses = null,
        public readonly ?array $phones = null,
        public readonly ?array $emails = null,
        public readonly ?array $infoZones = null,
        public readonly ?string $contactPageUrl = null,
        public readonly ?bool $isEmpty = null,
    ) {
    }

    /**
     * Get model.
     *
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException|\ReflectionException
     */
    public static function fromEntity(WebsiteEntity $website, CoreLocatorInterface $coreLocator, ?string $locale = null): self
    {
        self::setLocator($coreLocator);

        $locale = $locale ?: self::$coreLocator->locale();

        if (isset(self::$cache['response'][$website->getId()][$locale])) {
            return self::$cache['response'][$website->getId()][$locale];
        }

        $information = InformationModel::fromEntity($website, $coreLocator, $locale);
        $configuration = ConfigurationModel::fromEntity(self::cache($website, 'configuration', self::$cache), $information, $coreLocator, $locale);
        $security = $website->getSecurity();

        /** Preload logo */
        $logo = !empty($information->logos['logo']) && $coreLocator->schemeAndHttpHost() && str_contains($information->logos['logo'], self::$coreLocator->schemeAndHttpHost())
            ? $information->logos['logo'] : (!empty($information->logos['logo']) ? self::$coreLocator->schemeAndHttpHost().$information->logos['logo'] : null);
        $linkProvider = self::$coreLocator->request() ? self::$coreLocator->request()->attributes->get('_links', new GenericLinkProvider()) : null;
        if ($logo && $linkProvider) {
            self::$coreLocator->request()->attributes->set('_links', $linkProvider->withLink(
                (new Link('preload', $logo))->withAttribute('as', 'image')
            ));
        }

        $hosts = self::host($website);

        self::$cache['response'][$website->getId()][$locale] = new self(
            id: $website->getId(),
            slug: $website->getSlug(),
            companyName: $information->companyName,
            entity: $website,
            uploadDirname: $website->getUploadDirname(),
            configuration: $configuration,
            seoConfiguration: SeoConfigurationModel::fromEntity($website, $coreLocator),
            information: $information,
            api: self::jsonCache($website, $locale, ApiModel::class),
            hosts: $hosts,
            schemeAndHttpHost: $hosts->schemeAndHttpHost,
            security: $security,
            securityDashboardUrl: self::securityDashboardUrl($security),
            logos: $information->logos,
            logo: !empty($information->logos['logo']) ? $information->logos['logo'] : null,
            footerLogo: !empty($information->logos['footer']) ? $information->logos['footer'] : null,
            emailLogo: !empty($information->logos['email']) ? $information->logos['email'] : null,
            networks: $information->networks,
            addresses: $information->addresses,
            phones: $information->phones,
            emails: $information->emails,
            infoZones: $information->zones,
            contactPageUrl: !empty($configuration->pages['contact']) ? $configuration->pages['contact']->path : null,
            isEmpty: !$website->getId(),
        );

        return self::$cache['response'][$website->getId()][$locale];
    }

    /**
     * Get Hosts.
     */
    private static function host(?Website $website): object
    {
        $host = null;
        $schemeAndHttpHost = null;
        $request = self::$coreLocator->request();

        if (is_object($request) && method_exists($request, 'getHost')) {
            $host = $request->getHost();
            $schemeAndHttpHost = $request->getSchemeAndHttpHost();
        } elseif ($website instanceof Website) {
            $configuration = $website->getConfiguration();
            $defaultLocale = $configuration->getLocale();
            $domains = self::$coreLocator->em()->getRepository(Domain::class)->findBy(['configuration' => $configuration, 'locale' => $defaultLocale, 'asDefault' => true]);
            $host = !empty($domains) ? $domains[0]->getName() : null;
            $schemeAndHttpHost = 'https://'.$host;
        }

        return (object) [
            'host' => $host,
            'schemeAndHttpHost' => $schemeAndHttpHost,
        ];
    }

    /**
     * Get security dashboard URL.
     */
    private static function securityDashboardUrl(?Security $security): string
    {
        $url = self::$coreLocator->router()->generate('security_front_dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $page = $security->getFrontPageRedirection();
        $pageUrl = $page instanceof Page ? ViewModel::url($page) : false;

        return $pageUrl && $pageUrl->online && $pageUrl->path ? $pageUrl->path : $url;
    }
}
