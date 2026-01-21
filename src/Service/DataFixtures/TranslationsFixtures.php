<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core\Configuration;
use App\Service\Translation\Extractor;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * TranslationsFixtures.
 *
 * Translations Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => TranslationsFixtures::class, 'key' => 'translations_fixtures'],
])]
class TranslationsFixtures
{
    /**
     * TranslationsFixtures constructor.
     */
    public function __construct(private readonly Extractor $extractor)
    {
    }

    /**
     * Generate translations.
     *
     * @throws \Exception
     */
    public function generate(Configuration $configuration, array $websites): void
    {
        $allLocales = $configuration->getAllLocales();
        if (0 === count($websites)) {
            foreach ($allLocales as $locale) {
                $this->extractor->extract($locale);
            }
            $yamlTranslations = $this->extractor->findYaml($allLocales);
            foreach ($yamlTranslations as $domain => $localeTranslations) {
                foreach ($localeTranslations as $locale => $translations) {
                    foreach ($translations as $keyName => $content) {
                        $this->extractor->generateTranslation($configuration->getLocale(), $locale, $domain, $content, strval($keyName));
                    }
                }
            }
            $this->extractor->initFiles($allLocales);
        }
    }
}
