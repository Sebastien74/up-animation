<?php

declare(strict_types=1);

namespace App\Service\Seo\PageSpeed;

use App\Entity\Core\Domain;
use App\Entity\Core\Website;
use App\Entity\Seo\Url;
use App\Service\Content\SeoService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * PublicPageUrlResolver.
 *
 * Resolves the absolute public URL of a front page so an external probe (PageSpeed
 * Insights) hits the real live page. Pages map to "/{code}"; newscasts and products
 * live behind a module path (e.g. /{pageUrl}/fiche-actualite/{code}), so for those the
 * canonical URL is built with the same logic SEO uses (SeoService::getAsCardUrl) and we
 * only fall back to the bare domain + code when no canonical route can be generated.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class PublicPageUrlResolver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SeoService $seoService,
        private readonly string $appProtocol,
    ) {
    }

    /**
     * @param object|null $entity the entity owning the url, required for card interfaces
     */
    public function resolve(Website $website, Url $url, string $interface = 'page', ?object $entity = null, ?string $classname = null): ?string
    {
        // Cards (products, newscasts...) are only reachable through their module path; their
        // canonical URL comes from SeoService (same source the sitemap uses). We never fall back
        // to "domain/{code}" for them, which is a non-existent URL Google would fail to analyse.
        if ('page' !== $interface) {
            if (null === $entity || null === $classname) {
                return null;
            }
            try {
                $card = $this->seoService->getAsCardUrl($url, $entity, $classname);
            } catch (\Throwable) {
                $card = null;
            }

            return is_string($card) && '' !== $card ? $card : null;
        }

        // The home page (asIndex) is served at the domain root: drop its code.
        $code = (null !== $entity && method_exists($entity, 'isAsIndex') && $entity->isAsIndex())
            ? null
            : $url->getCode();

        return $this->pageUrl($website, $url->getLocale(), $code);
    }

    private function pageUrl(Website $website, ?string $locale, ?string $code): ?string
    {
        $base = $this->baseUrl($website, $locale);
        if (null === $base) {
            return null;
        }

        $path = trim((string) $code, '/');

        return rtrim($base, '/').('' === $path ? '/' : '/'.$path);
    }

    private function baseUrl(Website $website, ?string $locale): ?string
    {
        $domains = $this->entityManager->getRepository(Domain::class)
            ->findBy(['configuration' => $website->getConfiguration()]);

        $default = null;
        $localized = null;
        foreach ($domains as $domain) {
            $name = $domain->getName();
            if (!$name) {
                continue;
            }
            $url = $this->appProtocol.'://'.$name;
            if (null !== $locale && $domain->getLocale() === $locale) {
                $localized = $url;
            }
            if ($domain->isAsDefault() || null === $default) {
                $default = $url;
            }
        }

        return $localized ?? $default;
    }
}
