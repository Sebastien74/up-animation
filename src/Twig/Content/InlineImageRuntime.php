<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * InlineImageRuntime.
 *
 * Encodes a public image as a base64 data URI for self-contained emails.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class InlineImageRuntime implements RuntimeExtensionInterface
{
    private const MIME_TYPES = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
    ];

    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * Return a base64 data URI for the given public image, or the path untouched if missing.
     */
    public function inlineImage(string $path): string
    {
        $relative = ltrim((string) preg_replace('#^@images/#', '', $path), '/\\');
        $absolute = $this->coreLocator->publicDir().DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);

        if (!is_file($absolute)) {
            return $path;
        }

        // Encode once and cache; the mtime in the key refreshes it when the file changes.
        $key = 'inline_image_'.md5($absolute).'_'.filemtime($absolute);

        return $this->cache->get($key, function (ItemInterface $item) use ($absolute): string {
            $extension = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
            $mime = self::MIME_TYPES[$extension] ?? 'application/octet-stream';

            return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($absolute));
        });
    }
}
