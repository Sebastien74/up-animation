<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Service\Interface\CoreLocatorInterface;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * FavoritesRuntime.
 *
 * Cookie-backed favorites helpers for SSR rendering.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FavoritesRuntime implements RuntimeExtensionInterface
{
    public const COOKIE_NAME = 'up_favorites';
    public const MAX_FAVORITES = 80;

    private ?array $idsCache = null;

    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * Get sanitized favorite product IDs from cookie.
     *
     * @return array<int>
     */
    public function favoritesIds(): array
    {
        if (null !== $this->idsCache) {
            return $this->idsCache;
        }

        $request = $this->coreLocator->request();
        $raw = $request ? $request->cookies->get(self::COOKIE_NAME) : null;
        if (!is_string($raw) || '' === $raw) {
            return $this->idsCache = [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $this->idsCache = [];
        }

        $ids = [];
        foreach ($decoded as $value) {
            $id = (int) $value;
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
            if (count($ids) >= self::MAX_FAVORITES) {
                break;
            }
        }

        return $this->idsCache = $ids;
    }

    public function isFavorite(int $id): bool
    {
        return in_array($id, $this->favoritesIds(), true);
    }

    public function favoritesCount(): int
    {
        return count($this->favoritesIds());
    }
}
