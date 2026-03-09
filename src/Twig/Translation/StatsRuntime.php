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
    private array $cache = [];

    /**
     * Get count of words for all domains by locale.
     */
    public function transStats(array $domains): array
    {
        $this->stats = [];
        $ids = [];
        foreach ($domains as $d) {
            if ($d instanceof TranslationDomain) {
                $ids[] = $d->getId();
            }
        }
        sort($ids);
        $cacheKey = md5(implode(',', $ids));

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        foreach ($domains as $translationDomain) {
            $this->domainStats($translationDomain);
        }

        return $this->cache[$cacheKey] = $this->stats;
    }

    /**
     * Get count of words of TranslationDomain by locale.
     */
    public function domainStats(TranslationDomain $translationDomain): array
    {
        $accents = '/&([A-Za-z]{1,2})(grave|acute|circ|cedil|uml|lig);/';
        $domainName = $translationDomain->getName();

        foreach ($translationDomain->getUnits() as $unit) {
            $keyName = strip_tags($unit->getKeyname());
            $encodingBase = htmlentities($keyName, ENT_NOQUOTES, 'UTF-8');
            $encodingBase = preg_replace($accents, '$1', $encodingBase);
            $encodingBase = str_replace(['_'], [''], $encodingBase);
            
            foreach ($unit->getTranslations() as $translation) {
                $locale = $translation->getLocale();
                $wordsCount = str_word_count($encodingBase, 0);

                if (!isset($this->stats[$locale]['words'])) {
                    $this->stats[$locale]['words'] = 0;
                }
                if (!isset($this->stats[$domainName][$locale]['words'])) {
                    $this->stats[$domainName][$locale]['words'] = 0;
                }
                
                $this->stats[$domainName][$locale]['words'] += $wordsCount;
                $this->stats[$locale]['words'] += $wordsCount;
                $this->stats['keywords'][$keyName] = $wordsCount;

                if (!isset($this->stats[$domainName]['units'][$locale])) {
                    $this->stats[$domainName]['units'][$locale] = 0;
                }
                if (!isset($this->stats[$domainName]['units']['count'][$locale])) {
                    $this->stats[$domainName]['units']['count'][$locale] = 0;
                }

                if ($translation->getContent()) {
                    $this->stats[$domainName]['units'][$locale]++;
                }

                $this->stats[$domainName]['units']['count'][$locale]++;
            }
        }

        return $this->stats;
    }
}
