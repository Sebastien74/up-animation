<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Entity\Api\Api;
use App\Model\Core\WebsiteModel;

/**
 * CryptService.
 *
 * Manage string encryption
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CryptService
{
    private string $secretKey = 'fc58fd900e20f8f9bfc5af9ac8a5c247';
    private string $secretIv = '2y10nlpXG3AbjE4Rt72AkKZRVu3IdRJZ395JXjlM05Wd4StMG7efwqi';

    /**
     * Encrypt or decrypt a string.
     *
     * @param string $action : e -> Encrypt, d -> decrypt
     */
    public function execute(WebsiteModel $website, string $string, string $action = 'e'): bool|string|null
    {
        $api = $website->entity->getApi();
        $secretKey = $api instanceof Api && $api->getSecuritySecretKey() ? $api->getSecuritySecretKey() : $this->secretKey;
        $secretIv = $api instanceof Api && $api->getSecuritySecretIv() ? $api->getSecuritySecretIv() : $this->secretIv;

        $output = false;
        $encryptMethod = 'AES-256-CBC';
        $key = hash('sha256', $secretKey);
        $iv = substr(hash('sha256', $secretIv), 0, 16);

        if ('e' == $action) {
            $output = base64_encode(openssl_encrypt($string, $encryptMethod, $key, 0, $iv));
        } elseif ('d' == $action) {
            $output = openssl_decrypt(base64_decode($string), $encryptMethod, $key, 0, $iv);
        }

        return $output;
    }
}
