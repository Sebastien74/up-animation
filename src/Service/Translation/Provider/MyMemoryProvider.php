<?php

declare(strict_types=1);

namespace App\Service\Translation\Provider;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * MyMemoryProvider.
 *
 * Free translation backend (one text per request). The daily quota exhaustion
 * is cached so the chain stops trying it for a few hours once hit.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class MyMemoryProvider implements TranslatorProviderInterface
{
    private const string ENDPOINT = 'https://api.mymemory.translated.net/get';
    private const string EXHAUSTED_CACHE_KEY = 'mymemory_exhausted';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $translationLogger,
        private readonly string $email,
    ) {
    }

    public function name(): string
    {
        return 'mymemory';
    }

    public function supportsHtml(): bool
    {
        return false;
    }

    public function isAvailable(int $charCount): bool
    {
        return !$this->cache->getItem(self::EXHAUSTED_CACHE_KEY)->isHit();
    }

    public function translate(array $texts, string $source, string $target, bool $html = false): array
    {
        $langPair = substr($source, 0, 2).'|'.substr($target, 0, 2);
        $translated = [];
        foreach (array_values($texts) as $text) {
            if ('' === trim((string) $text)) {
                $translated[] = $text;
                continue;
            }
            $query = ['q' => $text, 'langpair' => $langPair];
            if ('' !== $this->email) {
                $query['de'] = $this->email;
            }
            $data = $this->httpClient->request('GET', self::ENDPOINT, ['query' => $query])->toArray(false);
            $status = (int) ($data['responseStatus'] ?? 0);
            if (200 !== $status) {
                $details = \is_string($data['responseDetails'] ?? null) ? $data['responseDetails'] : '';
                if (429 === $status || 403 === $status || str_contains(strtoupper($details), 'QUOTA')) {
                    $item = $this->cache->getItem(self::EXHAUSTED_CACHE_KEY);
                    $item->set(true)->expiresAfter(6 * 3600);
                    $this->cache->save($item);
                }
                throw new \RuntimeException(sprintf('MyMemory error (%d): %s', $status, $details));
            }
            $translated[] = $data['responseData']['translatedText'] ?? $text;
        }

        return $translated;
    }
}
