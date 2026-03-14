<?php

declare(strict_types=1);

namespace App\Model\Api;

use App\Entity\Api\Api;
use App\Entity\Api\Google;
use App\Model\BaseModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;

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
        public readonly ?string $youtubeApiKey = null,
        public readonly ?string $youtubeChannelId = null,
        public readonly ?int $youtubeNbrItems = null,
    ) {
    }

    /**
     * Get model.
     *
     * @throws MappingException
     */
    public static function fromEntity(Api $api, CoreLocatorInterface $coreLocator, ?string $locale = null): self
    {
        self::setLocator($coreLocator);

        $locale = $locale ?: self::$coreLocator->locale();

        if (isset(self::$cache['google'][$api->getId()][$locale])) {
            return self::$cache['google'][$api->getId()][$locale];
        }

        $google = self::cache($api, 'google', self::$cache);
        $isProd = self::$coreLocator->isProd();

        self::$cache['google'][$api->getId()][$locale] = new self(
            id: $google->getId(),
            entity: $google,
            clientId: $isProd ? self::getContentIntl('clientId', $locale, $google) : null,
            analyticsUa: $isProd ? self::getContentIntl('analyticsUa', $locale, $google) : null,
            analyticsAccountId: $isProd ? self::getContentIntl('analyticsAccountId', $locale, $google) : null,
            analyticsStatsDuration: $isProd ? self::getContentIntl('analyticsStatsDuration', $locale, $google) : null,
            tagManagerKey: $isProd ? self::getContentIntl('tagManagerKey', $locale, $google) : null,
            tagManagerLayer: $isProd ? self::getContentIntl('tagManagerLayer', $locale, $google) : null,
            searchConsoleKey: $isProd ? self::getContentIntl('searchConsoleKey', $locale, $google) : null,
            serverUrl: $isProd ? self::getContentIntl('serverUrl', $locale, $google) : null,
            mapKey: $isProd ? self::getContentIntl('mapKey', $locale, $google) : null,
            placeId:$isProd ? self::getContentIntl('placeId', $locale, $google) : null,
            youtubeApiKey: self::getContent('youtubeApiKey', $api),
            youtubeChannelId: self::getContent('youtubeChannelId', $api),
            youtubeNbrItems: self::getContent('youtubeNbrItems', $api),
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
            youtubeApiKey: $data->youtubeApiKey,
            youtubeChannelId: $data->youtubeChannelId,
            youtubeNbrItems: $data->youtubeNbrItems,
        );
    }
}
