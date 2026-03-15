<?php

declare(strict_types=1);

namespace App\Model\Api;

use App\Entity\Api\Api;
use App\Entity\Api\Instagram;
use App\Model\BaseModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;

/**
 * InstagramModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class InstagramModel extends BaseModel
{
    private static array $cache = [];

    /**
     * InstagramModel constructor.
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?Instagram $entity = null,
        public readonly ?string $accessToken = null,
        public readonly ?string $appId = null,
        public readonly ?string $appSecret = null,
        public readonly ?int $nbrItems = null,
        public readonly ?string $widget = null,
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

        if (isset(self::$cache['instagram'][$api->getId()][$locale])) {
            return self::$cache['instagram'][$api->getId()][$locale];
        }

        $instagram = self::cache($api, 'instagram', self::$cache);

        self::$cache['instagram'][$api->getId()][$locale] = new self(
            id: $instagram->getId(),
            entity: $instagram,
            accessToken: self::getContent('accessToken', $api),
            appId: self::getContent('appId', $api),
            appSecret: self::getContent('appSecret', $api),
            nbrItems: self::getContent('nbrItems', $api),
            widget: self::getContent('widget', $api),
        );

        return self::$cache['instagram'][$api->getId()][$locale];
    }

    /**
     * Get model by cache.
     */
    public static function modelCache(object $data): InstagramModel
    {
        return new self(
//            id: $data->id,
//            accessToken: $data->accessToken,
//            appId: $data->appId ?? null,
//            appSecret: $data->appSecret ?? null,
//            nbrItems: $data->nbrItems,
//            widget: $data->widget,
            id: $data->id,
            accessToken: $data->accessToken,
            appId: '1227922292865765',
            appSecret: '7e4fd55b09b2b2bb623b3ee1c96a7c77',
            nbrItems: 7,
            widget: $data->widget,
        );
    }
}
