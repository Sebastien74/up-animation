<?php

declare(strict_types=1);

namespace App\Model\Api;

use App\Entity\Api\Api;
use App\Entity\Api\Instagram;
use App\Model\BaseModel;
use App\Service\Interface\CoreLocatorInterface;

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
        public readonly ?int $nbrItems = null,
        public readonly ?string $widget = null,
    ) {
    }

    /**
     * Get model.
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
            id: $api->getId(),
            entity: $instagram,
            accessToken: self::getContent('accessToken', $api),
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
            id: $data->id,
            accessToken: $data->accessToken,
            nbrItems: $data->nbrItems,
            widget: $data->widget,
        );
    }
}
