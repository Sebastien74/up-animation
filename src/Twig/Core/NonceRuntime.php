<?php

declare(strict_types=1);

namespace App\Twig\Core;

use App\Service\Core\CspNonceGenerator;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * NonceRuntime.
 *
 * Generates a random nonce parameter for XSS attacks.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class NonceRuntime implements RuntimeExtensionInterface
{
    /**
     * NonceRuntime constructor.
     */
    public function __construct(private readonly CspNonceGenerator $nonceGenerator)
    {
    }

    /**
     * Generates a random nonce parameter for XSS attacks.
     *
     * @throws \Exception
     */
    public function getNonce(): string
    {
        return $this->nonceGenerator->getNonce();
    }
}
