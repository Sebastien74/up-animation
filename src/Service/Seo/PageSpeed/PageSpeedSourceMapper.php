<?php

declare(strict_types=1);

namespace App\Service\Seo\PageSpeed;

/**
 * PageSpeedSourceMapper.
 *
 * Maps a resource URL flagged by a PageSpeed audit back to its origin in the project,
 * so a finding points at where it can be fixed: a Webpack entrypoint (with its built
 * file), an uploaded/processed media, the page document itself, or a third-party host.
 *
 * The Webpack lookup is built once from every public/build/<context>/entrypoints.json,
 * reversing "entry name => emitted asset URLs" into "asset path => entry name".
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class PageSpeedSourceMapper
{
    /**
     * Reverse index "built asset path" => "entry name", lazily loaded.
     *
     * @var array<string, string>|null
     */
    private ?array $assetEntries = null;

    public function __construct(private readonly string $projectDir)
    {
    }

    /**
     * Describe where a resource comes from.
     *
     * @return array{type: string, label: string}
     */
    public function describe(string $resourceUrl, ?string $ownHost = null): array
    {
        $host = parse_url($resourceUrl, PHP_URL_HOST);
        $path = (string) (parse_url($resourceUrl, PHP_URL_PATH) ?: '');

        $isExternal = is_string($host) && '' !== $host
            && null !== $ownHost && '' !== $ownHost
            && !$this->sameHost($host, $ownHost);

        if ($isExternal) {
            return ['type' => 'third-party', 'label' => (string) $host];
        }

        if (str_starts_with($path, '/build/')) {
            $entry = $this->entryForPath($path);

            return $entry
                ? ['type' => 'entrypoint', 'label' => $entry.' ('.$path.')']
                : ['type' => 'asset', 'label' => $path];
        }

        if (preg_match('#/(medias|media|uploads)/#', $path)) {
            return ['type' => 'media', 'label' => $path];
        }

        if ('' === $path || '/' === $path) {
            return ['type' => 'document', 'label' => $path ?: '/'];
        }

        return ['type' => 'other', 'label' => $path];
    }

    private function sameHost(string $a, string $b): bool
    {
        $normalize = static fn (string $h): string => strtolower(preg_replace('/^www\./i', '', $h) ?? $h);

        return $normalize($a) === $normalize($b);
    }

    private function entryForPath(string $path): ?string
    {
        if (null === $this->assetEntries) {
            $this->assetEntries = $this->loadEntrypoints();
        }

        return $this->assetEntries[$path] ?? null;
    }

    /**
     * @return array<string, string>
     */
    private function loadEntrypoints(): array
    {
        $map = [];
        $buildDir = $this->projectDir.'/public/build';
        if (!is_dir($buildDir)) {
            return $map;
        }

        foreach (glob($buildDir.'/*/entrypoints.json') ?: [] as $file) {
            $data = json_decode((string) @file_get_contents($file), true);
            if (!is_array($data) || !isset($data['entrypoints']) || !is_array($data['entrypoints'])) {
                continue;
            }

            foreach ($data['entrypoints'] as $name => $assets) {
                if (!is_array($assets)) {
                    continue;
                }
                foreach (['js', 'css'] as $kind) {
                    foreach ((array) ($assets[$kind] ?? []) as $assetUrl) {
                        $assetPath = (string) (parse_url((string) $assetUrl, PHP_URL_PATH) ?: $assetUrl);
                        if ('' !== $assetPath) {
                            $map[$assetPath] = (string) $name;
                        }
                    }
                }
            }
        }

        return $map;
    }
}
