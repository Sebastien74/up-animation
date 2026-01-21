<?php

declare(strict_types=1);

namespace App\Service\Core;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Request;

/**
 * AI Service.
 *
 * Handles API requests and performs cosine similarity calculations.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => AI::class, 'key' => 'ai_service'],
])]
class AI
{
    private string $host = "prompt.agence-felix.fr";
    private string $baseUrl = "https://prompt.agence-felix.fr/v1/run-tool";
    private string $bearerToken = "VQu8F8qXd3FNR70lMrQ3la0EaLC01VsrRMFcKjlwjHh3FXasRM";
    private int $defaultSite = 40;

    public function __construct()
    {
    }

    /**
     * Executes the API request.
     */
    public function runApi(Request $request, $options = []): array
    {
        // Merge request data with additional options
        $data = array_merge($_POST, $options);
        $data = array_merge($request->query->all(), $data);

        // Set default site if not provided
        if (empty($data['site'])) {
            $data['site'] = $this->defaultSite;
        }

        // Initialize cURL request
        $curl = curl_init($this->baseUrl);

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_USERAGENT => 'API FELIX',
            CURLOPT_HTTPHEADER => [
                'Host: ' . $this->host,
                "Content-Type: application/json",
                "Accept: */*",
                "Authorization: Bearer " . $this->bearerToken
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response = curl_exec($curl);

        // Handle cURL errors
        if (curl_errno($curl)) {
            return ['error' => 'cURL Error: ' . curl_error($curl)];
        }

        curl_close($curl);

        return json_decode($response, true);
    }

    /**
     * Computes the cosine similarity between two vectors.
     */
    public function cosineSimilarity(array $vecA, array $vecB): float
    {
        // Precompute magnitudes
        $magnitudeA = sqrt(array_sum(array_map(function ($x) {
            return $x * $x;
        }, $vecA)));

        $magnitudeB = sqrt(array_sum(array_map(function ($x) {
            return $x * $x;
        }, $vecB)));

        // Avoid division by zero
        if ($magnitudeA == 0 || $magnitudeB == 0) {
            return 0.0;
        }

        // Compute dot product
        $dotProduct = array_sum(array_map(function ($a, $b) {
            return $a * $b;
        }, $vecA, $vecB));

        return $dotProduct / ($magnitudeA * $magnitudeB);
    }
}