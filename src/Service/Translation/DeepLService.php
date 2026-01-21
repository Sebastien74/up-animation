<?php

declare(strict_types=1);

namespace App\Service\Translation;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * DeepLService.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class DeepLService implements DeepLInterface
{
    private const bool API_FREE = true;
    private string $apiKey = "f4f69e71-663a-453b-b4ac-5bee21e2b196:fx";
    private ?string $apiUrl = null;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    /**
     * DeepLService constructor.
     */
    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiUrl = self::API_FREE ? 'https://api-free.deepl.com' : 'https://api.deepl.com';
    }

    /**
     * Translate a list of strings into the specified target language.
     */
    public function translate(array $texts, string $targetLang = 'EN'): array
    {
        try {

            $response = $this->httpClient->request('POST', $this->apiUrl."/v2/translate", [
                'headers' => [
                    'Authorization' => 'DeepL-Auth-Key ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'text' => $texts,
                    'target_lang' => strtoupper($targetLang),
                ],
            ]);
            $data = $response->toArray();
            return array_column($data['translations'], 'text');
        } catch (TransportExceptionInterface|\Throwable $e) {
            $this->logger->error('DeepL API error: ' . $e->getMessage());
            return [];
        }
    }
}
