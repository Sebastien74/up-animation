<?php

declare(strict_types=1);

namespace App\Service\Core;

use App\Service\Interface\CoreLocatorInterface;
use Random\RandomException;

/**
 * CspNonceGenerator.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CspNonceGenerator
{
    private const string ATTRIBUTE_KEY = '_csp_nonce';
    private array $cache = [];

    /**
     * CspNonceGenerator constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator) {}

    /**
     * @throws RandomException
     */
    public function getNonce(): string
    {
        $request = $this->coreLocator->request();

        if (!$request) {
            return '';
        }

        $cacheKey = $request->getSchemeAndHttpHost().$request->getUri();
        if (!empty($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        } elseif (!$request->attributes->has(self::ATTRIBUTE_KEY)) {
            $nonce = 'nonce-'.hash('crc32', random_bytes(4)).'/'.base64_encode(random_bytes(16));
            $this->cache[$cacheKey] = $nonce;
            $request->attributes->set(self::ATTRIBUTE_KEY, $nonce);
        }

        return $request->attributes->get(self::ATTRIBUTE_KEY);
    }
}
