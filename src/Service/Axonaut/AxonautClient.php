<?php

declare(strict_types=1);

namespace App\Service\Axonaut;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * AxonautClient.
 *
 * Pushes form contacts to the Axonaut CRM as a prospect company, an employee
 * (contact) and an opportunity. Every call is defensive: a remote failure is
 * logged and returns null so the caller (an async message handler) never breaks
 * the originating form submission.
 *
 * NOTE: Axonaut field names below mirror the v2 API response schema. The
 * opportunity pipe/step are account-specific and are read from configuration;
 * adjust them in services.yaml / .env if your pipeline differs. Requests and
 * responses are logged to ease the first calibration on a real account.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class AxonautClient implements AxonautClientInterface
{
    private const string BASE_URL = 'https://axonaut.com/api/v2';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $apiKey,
        private readonly bool $enabled = true,
        private readonly string $opportunityPipe = '',
        private readonly string $opportunityStep = '',
        private readonly float $opportunityAmount = 0.0,
    ) {
    }

    public function isAvailable(): bool
    {
        return $this->enabled && '' !== $this->apiKey;
    }

    public function createCompany(string $name, string $comments = ''): ?int
    {
        $payload = [
            'name' => $name,
            'is_prospect' => true,
            'is_customer' => false,
        ];
        if ('' !== $comments) {
            $payload['comments'] = $comments;
        }

        return $this->postId('/companies', $payload);
    }

    public function createEmployee(int $companyId, ?string $firstname, ?string $lastname, ?string $email, ?string $phone): ?int
    {
        $payload = array_filter([
            'company_id' => $companyId,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'phone_number' => $phone,
        ], static fn ($value) => null !== $value && '' !== $value);

        return $this->postId('/employees', $payload);
    }

    public function createOpportunity(int $companyId, string $name, string $comments = ''): ?int
    {
        $payload = [
            'name' => $name,
            'company_id' => $companyId,
        ];
        if ('' !== $comments) {
            $payload['comments'] = $comments;
        }
        if ('' !== $this->opportunityPipe) {
            $payload['pipe'] = $this->opportunityPipe;
        }
        if ('' !== $this->opportunityStep) {
            $payload['step'] = $this->opportunityStep;
        }
        if ($this->opportunityAmount > 0) {
            $payload['amount'] = $this->opportunityAmount;
        }

        return $this->postId('/opportunities', $payload);
    }

    /**
     * POST a payload and extract the created resource id.
     */
    private function postId(string $path, array $payload): ?int
    {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            $response = $this->httpClient->request('POST', self::BASE_URL.$path, [
                'headers' => [
                    'userApiKey' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);
            $status = $response->getStatusCode();
            $data = $response->toArray(false);
            if ($status >= 200 && $status < 300 && !empty($data['id'])) {
                return (int) $data['id'];
            }
            $this->logger->error('Axonaut POST '.$path.' failed', [
                'status' => $status,
                'payload' => $payload,
                'response' => $data,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Axonaut POST '.$path.' exception: '.$e->getMessage(), [
                'payload' => $payload,
            ]);
        }

        return null;
    }
}
