<?php

declare(strict_types=1);

namespace App\Model\Api;

use App\Entity\Api\Api;
use App\Entity\Api\Google;
use App\Model\BaseModel;
use App\Service\Interface\CoreLocatorInterface;

/**
 * GoogleModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class GoogleModel extends BaseModel
{
    private static array $cache = [];

    /**
     * GoogleModel constructor.
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?Google $entity = null,
        public readonly ?string $clientId = null,
        public readonly ?string $analyticsUa = null,
        public readonly ?string $analyticsAccountId = null,
        public readonly ?string $analyticsStatsDuration = null,
        public readonly ?string $tagManagerKey = null,
        public readonly ?string $tagManagerLayer = null,
        public readonly ?string $searchConsoleKey = null,
        public readonly ?string $serverUrl = null,
        public readonly ?string $mapKey = null,
        public readonly ?string $placeId = null,
    ) {
    }

    /**
     * Get model.
     */
    public static function fromEntity(Api $api, CoreLocatorInterface $coreLocator, ?string $locale = null): self
    {
        self::setLocator($coreLocator);

        $locale = $locale ?: self::$coreLocator->locale();

        if (isset(self::$cache['google'][$api->getId()][$locale])) {
            return self::$cache['google'][$api->getId()][$locale];
        }

        $google = self::cache($api, 'google', self::$cache);

        self::$cache['google'][$api->getId()][$locale] = new self(
            id: $api->getId(),
            entity: $google,
            clientId: self::getContentIntl('clientId', $locale, $google),
            analyticsUa: self::getContentIntl('analyticsUa', $locale, $google),
            analyticsAccountId: self::getContentIntl('analyticsAccountId', $locale, $google),
            analyticsStatsDuration: self::getContentIntl('analyticsStatsDuration', $locale, $google),
            tagManagerKey: self::getContentIntl('tagManagerKey', $locale, $google),
            tagManagerLayer: self::getContentIntl('tagManagerLayer', $locale, $google),
            searchConsoleKey: self::getContentIntl('searchConsoleKey', $locale, $google),
            serverUrl: self::getContentIntl('serverUrl', $locale, $google),
            mapKey: self::getContentIntl('mapKey', $locale, $google),
            placeId: self::getContentIntl('placeId', $locale, $google),
        );

        return self::$cache['google'][$api->getId()][$locale];
    }

    /**
     * Get model by cache.
     */
    public static function modelCache(object $data): GoogleModel
    {
        return new self(
            id: $data->id,
            clientId: $data->clientId,
            analyticsUa: $data->analyticsUa,
            analyticsAccountId: $data->analyticsAccountId,
            analyticsStatsDuration: $data->analyticsStatsDuration,
            tagManagerKey: $data->tagManagerKey,
            tagManagerLayer: $data->tagManagerLayer,
            searchConsoleKey: $data->searchConsoleKey,
            serverUrl: $data->serverUrl,
            mapKey: $data->mapKey,
            placeId: $data->placeId,
        );
    }
}
