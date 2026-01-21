<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core as CoreEntities;
use App\Entity\Layout\CssClass;
use App\Entity\Security\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * ConfigurationFixtures.
 *
 * ConfigurationModel Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ConfigurationFixtures::class, 'key' => 'config_fixtures'],
])]
class ConfigurationFixtures
{
    private const bool DISABLED_GDPR = true;
    private const array CUSTOM_CLASSES = [];

    private array $yamlConfiguration = [];

    /**
     * ConfigurationFixtures constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Add ConfigurationModel.
     *
     * @throws \Exception
     */
    public function add(
        CoreEntities\Website $website,
        array $yamlConfiguration,
        string $locale,
        bool $devMode,
        array $defaultsModules,
        array $othersModules,
        ?User $user = null,
        ?CoreEntities\Website $websiteToDuplicate = null): CoreEntities\Configuration
    {
        $this->yamlConfiguration = $yamlConfiguration;

        $configuration = $this->addConfiguration($locale, $website, $yamlConfiguration, $user);

        $this->addModules($configuration, $devMode, $defaultsModules, $othersModules, $websiteToDuplicate);
        $this->addDomains($configuration);
        $this->addClasses($configuration, $websiteToDuplicate);

        $website->setConfiguration($configuration);

        $this->entityManager->persist($configuration);

        return $configuration;
    }

    /**
     * Add ConfigurationModel.
     */
    private function addConfiguration(string $locale, CoreEntities\Website $website, array $yamlConfiguration, ?User $user = null): CoreEntities\Configuration
    {
        $template = !empty($yamlConfiguration['template']) ? $yamlConfiguration['template']
            : ($website->getConfiguration() instanceof CoreEntities\Configuration ? $website->getConfiguration()->getTemplate() : 'default');
        $locales = !empty($this->yamlConfiguration['locales_others']) ? $this->yamlConfiguration['locales_others']
            : ($website->getConfiguration() instanceof CoreEntities\Configuration ? $website->getConfiguration()->getLocales() : []);
        $onlineLocales = !empty($locales) ? array_merge($locales, [$locale]) : [$locale];

        $configuration = $website->getConfiguration() instanceof CoreEntities\Configuration ? $website->getConfiguration() : new CoreEntities\Configuration();
        $configuration->setLocale($locale);
        $configuration->setLocales($locales);
        $configuration->setOnlineLocales($onlineLocales);
        $configuration->setTemplate($template);
        $configuration->setCreatedBy($user);
        $configuration->setWebsite($website);

        return $configuration;
    }

    /**
     * Add Modules.
     */
    private function addModules(CoreEntities\Configuration $configuration, bool $devMode, array $defaultsModules, array $othersModules, ?CoreEntities\Website $websiteToDuplicate = null): void
    {
        if ($websiteToDuplicate instanceof CoreEntities\Website) {
            foreach ($websiteToDuplicate->getConfiguration()->getModules() as $module) {
                $configuration->addModule($module);
            }
        } else {
            $modules = $this->entityManager->getRepository(CoreEntities\Module::class)->findAll();
            $modulesToAdd = $devMode ? array_merge($defaultsModules, $othersModules) : $defaultsModules;
            foreach ($modules as $module) {
                /** @var CoreEntities\Module $module */
                if (in_array($module->getRole(), $modulesToAdd) || 'gdpr' === $module->getSlug() && !self::DISABLED_GDPR) {
                    $configuration->addModule($module);
                }
            }
        }
    }

    /**
     * Add Domains.
     */
    private function addDomains(CoreEntities\Configuration $configuration): void
    {
        if (!empty($this->yamlConfiguration['domains']) && is_array($this->yamlConfiguration['domains'])) {
            $repository = $this->entityManager->getRepository(CoreEntities\Domain::class);
            $position = count($repository->findBy(['configuration' => $configuration])) + 1;
            foreach ($this->yamlConfiguration['domains'] as $locale => $domains) {
                foreach ($domains as $domainName => $asDefault) {
                    $existing = $repository->findBy(['name' => $domainName]);
                    if (!$existing) {
                        $domain = new CoreEntities\Domain();
                        $domain->setName($domainName);
                        $domain->setLocale($locale);
                        $domain->setPosition($position);
                        $domain->setAsDefault($asDefault);
                        $configuration->addDomain($domain);
                        ++$position;
                    }
                }
            }
        }
    }

    /**
     * Add class.
     */
    private function addClasses(CoreEntities\Configuration $configuration, ?CoreEntities\Website $websiteToDuplicate = null): void
    {
        if ($websiteToDuplicate instanceof CoreEntities\Website) {
            foreach ($websiteToDuplicate->getConfiguration()->getCssClasses() as $referClass) {
                $class = new CssClass();
                $class->setName($referClass->getName());
                $class->setDescription($referClass->getDescription());
                $configuration->addCssClass($class);
            }
        } else {
            foreach (self::CUSTOM_CLASSES as $classname) {
                $class = new CssClass();
                $class->setName($classname);
                $configuration->addCssClass($class);
            }
        }
    }
}
