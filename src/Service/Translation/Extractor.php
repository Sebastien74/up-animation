<?php

declare(strict_types=1);

namespace App\Service\Translation;

use App\Entity\Core\Entity;
use App\Entity\Core\Website;
use App\Entity\Translation\Translation;
use App\Entity\Translation\TranslationDomain;
use App\Entity\Translation\TranslationUnit;
use App\Repository\Translation\TranslationDomainRepository;
use App\Repository\Translation\TranslationRepository;
use App\Repository\Translation\TranslationUnitRepository;
use App\Service\Core\InterfaceHelper;
use App\Service\Development\EntityService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Extractor.
 *
 * To extract translations
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class Extractor
{
    private TranslationDomainRepository $domainRepository;
    private TranslationUnitRepository $unitRepository;
    private TranslationRepository $translationRepository;
    private array $interfaceNames = [];
    private array $entityFields = ['plural', 'singular', 'add'];

    /**
     * Extractor constructor.
     *
     * @throws NonUniqueResultException
     */
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly string $projectDir,
        private readonly string $cacheDir,
        private readonly EntityManagerInterface $entityManager,
        private readonly EntityService $entityService,
        private readonly InterfaceHelper $interfaceHelper,
    ) {
        $this->domainRepository = $this->entityManager->getRepository(TranslationDomain::class);
        $this->unitRepository = $this->entityManager->getRepository(TranslationUnit::class);
        $this->translationRepository = $this->entityManager->getRepository(Translation::class);
        $this->interfaceNames();
    }

    /**
     * Extract default locales translations.
     *
     * @throws \Exception
     */
    public function extract(string $locale): void
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $input = new ArrayInput([
            'command' => 'translation:extract',
            'locale' => $locale,
            '--format' => 'yaml',
            '--force' => true,
        ]);
        $output = new BufferedOutput();
        $application->run($input, $output);
    }

    /**
     * Get yaml files.
     */
    public function findYaml(array $locales): array
    {
        $translations = [];
        $finder = Finder::create();
        $finder->files()->in($this->projectDir.DIRECTORY_SEPARATOR.'translations');
        foreach ($finder as $file) {
            $matches = explode('.', $file->getFilename());
            $domain = str_replace('+intl-icu', '', $matches[0]);
            $locale = $matches[1];
            if ($domain && '.gitkeep' !== $file->getFilename() && in_array($locale, $locales) && preg_match('/.yaml/', $file->getPathname())) {
                $translations[$domain][$locale] = Yaml::parseFile($file->getPathname());
            }
        }

        return $translations;
    }

    /**
     * Set DomainModel.
     */
    public function domain(string $domainName): TranslationDomain
    {
        $domain = $this->domainRepository->findOneBy(['name' => $domainName]);
        $entityName = str_replace(['entity_', '+intl-icu'], ['', ''], $domainName);
        $toExtract = ['gdpr', 'build', 'ie_alert', 'email', 'exception', 'messages', 'security', 'security_cms'];

        $adminName = $domainName;
        if (str_contains($domainName, 'entity_') && !empty($this->interfaceNames[$entityName])) {
            $adminName = $this->interfaceNames[$entityName];
        }

        $adminName = $this->getDomainAdminName($adminName);

        if (!$domain) {
            $domain = new TranslationDomain();
            $domain->setName($domainName);
            $domain->setAdminName(ltrim($adminName, '__'));
        }

        if (in_array($domainName, $toExtract) || str_contains($domainName, 'front')) {
            $domain->setExtract(true);
            $domain->setForTranslator(true);
        }

        $this->entityManager->persist($domain);
        $this->entityManager->flush();

        return $domain;
    }

    /**
     * Set Unit.
     */
    private function unit(TranslationDomain $domain, string $keyName): TranslationUnit
    {
        $unit = $this->unitRepository->findOneBy(['domain' => $domain, 'keyName' => $keyName]);
        if (!$unit) {
            $unit = new TranslationUnit();
            $unit->setKeyname($keyName);
            $unit->setDomain($domain);
            $this->entityManager->persist($unit);
            $this->entityManager->flush();
        }

        return $unit;
    }

    /**
     * Set Translation.
     */
    public function generateTranslation(string $defaultLocale, string $locale, string $domain, ?string $content = null, ?string $keyName = null): bool
    {
        $vendorDomains = ['validators', 'security'];
        $defaultDomains = ['admin', 'dev', 'exception', 'form_widget', 'gdpr', 'js_plugins', 'security_cms', 'time', 'validators_cms', 'domain'];
        $disallowed = ['entity_', '_undefined', 'delete_'];

        if ($keyName && !in_array($domain, $disallowed)) {
            $domain = $this->domain($domain);
            $isNew = $content ? str_starts_with($content, '__') : $content;
            $contentFormatted = $content ? ltrim($content, '__') : $content;
            $unit = $this->unit($domain, $keyName);
            $translation = $this->existingTranslation($unit, $locale);
            $isEntityConfiguration = str_contains($domain->getName(), 'entity_');
            $asDefault = false;
            $isYamlConfig = false;
            $contentLocale = $this->getAppYamlTranslation($domain, $keyName, $locale);

            if (!$contentLocale) {
                $contentLocale = (!$translation && $isEntityConfiguration)
                || (($isNew || !$translation) && !in_array($domain->getName(), $defaultDomains) && $locale == $defaultLocale)
                || (($isNew || !$translation) && in_array($domain->getName(), $defaultDomains) && 'fr' == $locale)
                || (!$translation && in_array($domain->getName(), $vendorDomains))
                    ? $contentFormatted : ($translation instanceof Translation ? $translation->getContent() : null);
            } else {
                $contentFormatted = $contentLocale;
                $isYamlConfig = true;
            }

            if (!$contentLocale) {
                $contentFormatted = $this->getReferLocaleContent($unit, $locale, $defaultLocale);
                $asDefault = !empty($contentFormatted);
            }

            if (!$translation) {
                $this->translation($unit, $locale, $contentLocale);
            }

            if ($translation && !$translation->getContent()
                && ($contentFormatted && !in_array($domain->getName(), $defaultDomains) && $locale == $defaultLocale
                    || $contentFormatted && in_array($domain->getName(), $defaultDomains) && 'fr' == $locale
                    || $isEntityConfiguration
                    || $isYamlConfig
                    || $asDefault
                )) {
                $translation->setContent($contentFormatted);
                $this->entityManager->persist($translation);
                $this->entityManager->flush();
            }
        }

        return true;
    }

    /**
     * Get Default App Translation Yaml (/bin/data/translations).
     */
    private function getAppYamlTranslation(TranslationDomain $domain, string $keyName, string $locale): ?string
    {
        $filesystem = new Filesystem();
        $baseDirname = $this->projectDir.'/bin/data/translations/';
        $baseDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $baseDirname);

        $filePath = $baseDirname.$domain->getName().'+intl-icu.'.$locale.'.yaml';
        if ($filesystem->exists($filePath)) {
            $values = Yaml::parseFile($filePath);
            if (!empty($values[$keyName])) {
                return $values[$keyName];
            }
        }

        $filePath = $baseDirname.$domain->getName().'.'.$locale.'.yaml';
        if ($filesystem->exists($filePath)) {
            $values = Yaml::parseFile($filePath);
            if (!empty($values[$keyName])) {
                return $values[$keyName];
            }
        }

        $filePath = $baseDirname.'translations.'.$locale.'.yaml';
        if ($filesystem->exists($filePath)) {
            $values = Yaml::parseFile($filePath);
            if (!empty($values[$keyName])) {
                return $values[$keyName];
            }
        }

        return null;
    }

    /**
     * Generate translations for Entity configuration.
     *
     * @throws NonUniqueResultException
     */
    public function extractEntities(Website $website, string $defaultLocale, array $locales): void
    {
        foreach ($locales as $locale) {
            $this->entityService->execute($website, $locale);
        }

        $entities = $this->entityManager->getRepository(Entity::class)->findAll();
        $values = $this->getCoreValues();

        foreach ($entities as $entity) {
            $this->interfaceHelper->setInterface($entity->getClassName());
            $interface = $this->interfaceHelper->getInterface();
            $interfaceName = !empty($interface['name']) ? $interface['name'] : null;
            $config = !empty($values[$entity->getClassName()])
                ? $values[$entity->getClassName()] : [];
            if ($interfaceName) {
                $translationsEntities = $this->translationsEntities($interface, $config, $locales);
                foreach ($translationsEntities as $locale => $translations) {
                    $domainName = 'entity_'.$interfaceName.'+intl-icu.'.$locale.'.yaml';
                    $filePath = $this->projectDir.'/translations/'.$domainName;
                    $filePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);
                    ksort($translations);
                    $yaml = Yaml::dump($translations);
                    file_put_contents($filePath, $yaml);
                }
            }
        }
    }

    /**
     * Get core values.
     */
    private function getCoreValues(): array
    {
        $values = [];
        $coreDirname = $this->projectDir.'/bin/data/fixtures/';
        $coreDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $coreDirname);
        $imports = Yaml::parseFile($coreDirname.'entity-configuration.yaml')['imports'];
        foreach ($imports as $import) {
            $values = array_merge($values, Yaml::parseFile($coreDirname.$import['resource']));
        }

        return $values;
    }

    /**
     * Get Entities Translations.
     */
    private function translationsEntities(array $interface = [], array $config = [], array $allLocales = []): array
    {
        $translations = [];

        foreach ($allLocales as $locale) {
            foreach ($this->entityFields as $field) {
                $translations[$locale][$field] = null;
            }
        }

        if (!empty($interface['labels'])) {
            foreach ($interface['labels'] as $keyName => $value) {
                $translations['fr'][$keyName] = $value;
            }
        }

        if (!empty($config['translations'])) {
            foreach ($config['translations'] as $keyName => $locales) {
                foreach ($locales as $locale => $value) {
                    $translations[$locale][$keyName] = $value;
                }
            }
        }

        foreach ($translations as $translation) {
            foreach ($translation as $keyName => $content) {
                foreach ($allLocales as $locale) {
                    if (!isset($translations[$locale][$keyName])) {
                        $translations[$locale][$keyName] = null;
                    }
                }
            }
        }

        return $translations;
    }

    /**
     * Check if translation already exist.
     */
    private function existingTranslation(TranslationUnit $unit, string $locale): ?Translation
    {
        return $this->translationRepository->findOneBy([
            'unit' => $unit,
            'locale' => $locale,
        ]);
    }

    /**
     * To get refer locale content.
     */
    private function getReferLocaleContent(TranslationUnit $unit, string $locale, string $defaultLocale): ?string
    {
        $localesMatches = explode('_', $locale);
        $locale = 2 === count($localesMatches) ? $localesMatches[0] : $locale;

        $referLocalTranslation = $this->entityManager->getRepository(Translation::class)->findOneBy([
            'unit' => $unit,
            'locale' => $locale,
        ]);

        if (!$referLocalTranslation) {
            $referLocalTranslation = $this->entityManager->getRepository(Translation::class)->findOneBy([
                'unit' => $unit,
                'locale' => $defaultLocale,
            ]);
        }

        if ($referLocalTranslation instanceof Translation && $referLocalTranslation->getContent()) {
            return $referLocalTranslation->getContent();
        }

        return null;
    }

    /**
     * Generate DB Translation.
     */
    private function translation(TranslationUnit $unit, string $locale, ?string $content = null): void
    {
        $content = $content ? str_replace(['{{', '}}'], ['{', '}'], $content) : $content;
        $translation = new Translation();
        $translation->setLocale($locale);
        $translation->setContent($content);
        $translation->setUnit($unit);
        $this->entityManager->persist($translation);
        $this->entityManager->flush();
    }

    /**
     * Remove extract yaml files & generate .db files.
     */
    public function initFiles(array $locales): void
    {
        $filesystem = new Filesystem();
        $baseDirname = $this->projectDir.'/translations/';
        $baseDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $baseDirname);

        /** Remove extract yaml files */
        $finder = Finder::create();
        $finder->in($baseDirname)->name('*.yaml')->name('*.yml');
        $filesystem->remove($finder);

        /** Add .db files */
        $domains = $this->domainRepository->findAll();
        foreach ($domains as $domain) {
            foreach ($locales as $locale) {
                $fileDbPath = $baseDirname.$domain->getName().'.'.$locale.'.db';
                file_put_contents($fileDbPath, '');
            }
        }
    }

    /**
     * Clear cache.
     */
    public function clearCache(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove([$this->cacheDir.'/translations']);
        $filesystem->remove([$this->cacheDir.'/website']);
    }

    /**
     * Get domain AdminName.
     */
    private function getDomainAdminName(string $domain): string
    {
        $domain = str_replace('+intl-icu', '', $domain);
        $domains = [
            'admin' => 'Administration',
            'domain' => 'Symfony domain',
            'error' => 'Erreur',
            'exception' => 'Exception',
            'front_default' => 'Site principal',
            'front_webmaster' => 'Webmaster tools box',
            'front' => 'Site',
            'front_form' => 'Formulaires site',
            'front_js_plugins' => 'Plugins javaScript (Site)',
            'gdpr' => 'Gestion des Cookies',
            'admin_js_plugins' => 'Plugins javaScript (Administration)',
            'admin_breadcrumb' => "Fil d'Ariane (Administration)",
            'security' => 'Sécurité',
            'security_cms' => 'Sécurité CMS',
            'time' => 'Extension doctrine',
            'validators' => 'Validations',
            'validators_cms' => 'Validations CMS',
            'build' => 'Page de maintenance',
            'email' => 'Emails',
            'ie_alert' => 'Alerte Internet Explorer',
            'messages' => 'Général',
        ];

        return !empty($domains[$domain]) ? $domains[$domain] : $domain;
    }

    /**
     * Set Interface names.
     *
     * @throws NonUniqueResultException
     */
    private function interfaceNames(): void
    {
        $metasData = $this->entityManager->getMetadataFactory()->getAllMetadata();
        foreach ($metasData as $metadata) {
            if (!str_contains($metadata->getName(), 'Base')) {
                $this->interfaceHelper->setInterface($metadata->getName());
                $interface = $this->interfaceHelper->getInterface();
                if (!empty($interface['name']) && !empty($interface['classname'])) {
                    $adminName = !empty($interface['configuration']) && is_object($interface['configuration']) && property_exists($interface['configuration'], 'adminName')
                        ? $interface['configuration']->adminName : $interface['classname'];
                    $this->interfaceNames[$interface['name']] = $adminName;
                }
            }
        }
    }
}
