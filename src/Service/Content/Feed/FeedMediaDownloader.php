<?php

declare(strict_types=1);

namespace App\Service\Content\Feed;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

/**
 * FeedMediaDownloader.
 *
 * Downloads remote media (image/video/thumbnail) to /public/feed/medias/
 * and returns a relative path usable in templates via asset().
 *
 * Idempotent: skip the HTTP call if a file already exists for the same
 * (provider, externalId, kind). Use --force on the sync command to bypass.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class FeedMediaDownloader
{
    public const string KIND_MEDIA = 'media';
    public const string KIND_THUMBNAIL = 'thumbnail';

    private const string BASE_RELATIVE = 'feed/medias';
    private const int HTTP_TIMEOUT = 30;

    public function __construct(
        private HttpClientInterface $httpClient,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    /**
     * Returns the relative path from /public (e.g. "feed/medias/instagram/123/media.jpg"),
     * or null on failure.
     */
    public function download(string $url, string $provider, string $externalId, string $kind, bool $force = false): ?string
    {
        $publicDir = $this->parameterBag->get('kernel.project_dir').'/public';
        $safeExternalId = $this->sanitize($externalId);
        $relativeDir = self::BASE_RELATIVE.'/'.$provider.'/'.$safeExternalId;
        $absoluteDir = $publicDir.'/'.$relativeDir;

        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0755, true) && !is_dir($absoluteDir)) {
            return null;
        }

        if (!$force) {
            $existing = glob($absoluteDir.'/'.$kind.'.*');
            if (!empty($existing)) {
                return $relativeDir.'/'.basename($existing[0]);
            }
        }

        try {
            $response = $this->httpClient->request('GET', $url, ['timeout' => self::HTTP_TIMEOUT]);
            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $contentType = $response->getHeaders(false)['content-type'][0] ?? '';
            $extension = $this->extensionFromContentType($contentType) ?? $this->extensionFromUrl($url);
            $filename = $kind.'.'.$extension;
            $absolutePath = $absoluteDir.'/'.$filename;

            file_put_contents($absolutePath, $response->getContent());

            return $relativeDir.'/'.$filename;
        } catch (Throwable) {
            return null;
        }
    }

    private function sanitize(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $value) ?? 'invalid';
    }

    private function extensionFromContentType(string $contentType): ?string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/webm' => 'webm',
        ];
        $mime = strtolower(trim(explode(';', $contentType)[0]));
        return $map[$mime] ?? null;
    }

    private function extensionFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return preg_match('/^[a-z0-9]{2,4}$/', $ext) ? $ext : 'bin';
    }
}
