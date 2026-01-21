<?php

declare(strict_types=1);

namespace App\Model\Core;

use App\Entity\Core\Configuration;
use App\Entity\Core\Module;
use App\Entity\Layout\Page;
use App\Entity\Security\User;
use App\Model\BaseModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * ConfigurationModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class ConfigurationModel extends BaseModel
{
    private const array CUSTOM_MODULES = [
        'linkColors' => true,
        'cta' => false,
        'ctaColors' => false,
        'gradientColors' => true,
    ];

    private static array $cache = [];

    /**
     * ConfigurationModel constructor.
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?object $entity = null,
        public readonly ?string $locale = null,
        public readonly ?array $allLocales = null,
        public readonly ?array $onlineLocales = null,
        public readonly ?bool $asMultiLocales = null,
        public readonly ?string $template = null,
        public readonly ?bool $onlineStatus = null,
        public readonly ?bool $seoStatus = null,
        public readonly ?bool $accessibilityStatus = null,
        public readonly ?bool $preloader = null,
        public readonly ?bool $fullWidth = null,
        public readonly ?bool $scrollTopBtn = null,
        public readonly ?bool $breadcrumb = null,
        public readonly ?bool $subNavigation = null,
        public readonly ?bool $mediasSecondary = null,
        public readonly ?bool $progressiveWebApp = null,
        public readonly ?array $ipsBan = null,
        public readonly ?array $ipsDev = null,
        public readonly ?array $ipsCustomer = null,
        public readonly ?array $domains = null,
        public readonly ?object $domain = null,
        public readonly ?array $pages = null,
        public readonly ?array $medias = null,
        public readonly ?array $logos = null,
        public readonly ?array $modules = null,
        public readonly ?array $transitions = null,
        public readonly ?string $adminTheme = null,
        public readonly ?string $buildTheme = null,
        public readonly ?string $charset = null,
        public readonly ?object $customModules = null,
    ) {
    }

    /**
     * Get model.
     *
     * @throws NonUniqueResultException|MappingException|InvalidArgumentException
     */
    public static function fromEntity(Configuration $configuration, InformationModel $informationModel, CoreLocatorInterface $coreLocator, ?string $locale = null): self
    {
        self::setLocator($coreLocator);

        $locale = $locale ?: self::$coreLocator->locale();
        $domains = DomainModel::fromEntity($configuration, $coreLocator, $locale);
        $locale = $domains->locale;

        if (isset(self::$cache['response'][$configuration->getId()][$locale])) {
            return self::$cache['response'][$configuration->getId()][$locale];
        }

        $allLocales = self::getContent('allLocales', $configuration, false, true);

        self::$cache['response'][$configuration->getId()][$locale] = new self(
            id: self::getContent('id', $configuration),
            entity: $configuration,
            locale: self::getContent('locale', $configuration),
            allLocales: $allLocales,
            onlineLocales: self::getContent('onlineLocales', $configuration, false, true),
            asMultiLocales: count($allLocales) > 1,
            template: self::getContent('template', $configuration),
            onlineStatus: self::getContent('onlineStatus', $configuration, true),
            seoStatus: self::getContent('seoStatus', $configuration, true),
            accessibilityStatus: self::getContent('accessibilityStatus', $configuration, true),
            preloader: self::getContent('preloader', $configuration, true),
            fullWidth: self::getContent('fullWidth', $configuration, true),
            scrollTopBtn: self::getContent('scrollTopBtn', $configuration, true),
            breadcrumb: self::getContent('breadcrumb', $configuration, true),
            subNavigation: self::getContent('subNavigation', $configuration, true),
            mediasSecondary: self::getContent('mediasSecondary', $configuration, true),
            progressiveWebApp: self::getContent('progressiveWebApp', $configuration, true),
            ipsBan: self::getContent('ipsBan', $configuration, false, true),
            ipsDev: self::getContent('ipsDev', $configuration, false, true),
            ipsCustomer: self::getContent('ipsCustomer', $configuration, false, true),
            domains: $domains->list,
            domain: $domains->default,
            pages: self::pages($configuration, $locale),
            medias: $informationModel->medias,
            logos: $informationModel->logos,
            modules: self::modules($configuration),
            transitions: TransitionModel::class::fromEntity($configuration, $coreLocator, $locale)->list,
            adminTheme: self::getContent('adminTheme', $configuration),
            buildTheme: self::getContent('buildTheme', $configuration),
            charset: self::getContent('charset', $configuration),
            customModules: (object) self::CUSTOM_MODULES,
        );

        return self::$cache['response'][$configuration->getId()][$locale];
    }

    /**
     * To get domains.
     */
    private static function pages(Configuration $configuration, string $locale): array
    {
        $filesystem = new Filesystem();
        $dirname = self::$coreLocator->cacheDir().'/pages.cache.json';

        if (!$filesystem->exists($dirname)) {
            $pageIds = [];
            foreach ($configuration->getPages() as $page) {
                $pageIds[] = $page->getId();
            }

            $dbPages = self::$coreLocator->em()->getRepository(Page::class)
                ->createQueryBuilder('p')
                ->innerJoin('p.urls', 'u')
                ->andWhere('p.id IN (:pageIds)')
                ->setParameter('pageIds', $pageIds)
                ->addSelect('u')
                ->getQuery()
                ->getResult();

            $cacheData = [];
            foreach ($dbPages as $page) {
                $slug = $page->getSlug();
                foreach ($page->getUrls() as $url) {
                    if ($url->isOnline()) {
                        $cacheData[$configuration->getId()][$url->getLocale()][$slug]['code'] = $url->getCode();
                        $cacheData[$configuration->getId()][$url->getLocale()][$slug]['path'] = $page->isAsIndex()
                            ? rtrim(self::$coreLocator->router()->generate('front_index', [], 0), '/')
                            : self::$coreLocator->router()->generate('front_index', ['url' => $url->getCode()], 0);
                    }
                }
            }

            $fp = fopen($dirname, 'w');
            fwrite($fp, json_encode($cacheData, JSON_PRETTY_PRINT));
            fclose($fp);
        }

        $jsonPages = (array) json_decode(file_get_contents($dirname));
        $configurationPages = isset($jsonPages[$configuration->getId()]) ? (array) $jsonPages[$configuration->getId()] : [];

        return !empty($configurationPages[$locale]) ? (array) $configurationPages[$locale]
            : (!empty($configurationPages[$configuration->getLocale()]) ? (array) $configurationPages[$configuration->getLocale()] : []);
    }

    /**
     * Get modules status.
     *
     * @throws InvalidArgumentException
     */
    private static function modules(Configuration $configuration): array
    {
        if (isset(self::$cache['modules'][$configuration->getId()])) {
            return self::$cache['modules'][$configuration->getId()];
        }

        $modulesActives = [];
        $isAdmin = self::$coreLocator->request() && preg_match('/\/admin-'.$_ENV['SECURITY_TOKEN'].'/', self::$coreLocator->request()->getUri());
        $currentUser = self::$coreLocator->tokenStorage()->getToken() ? self::$coreLocator->tokenStorage()->getToken()->getUser() : null;
        $roles = $currentUser ? $currentUser->getRoles() : [];
        $modulesDb = self::$coreLocator->em()->getRepository(Module::class)->findAll();
        $isInternal = $currentUser instanceof User && in_array('ROLE_INTERNAL', $roles);

        $roles = $currentUser ? $currentUser->getRoles() : [];
        foreach ($configuration->getModules() as $module) {
            $modulesActives[$module->getSlug()] = !$isAdmin || in_array($module->getRole(), $roles);
        }

        foreach ($modulesDb as $module) {
            if (!empty($modulesActives[$module->getSlug()])) {
                self::$cache['modules'][$configuration->getId()][$module->getSlug()] = $modulesActives[$module->getSlug()];
            }
        }

        self::$cache['modules'][$configuration->getId()]['delete'] = $isInternal || in_array('ROLE_DELETE', $roles);
        self::$cache['modules'][$configuration->getId()]['gdpr'] = isset($modulesActives['gdpr']);

        ksort(self::$cache['modules'][$configuration->getId()]);

        return self::$cache['modules'][$configuration->getId()];
    }
}
