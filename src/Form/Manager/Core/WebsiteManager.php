<?php

declare(strict_types=1);

namespace App\Form\Manager\Core;

use App\Entity\Core as CoreEntities;
use App\Entity\Information\Email;
use App\Entity\Layout as LayoutEntities;
use App\Entity\Module\Menu as MenuEntities;
use App\Entity\Module\Search\Search;
use App\Entity\Security\User;
use App\Service\Interface\CoreLocatorInterface;
use App\Service\Interface\DataFixturesInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Form;

/**
 * WebsiteManager.
 *
 * Manage admin WebsiteModel form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => WebsiteManager::class, 'key' => 'core_website_form_manager'],
])]
class WebsiteManager
{
    private ?User $user;

    /**
     * WebsiteManager constructor.
     */
    public function __construct(
        private readonly DataFixturesInterface $fixtures,
        private readonly CoreLocatorInterface $coreLocator,
    ) {
        $this->user = $coreLocator->user();
    }

    /**
     * @prePersist
     *
     * @throws \Exception
     */
    public function prePersist(CoreEntities\Website $website, CoreEntities\Website $currentWebsite, array $interface, Form $form): void
    {
        $this->fixtures->website()->initialize($website, $website->getConfiguration()->getLocale(), null, $form->get('yaml_config')->getData(), $form->get('website_to_duplicate')->getData());
    }

    /**
     * @preUpdate
     *
     * @throws \Exception
     */
    public function preUpdate(CoreEntities\Website $website): void
    {
        $configuration = $website->getConfiguration();
        $locale = $configuration->getLocale();
        $locales = $configuration->getLocales();

        /* Remove default locale if in locales */
        if (in_array($locale, $locales)) {
            unset($locales[array_search($locale, $locales)]);
            $configuration->setLocales($locales);
        }

        $this->setModules($configuration);
        $this->setLayoutConfiguration($configuration);
        $this->setGdpr($website, $configuration);
        $this->setSecurity($website, $configuration);
        $this->setEmails($website, $locale, $locales);
        $this->setFramework($configuration);
        $this->addPages($website, $configuration);
        $this->cacheDomains($configuration);
    }

    /**
     * To set modules.
     */
    private function setModules(CoreEntities\Configuration $configuration): void
    {
        $associateModules = [
            'form-calendar' => 'form',
        ];

        foreach ($associateModules as $code => $associateCode) {
            foreach ($configuration->getModules() as $module) {
                if ($module->getSlug() === $code) {
                    $associateModuleExist = false;
                    foreach ($configuration->getModules() as $moduleDb) {
                        if ($moduleDb->getSlug() === $associateCode) {
                            $associateModuleExist = true;
                            break;
                        }
                    }
                    if (!$associateModuleExist) {
                        $associateModule = $this->coreLocator->em()->getRepository(CoreEntities\Module::class)->findOneBy(['slug' => $associateCode]);
                        if ($associateModule instanceof CoreEntities\Module) {
                            $configuration->addModule($associateModule);
                        }
                    }
                }
            }
        }
    }

    /**
     * To set LayoutConfiguration Page.
     */
    private function setLayoutConfiguration(CoreEntities\Configuration $configuration): void
    {
        $layoutConfiguration = $this->coreLocator->em()->getRepository(LayoutEntities\LayoutConfiguration::class)->findOneBy([
            'website' => $configuration->getWebsite(),
            'entity' => LayoutEntities\Page::class,
        ]);

        $modulesNotInLayout = ['edit', 'information', 'medias', 'newsletter', 'navigation', 'customs-actions', 'user', 'gdpr', 'seo', 'css', 'edit', 'translation', 'secure-page', 'secure-module', 'google-analytics'];
        $modulesInLayout = [];
        $blockTypesNotInLayout = [];
        $blockTypesInLayout = [];
        if ($layoutConfiguration instanceof LayoutEntities\LayoutConfiguration) {
            foreach ($layoutConfiguration->getModules() as $module) {
                $modulesInLayout[] = $module->getSlug();
            }
            foreach ($layoutConfiguration->getBlockTypes() as $blockType) {
                $blockTypesInLayout[] = $blockType->getSlug();
            }
        }
        foreach ($configuration->getModules() as $module) {
            if (!in_array($module->getSlug(), $modulesInLayout) && !in_array($module->getSlug(), $modulesNotInLayout)) {
                $layoutConfiguration->addModule($module);
                $this->coreLocator->em()->persist($layoutConfiguration);
            }
        }
        foreach ($configuration->getBlockTypes() as $blockType) {
            if (!in_array($blockType->getSlug(), $blockTypesInLayout)
                && !in_array($blockType->getSlug(), $blockTypesNotInLayout)
                && !str_contains($blockType->getSlug(), 'form-')
                && !str_contains($blockType->getSlug(), 'layout-')) {
                $layoutConfiguration->addBlockType($blockType);
                $this->coreLocator->em()->persist($layoutConfiguration);
            }
        }
    }

    /**
     * To active GDPR contents.
     */
    private function setGdpr(CoreEntities\Website $website, CoreEntities\Configuration $configuration): void
    {
        $gdprActive = $this->moduleActive($configuration, 'gdpr');
        if ($gdprActive) {
            $footerMenu = $this->getMenu($website, 'footer');
            $cookiesPage = $this->getPage($website, 'cookies');
            /* Active urls */
            if ($cookiesPage) {
                foreach ($cookiesPage->getUrls() as $url) {
                    $url->setOnline(true);
                }
            }
            if ($footerMenu && $cookiesPage) {
                foreach ($configuration->getOnlineLocales() as $locale) {
                    $existingLink = $this->coreLocator->em()->getRepository(MenuEntities\Link::class)->findByPageAndLocale($cookiesPage, $locale);
                    if (!$existingLink) {
                        $this->addLink($footerMenu, $locale, $cookiesPage, $website);
                    }
                }
            }
        }
    }

    /**
     * To set security.
     *
     * @throws \Exception
     */
    private function setSecurity(CoreEntities\Website $website, CoreEntities\Configuration $configuration): void
    {
        $security = $website->getSecurity();
        $websites = $this->coreLocator->em()->getRepository(CoreEntities\Website::class)->findAll();

        foreach ($websites as $websiteDb) {
            if ($websiteDb->getId() !== $website->getId()) {
                /** @var CoreEntities\Security $securityDb */
                $securityDb = $websiteDb->getSecurity();
                $securityDb->setAdminPasswordDelay($security->getAdminPasswordDelay());
                $this->coreLocator->em()->persist($securityDb);
            }
        }
    }

    /**
     * To set required locales emails.
     */
    private function setEmails(CoreEntities\Website $website, string $locale, array $locales): void
    {
        $slugs = ['support', 'no-reply'];
        $information = $website->getInformation();
        $defaultLocaleEmails = [];

        foreach ($information->getEmails() as $email) {
            if ($email->getLocale() === $locale && in_array($email->getSlug(), $slugs)) {
                $defaultLocaleEmails[$email->getSlug()] = $email;
            }
        }

        foreach ($slugs as $slug) {
            $existing = false;

            foreach ($locales as $locale) {
                foreach ($information->getEmails() as $email) {
                    if ($email->getLocale() === $locale && $email->getSlug() === $slug) {
                        $existing = true;
                    }
                }

                if (!$existing && !empty($defaultLocaleEmails[$slug])) {
                    $newEmail = new Email();
                    $newEmail->setSlug($slug);
                    $newEmail->setLocale($locale);
                    $newEmail->setEmail($defaultLocaleEmails[$slug]->getEmail());
                    $newEmail->setZones($defaultLocaleEmails[$slug]->getZones());
                    $newEmail->setDeletable(false);
                    $information->addEmail($newEmail);
                    $this->coreLocator->em()->persist($information);
                }
            }
        }
    }

    /**
     * Set cache Domains.
     */
    private function cacheDomains(CoreEntities\Configuration $configuration): void
    {
        $dirname = $this->coreLocator->cacheDir().'/domains.cache.json';
        $filesystem = new Filesystem();
        if ($filesystem->exists($dirname)) {
            $filesystem->remove($dirname);
            $domains = $this->coreLocator->em()->getRepository(CoreEntities\Domain::class)
                ->createQueryBuilder('d')
                ->andWhere('d.configuration = :configuration')
                ->setParameter('configuration', $configuration)
                ->getQuery()
                ->getResult();
            $cacheData = [];
            foreach ($domains as $domain) {
                $cacheData[$configuration->getId()][$domain->getLocale()][] = [
                    'name' => $domain->getName(),
                    'locale' => $domain->getLocale(),
                    'asDefault' => $domain->isAsDefault(),
                ];
            }
            $fp = fopen($dirname, 'w');
            fwrite($fp, json_encode($cacheData, JSON_PRETTY_PRINT));
            fclose($fp);
        }
    }

    /**
     * Set Yaml framework configuration.
     */
    private function setFramework(CoreEntities\Configuration $configuration): void
    {
        //        $allLocales = $configuration->getAllLocales();
        //
        //        $filePath = $this->projectDir . '/config/packages/translation.yaml';
        //        $filePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);
        //        $filesystem = new Filesystem();
        //
        //        if ($filesystem->exists($filePath)) {
        //
        //            $values = Yaml::parseFile($filePath);
        //            $enabledLocales = $values['framework']['translator']['enabled_locales'];
        //
        //            $yamlLocalesCount = 0;
        //            foreach ($enabledLocales as $locale) {
        //                if (in_array($locale, $allLocales)) {
        //                    $yamlLocalesCount++;
        //                }
        //            }
        //
        //            if ($yamlLocalesCount !== count($allLocales)) {
        //                $values['framework']['translator']['enabled_locales'] = $allLocales;
        //                $yaml = Yaml::dump($values);
        //                file_put_contents($filePath, $yaml);
        //                if ($this->kernel->getEnvironment() === 'prod') {
        //                    $filesystem->remove([$this->kernel->getCacheDir()]);
        //                }
        //            }
        //        }
    }

    /**
     * To add associated pages Module.
     *
     * @throws \Exception
     */
    private function addPages(CoreEntities\Website $website, CoreEntities\Configuration $configuration): void
    {
        $security = $website->getSecurity();
        $pagesToCreate = [
//            'secure-page' => [['name' => 'Mon compte', 'asIndex' => false, 'reference' => 'user-dashboard', 'menu' => false, 'template' => 'cms', 'urlAsIndex' => false, 'deletable' => true, 'secure' => true]],
            'search' => [['name' => 'Page de résultats', 'asIndex' => false, 'reference' => 'search-results', 'menu' => false, 'template' => 'cms', 'urlAsIndex' => false, 'deletable' => true, 'secure' => false]],
        ];
        foreach ($pagesToCreate as $slugModule => $pageParams) {
            if ($this->moduleActive($configuration, $slugModule)) {
                $pages = $this->fixtures->page()->add($website, $pageParams, $this->user, false);
                foreach ($pages as $slug => $page) {
                    if ('user-dashboard' === $slug) {
                        $security->setFrontPageRedirection($page);
                    } elseif ('search-results' === $slug) {
                        $search = $this->coreLocator->em()->getRepository(Search::class)->findOneBy(['slug' => 'main', 'website' => $website]);
                        $search->setResultsPage($page);
                        $this->coreLocator->em()->persist($search);
                    }
                }
            }
        }

        if (!$configuration->isOnlineStatus()) {
            $reference = 'in-build';
            $pageParams = [['name' => 'En maintenance', 'asIndex' => false, 'reference' => $reference, 'menu' => false, 'template' => 'build', 'urlAsIndex' => false, 'deletable' => true, 'secure' => false]];
            $pages = $this->fixtures->page()->add($website, $pageParams, $this->user, false);
            $page = !empty($pages[$reference]) ? $pages[$reference] : null;
            if ($page instanceof LayoutEntities\Page) {
                $layout = $page->getLayout();
                foreach ($layout->getZones() as $zone) {
                    $layout->removeZone($zone);
                    $this->coreLocator->em()->persist($layout);
                }
                foreach ($page->getUrls() as $url) {
                    $url->setAsIndex(false);
                    $url->setHideInSitemap(true);
                    $this->coreLocator->em()->persist($url);
                }
            }
        }
    }

    /**
     * Check if module is activated.
     */
    private function moduleActive(CoreEntities\Configuration $configuration, string $slug): bool
    {
        $active = false;
        foreach ($configuration->getModules() as $module) {
            if ($module->getSlug() === $slug) {
                $active = true;
                break;
            }
        }

        return $active;
    }

    /**
     * Get page by slug.
     */
    private function getMenu(CoreEntities\Website $website, string $slug): ?MenuEntities\Menu
    {
        return $this->coreLocator->em()->getRepository(MenuEntities\Menu::class)->findOneBy([
            'website' => $website,
            'slug' => $slug,
        ]);
    }

    /**
     * Get page by slug.
     */
    private function getPage(CoreEntities\Website $website, string $slug): mixed
    {
        return $this->coreLocator->em()->getRepository(LayoutEntities\Page::class)->findOneBy([
            'website' => $website,
            'slug' => $slug,
        ]);
    }

    /**
     * Add Link to Menu.
     */
    private function addLink(MenuEntities\Menu $menu, string $locale, LayoutEntities\Page $page, CoreEntities\Website $website): void
    {
        $linkPosition = count($this->coreLocator->em()->getRepository(MenuEntities\Link::class)->findBy([
            'menu' => $menu,
            'locale' => $locale,
        ])) + 1;

        $intl = new MenuEntities\LinkIntl();
        $intl->setLocale($locale);
        $intl->setWebsite($website);
        $intl->setTitle($page->getAdminName());
        $intl->setTargetPage($page);

        $link = new MenuEntities\Link();
        $link->setAdminName($page->getAdminName());
        $link->setMenu($menu);
        $link->setPosition($linkPosition);
        $link->setLocale($locale);
        $link->setIntl($intl);
        $intl->setLink($link);

        $this->coreLocator->em()->persist($intl);
        $this->coreLocator->em()->persist($link);
    }
}
