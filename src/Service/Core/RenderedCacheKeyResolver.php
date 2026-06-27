<?php

declare(strict_types=1);

namespace App\Service\Core;

use App\Entity\Layout\Page;
use App\Model\Core\WebsiteModel;

/**
 * RenderedCacheKeyResolver.
 *
 * Single source of truth for the Doctrine result-cache keys that back a page render and a
 * module action render. Shared by CacheInvalidationSubscriber (edit-driven invalidation)
 * and EntityCacheInvalidator (per-entity button) so both delete the exact keys the front
 * sets via enableResultCache().
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class RenderedCacheKeyResolver
{
    /**
     * @return array<string>
     */
    public function pageKeys(Page $page, WebsiteModel $website): array
    {
        $keys = [];
        foreach ($page->getUrls() as $url) {
            $locale = $url->getLocale();
            $urlCode = $url->getCode();
            if ($page->isAsIndex()) {
                $keys[] = 'page-index-'.$website->id.'-'.$locale;
            } elseif ($urlCode) {
                $keys[] = 'page-'.$website->id.'-'.$urlCode.'-'.$locale;
            }
            $keys[] = 'page-url-'.md5($page->getId().'_'.$locale);
            $keys[] = 'page_url_id_'.$url->getId().'_'.$locale;
            $keys[] = 'pages_index_url_'.md5($page->getId().'_'.$locale);
            foreach (['page-bi-', 'page-bai-', 'page-pi-', 'page-pmr-'] as $prefix) {
                $keys[] = $prefix.$page->getId().'-'.$locale;
            }
            foreach ([0, 1] as $previewFlag) {
                if ($page->isAsIndex()) {
                    $keys[] = 'page-stamp-'.$website->id.'-index-'.$locale.'-'.$previewFlag;
                }
                if ($urlCode) {
                    $keys[] = 'page-stamp-'.$website->id.'-'.$urlCode.'-'.$locale.'-'.$previewFlag;
                }
            }
        }
        if ($page->getLayout()) {
            $keys[] = 'layout_'.$page->getLayout()->getId();
        }

        return $keys;
    }

    /**
     * @return array<string>
     */
    public function actionKeys(string $namespace, mixed $entityId, WebsiteModel $website): array
    {
        $keys = [];
        $idsStr = is_array($entityId) ? implode('_', $entityId) : (string) $entityId;
        $wId = $website->id;
        $locales = $website->configuration->allLocales ?? [];

        foreach ($locales as $locale) {
            $keys[] = 'pages_action_'.md5($namespace.'_'.$idsStr.'_'.$locale.'_'.$wId);
            $keys[] = 'page_action_'.md5($wId.'_'.$locale.'_'.$namespace.'_'.$entityId);
            $keys[] = 'pages_action_ids_'.md5($wId.'_'.$locale.'_'.$namespace.'_'.$idsStr);
            $keys[] = 'page_action_slug_'.md5($wId.'_'.$locale.'_'.$namespace.'_'.$entityId);
            $keys[] = 'pages_action_slug_'.md5($wId.'_'.$locale.'_'.$namespace.'_'.$entityId);
        }

        if ($locales) {
            $sortedLocales = $locales;
            sort($sortedLocales);
            $keys[] = 'pages_action_locales_'.md5($namespace.'_'.$idsStr.'_'.implode(',', $sortedLocales).'_'.$wId);
        }

        return $keys;
    }
}
