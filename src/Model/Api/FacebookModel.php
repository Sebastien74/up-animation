<?php

declare(strict_types=1);

namespace App\Model\Api;

use App\Entity\Api\Api;
use App\Entity\Api\Facebook;
use App\Model\BaseModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;

/**
 * FacebookModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class FacebookModel extends BaseModel
{
    private static array $cache = [];

    /**
     * WebsiteModel constructor.
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?Facebook $entity = null,
        public readonly ?string $apiVersion = null,
        public readonly ?string $pageId = null,
        public readonly ?string $appId = null,
        public readonly ?string $apiSecretKey = null,
        public readonly ?string $apiPublicKey = null,
        public readonly ?string $apiGraphVersion = null,
        public readonly ?string $domainVerification = null,
        public readonly ?string $pixel = null,
        public readonly ?bool $phoneTrack = null,
    ) {
    }

    /**
     * Get model.
     *
     * @throws NonUniqueResultException|MappingException
     */
    public static function fromEntity(Api $api, CoreLocatorInterface $coreLocator, ?string $locale = null): self
    {
        self::setLocator($coreLocator);

        $locale = $locale ?: self::$coreLocator->locale();

        if (isset(self::$cache['facebook'][$api->getId()][$locale])) {
            return self::$cache['facebook'][$api->getId()][$locale];
        }

        $facebook = self::cache($api, 'facebook', self::$cache);

        self::$cache['facebook'][$api->getId()][$locale] = new self(
            id: $api->getId(),
            entity: $facebook,
            apiVersion: self::getContent('apiVersion', $api),
            pageId: self::getContent('pageId', $api),
            appId: self::getContent('appId', $api),
            apiSecretKey: self::getContent('apiSecretKey', $api),
            apiPublicKey: self::getContent('apiPublicKey', $api),
            apiGraphVersion: self::getContent('apiGraphVersion', $api),
            domainVerification: self::getContentIntl('domainVerification', $locale, $facebook),
            pixel: self::getContentIntl('pixel', $locale, $facebook),
            phoneTrack: self::getContentIntl('phoneTrack', $locale, $facebook),
        );

        return self::$cache['facebook'][$api->getId()][$locale];
    }

    /**
     * Get model by cache.
     */
    public static function modelCache(object $data): FacebookModel
    {
        return new self(
            id: $data->id,
            apiVersion: $data->apiVersion,
            pageId: $data->pageId,
            appId: $data->appId,
            apiSecretKey: $data->apiSecretKey,
            apiPublicKey: $data->apiPublicKey,
            apiGraphVersion: $data->apiGraphVersion,
            domainVerification: $data->domainVerification,
            pixel: $data->pixel,
            phoneTrack: $data->phoneTrack,
        );
    }
}
