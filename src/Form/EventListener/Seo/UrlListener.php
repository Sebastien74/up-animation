<?php

declare(strict_types=1);

namespace App\Form\EventListener\Seo;

use App\Entity\Seo\Url;
use App\Form\EventListener\BaseListener;
use Symfony\Component\Form\FormEvent;

/**
 * UrlListener.
 *
 * Listen Url Form attribute
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UrlListener extends BaseListener
{
    /**
     * preSetData.
     */
    public function preSetData(FormEvent $event): void
    {
        $entity = $event->getData();
        if (!$entity) {
            return;
        }

        $this->removeDuplicateLocales($entity);

        foreach ($this->locales as $locale) {
            $exist = false;
            foreach ($entity->getUrls() as $url) {
                if ($url->getLocale() === $locale) {
                    $exist = true;
                    break;
                }
            }
            if (!$exist && empty($entity->getId()) && $locale === $this->defaultLocale
                || !$exist && $entity->getId()) {
                $url = new Url();
                $url->setLocale($locale);
                $entity->addUrl($url);
            }
        }

        $this->sortUrls($entity);
    }

    /**
     * Remove duplicate locales in an urls collection.
     */
    private function removeDuplicateLocales(mixed $entity): void
    {
        $urls = $entity->getUrls();
        $locales = [];
        foreach ($urls as $url) {
            if (in_array($url->getLocale(), $locales)) {
                $entity->removeUrl($url);
            } else {
                $locales[] = $url->getLocale();
            }
        }
    }

    /**
     * Sort urls by configuration order.
     */
    private function sortUrls(mixed $entity): void
    {
        $urls = $entity->getUrls();
        if ($urls->count() > 1) {
            $sortedUrls = [];
            $orderedLocales = $this->locales;
            usort($orderedLocales, function ($a, $b) {
                if ($a === $this->defaultLocale) {
                    return -1;
                }
                if ($b === $this->defaultLocale) {
                    return 1;
                }
                return strcmp($a, $b);
            });
            foreach ($orderedLocales as $locale) {
                foreach ($urls as $url) {
                    if ($url->getLocale() === $locale) {
                        $sortedUrls[] = $url;
                        break;
                    }
                }
            }
            $urls->clear();
            foreach ($sortedUrls as $url) {
                $entity->addUrl($url);
            }
        }
    }
}
