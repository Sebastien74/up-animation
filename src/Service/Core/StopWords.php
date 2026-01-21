<?php

declare(strict_types=1);

namespace App\Service\Core;

use App\Twig\Translation\IntlRuntime;

/**
 * StopWords.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class StopWords
{
    /**
     * StopWords constructor.
     */
    public function __construct(
        private readonly IntlRuntime $intlRuntime,
        private readonly string $projectDir,
    ) {
    }

    /**
     * Initialize class.
     */
    public function init(): void
    {
        /** load files with classes */
        $languages = self::availableLanguages();
        foreach ($languages as $lang_name => $class_name) {
            if (!class_exists($class_name)) {
                require_once $this->projectDir.'/bin/data/stop-words/'.$lang_name.'.php';
            }
        }
    }

    /**
     * Returns an array of stop words.
     */
    public function stopWords(string $locale = 'fr'): array
    {
        $language = strtolower($this->intlRuntime->languageName($locale, 'en'));
        $languages = self::availableLanguages();
        if (!$locale || !isset($languages[$language])) {
            return [];
        }
        $class_name = $languages[$language];

        return call_user_func([$class_name, 'stopWords']);
    }

    /**
     * Returns an array of stop words by locale code.
     */
    public function stopWordsByLocale(string $locale = 'en_US'): array
    {
        $language = self::localeToLanguage($locale);

        return $this->stopWords($language);
    }

    /**
     * Returns an array of available languages.
     */
    public static function availableLanguages(): array
    {
        return [
            'czech' => 'APP_StopWords_Czech',
            'danish' => 'APP_StopWords_Danish',
            'dutch' => 'APP_StopWords_Dutch',
            'english' => 'APP_StopWords_English',
            'finnish' => 'APP_StopWords_Finnish',
            'french' => 'APP_StopWords_French',
            'german' => 'APP_StopWords_German',
            'hungarian' => 'APP_StopWords_Hungarian',
            'italian' => 'APP_StopWords_Italian',
            'norwegian' => 'APP_StopWords_Norwegian',
            'polish' => 'APP_StopWords_Polish',
            'portuguese' => 'APP_StopWords_Portuguese',
            'romanian' => 'APP_StopWords_Romanian',
            'russian' => 'APP_StopWords_Russian',
            'slovak' => 'APP_StopWords_Slovak',
            'spanish' => 'APP_StopWords_Spanish',
            'swedish' => 'APP_StopWords_Swedish',
            'turkish' => 'APP_StopWords_Turkish',
        ];
    }

    /**
     * Returns language name based on given locale code.
     */
    public static function localeToLanguage(string $locale = 'en_US'): bool|string
    {
        $languages = [
            'czech' => ['cs', 'cs_CZ'],
            'danish' => ['da', 'da_DK'],
            'dutch' => ['nl', 'nl_NL', 'nl_NL_formal'],
            'english' => ['en', 'en_AU', 'en_CA', 'en_GB', 'en_NZ', 'en_US', 'en_ZA'],
            'finnish' => ['fi', 'fi_FI'],
            'french' => ['fr', 'fr_BE', 'fr_CA', 'fr_FR'],
            'german' => ['de', 'de_CH', 'de_DE', 'de_DE_formal'],
            'hungarian' => ['hu', 'hu_HU'],
            'italian' => ['it', 'it_IT'],
            'norwegian' => ['nb', 'nn', 'nb_NO', 'nn_NO'],
            'polish' => ['pl', 'pl_PL'],
            'portuguese' => ['pt', 'pt_BR', 'pt_PT'],
            'romanian' => ['ro', 'ro_RO'],
            'russian' => ['ru', 'ru_RU'],
            'slovak' => ['sk', 'sk_SK'],
            'spanish' => ['es', 'es_AR', 'es_CL', 'es_ES', 'es_GT', 'es_MX', 'es_PE', 'es_VE'],
            'swedish' => ['sv', 'sv_SE'],
            'turkish' => ['tr', 'tr_TR'],
        ];

        foreach ($languages as $lang_name => $locales) {
            if (in_array($locale, $locales)) {
                return $lang_name;
            }
        }

        return false;
    }
}
