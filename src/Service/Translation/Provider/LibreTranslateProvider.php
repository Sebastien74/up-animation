<?php

declare(strict_types=1);

namespace App\Service\Translation\Provider;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * LibreTranslateProvider.
 *
 * Self-hosted open-source backend. Disabled unless LIBRETRANSLATE_URL is set.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class LibreTranslateProvider implements TranslatorProviderInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $url,
        private readonly string $apiKey,
    ) {
    }

    public function name(): string
    {
        return 'libretranslate';
    }

    public function supportsHtml(): bool
    {
        return true;
    }

    public function isAvailable(int $charCount): bool
    {
        return '' !== $this->url;
    }

    public function translate(array $texts, string $source, string $target, bool $html = false): array
    {
        $payload = [
            'q' => array_values($texts),
            'source' => substr($source, 0, 2),
            'target' => substr($target, 0, 2),
            'format' => $html ? 'html' : 'text',
        ];
        if ('' !== $this->apiKey) {
            $payload['api_key'] = $this->apiKey;
        }
        $data = $this->httpClient->request('POST', rtrim($this->url, '/').'/translate', [
            'json' => $payload,
        ])->toArray();
        $translated = $data['translatedText'] ?? [];

        return \is_array($translated) ? $translated : [$translated];
    }
}
