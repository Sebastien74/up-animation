<?php

declare(strict_types=1);

namespace App\Twig\Translation;

use App\Entity\Translation\TranslationDomain;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * StatsRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class StatsRuntime implements RuntimeExtensionInterface
{
    private array $stats = [];

    /**
     * Get count of words for all domains by locale.
     */
    public function transStats(array $domains): array
    {
        $this->stats = [];

        foreach ($domains as $translationDomain) {
            $this->domainStats($translationDomain);
        }

        return $this->stats;
    }

    /**
     * Get count of words of TranslationDomain by locale.
     */
    public function domainStats(TranslationDomain $translationDomain): array
    {
        $accents = '/&([A-Za-z]{1,2})(grave|acute|circ|cedil|uml|lig);/';

        foreach ($translationDomain->getUnits() as $unit) {
            foreach ($unit->getTranslations() as $translation) {
                $locale = $translation->getLocale();
                $keyName = strip_tags($translation->getUnit()->getKeyname());
                $encoding = htmlentities($keyName, ENT_NOQUOTES, 'UTF-8');
                $encoding = preg_replace($accents, '$1', $encoding);
                $encoding = str_replace(['_'], [''], $encoding);
                $wordsCount = str_word_count($encoding, 0);

                $localeCount = empty($this->stats[$locale]['words'])
                    ? 0 : $this->stats[$locale]['words'];
                $localeDomainCount = empty($this->stats[$translationDomain->getName()][$locale]['words'])
                    ? 0 : $this->stats[$translationDomain->getName()][$locale]['words'];
                $this->stats[$translationDomain->getName()][$locale]['words'] = $localeDomainCount + $wordsCount;
                $this->stats[$locale]['words'] = $localeCount + $wordsCount;
                $this->stats['keywords'][$keyName] = $wordsCount;

                if (!isset($this->stats[$translationDomain->getName()]['units'][$locale])) {
                    $this->stats[$translationDomain->getName()]['units'][$locale] = 0;
                }

                $translationCount = empty($this->stats[$translationDomain->getName()]['units'][$locale])
                    ? 0 : $this->stats[$translationDomain->getName()]['units'][$locale];

                $translationCountAll = empty($this->stats[$translationDomain->getName()]['units']['count'][$locale])
                    ? 0 : $this->stats[$translationDomain->getName()]['units']['count'][$locale];

                if ($translation->getContent()) {
                    $this->stats[$translationDomain->getName()]['units'][$locale] = $translationCount + 1;
                }

                $this->stats[$translationDomain->getName()]['units']['count'][$locale] = $translationCountAll + 1;
            }
        }

        return $this->stats;
    }
}
