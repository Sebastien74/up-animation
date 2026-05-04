<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Entity\Api\Api;
use App\Model\Core\WebsiteModel;

/**
 * CryptService.
 *
 * Manage string encryption.
 *
 * Encryption format for new payloads: base64( iv || ciphertext ), where iv is
 * a freshly generated 16-byte value. Legacy payloads encrypted with the old
 * static-IV format are still decrypted to keep already-issued tokens valid.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CryptService
{
    private const string CIPHER = 'AES-256-CBC';
    private const int IV_LENGTH = 16;

    /**
     * Encrypt or decrypt a string.
     *
     * @param string $action : e -> Encrypt, d -> decrypt
     */
    public function execute(WebsiteModel $website, string $string, string $action = 'e'): bool|string|null
    {
        $api = $website->entity->getApi();
        $secretKey = $api instanceof Api ? $api->getSecuritySecretKey() : null;
        $secretIv = $api instanceof Api ? $api->getSecuritySecretIv() : null;

        // Refuse to operate with empty secrets — callers (BaseAuthenticator,
        // RecaptchaService) generate them on demand, so this should never happen
        // in normal operation. Failing closed is much safer than silently using
        // a hardcoded fallback that would be the same for every install.
        if (!$secretKey || !$secretIv) {
            return false;
        }

        $key = hash('sha256', $secretKey, true);

        if ('e' === $action) {
            $iv = random_bytes(self::IV_LENGTH);
            $cipher = openssl_encrypt($string, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
            if (false === $cipher) {
                return false;
            }

            return base64_encode($iv.$cipher);
        }

        if ('d' === $action) {
            $raw = base64_decode($string, true);
            if (false === $raw) {
                return false;
            }

            // New format: random IV prefixed to the ciphertext.
            if (strlen($raw) > self::IV_LENGTH) {
                $iv = substr($raw, 0, self::IV_LENGTH);
                $cipher = substr($raw, self::IV_LENGTH);
                $plain = openssl_decrypt($cipher, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
                if (false !== $plain) {
                    return $plain;
                }
            }

            // Legacy format: static IV derived from the website key. Kept only
            // to keep existing tokens valid; new payloads use the format above.
            $legacyIv = substr(hash('sha256', $secretIv), 0, self::IV_LENGTH);
            $legacy = openssl_decrypt($string, self::CIPHER, hash('sha256', $secretKey), 0, $legacyIv);

            return false === $legacy ? false : $legacy;
        }

        return false;
    }
}
