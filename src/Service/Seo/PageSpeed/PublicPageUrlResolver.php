<?php

declare(strict_types=1);

namespace App\Service\Seo\PageSpeed;

use App\Entity\Core\Domain;
use App\Entity\Core\Website;
use Doctrine\ORM\EntityManagerInterface;

/**
 * PublicPageUrlResolver.
 *
 * Resolves the absolute public URL of a front page (by website, locale and url code),
 * picking the domain that matches the locale and falling back to the default domain.
 * Used by external probes that must hit the live page (PageSpeed Insights).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class PublicPageUrlResolver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $appProtocol,
    ) {
    }

    public function resolve(Website $website, ?string $locale, ?string $code): ?string
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
