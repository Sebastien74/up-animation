<?php

declare(strict_types=1);

namespace App\Service\Security;

use App\Entity\Api\Api;
use App\Entity\Core\Website;
use Doctrine\ORM\EntityManagerInterface;

/**
 * WebsiteSecretProvider.
 *
 * Single source for the per-website secret used as the captcha HMAC key.
 * Generates and persists the secret on demand so issuing and verifying always
 * share the same value. Replaces the security-key generation that used to be
 * duplicated across RecaptchaService and the authenticators.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class WebsiteSecretProvider
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function hmacKey(Website $website): string
    {
        $api = $website->getApi();
        if (!$api instanceof Api) {
            throw new \RuntimeException('Website has no API configuration to derive a captcha secret.');
        }

        $flush = false;
        if (!$api->getSecuritySecretKey()) {
            $api->setSecuritySecretKey($this->generate());
            $flush = true;
        }
        if (!$api->getSecuritySecretIv()) {
            $api->setSecuritySecretIv($this->generate());
            $flush = true;
        }

        if ($flush) {
            $this->entityManager->persist($api);
            $this->entityManager->flush();
        }

        return $api->getSecuritySecretKey();
    }

    private function generate(): string
    {
        return substr(str_shuffle(base64_encode(random_bytes(48))), 0, 45);
    }
}
