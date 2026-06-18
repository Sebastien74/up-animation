<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Core\Website;
use App\Entity\Seo\Url;

/**
 * PageAnalysisTrait.
 *
 * Shared helpers to build the front preview URL of a page (per interface), used by the
 * PageSpeed analysis dashboard.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
trait PageAnalysisTrait
{
    /**
     * Front preview route names per interface (for "open preview" links).
     */
    private const PREVIEW_ROUTES = [
        'page' => 'front_page_preview',
        'newscast' => 'front_newscast_preview',
        'catalogproduct' => 'front_catalogproduct_preview',
    ];

    /**
     * Generate the public preview URL for an interface/url (admin only).
     */
    private function previewUrlFor(string $interface, Website $website, Url $url): string
    {
        return $this->previewUrlForId($interface, $website, (int) $url->getId());
    }

    /**
     * Generate the public preview URL from a Url id (avoids hydrating the entity).
     */
    private function previewUrlForId(string $interface, Website $website, int $urlId): string
    {
        $route = self::PREVIEW_ROUTES[$interface] ?? self::PREVIEW_ROUTES['page'];
        $params = 'page' === $interface
            ? ['website' => $website->getId(), 'url' => $urlId]
            : ['url' => $urlId];

        return $this->coreLocator->router()->generate($route, $params);
    }
}
