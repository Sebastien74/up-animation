<?php

declare(strict_types=1);

namespace App\Service\Translation\Provider;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * DeepLProvider.
 *
 * DeepL Free/Pro backend. Quota is read from /v2/usage so the chain can fall
 * back before exhausting the monthly free allowance.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class DeepLProvider implements TranslatorProviderInterface
{
    private const string FREE_BASE = 'https://api-free.deepl.com';
    private const string PRO_BASE = 'https://api.deepl.com';
    private const string USAGE_CACHE_KEY = 'deepl_usage';
    private const int BATCH_SIZE = 40;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $translationLogger,
        private readonly string $apiKey,
        private readonly bool $enabled = true,
    ) {
    }

    public function name(): string
    {
        return 'deepl';
    }

    public function supportsHtml(): bool
    {
        return true;
    }

    public function isAvailable(int $charCount): bool
    {
        if (!$this->enabled || '' === $this->apiKey) {
            return false;
        }
        $usage = $this->usage();
        if (null === $usage) {
            return false;
        }
        [$count, $limit] = $usage;

        return 0 === $limit || ($count + $charCount) <= $limit;
    }

    public function translate(array $texts, string $source, string $target, bool $html = false): array
    {
        $translated = [];
        foreach (array_chunk(array_values($texts), self::BATCH_SIZE) as $chunk) {
            $payload = [
                'text' => $chunk,
                'source_lang' => $this->mapSource($source),
                'target_lang' => $this->mapTarget($target),
            ];
            if ($html) {
                $payload['tag_handling'] = 'html';
            }
            $response = $this->httpClient->request('POST', $this->baseUrl().'/v2/translate', [
                'headers' => [
                    'Authorization' => 'DeepL-Auth-Key '.$this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);
            $data = $response->toArray();
            foreach ($data['translations'] ?? [] as $item) {
                $translated[] = $item['text'] ?? '';
            }
        }
        $this->cache->deleteItem(self::USAGE_CACHE_KEY);

        return $translated;
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    private function usage(): ?array
    {
        $item = $this->cache->getItem(self::USAGE_CACHE_KEY);
        if ($item->isHit()) {
            return $item->get();
        }
        try {
            $response = $this->httpClient->request('GET', $this->baseUrl().'/v2/usage', [
                'headers' => ['Authorization' => 'DeepL-Auth-Key '.$this->apiKey],
            ]);
            $data = $response->toArray();
            $usage = [(int) ($data['character_count'] ?? 0), (int) ($data['character_limit'] ?? 0)];
            $item->set($usage)->expiresAfter(120);
            $this->cache->save($item);

            return $usage;
        } catch (\Throwable $e) {
            $this->translationLogger->error('DeepL usage check failed: '.$e->getMessage());

            return null;
        }
    }

    private function baseUrl(): string
    {
        return str_ends_with($this->apiKey, ':fx') ? self::FREE_BASE : self::PRO_BASE;
    }

    private function mapTarget(string $locale): string
    {
        $lang = strtolower(substr($locale, 0, 2));

        return match ($lang) {
            'en' => 'EN-GB',
            'pt' => 'PT-PT',
            default => strtoupper($lang),
        };
    }

    private function mapSource(string $locale): string
    {
        return strtoupper(substr($locale, 0, 2));
    }
}
