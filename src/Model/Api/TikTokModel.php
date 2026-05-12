<?php

declare(strict_types=1);

namespace App\Model\Api;

use App\Entity\Api\Api;
use App\Entity\Api\TikTok;
use App\Model\BaseModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;

/**
 * TikTokModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class TikTokModel extends BaseModel
{
    private static array $cache = [];

    public function __construct(
        public readonly ?int $id = null,
        public readonly ?TikTok $entity = null,
        public readonly ?string $accessToken = null,
        public readonly ?string $appId = null,
        public readonly ?string $appSecret = null,
        public readonly ?int $nbrItems = null,
    ) {
    }

    /**
     * @throws MappingException
     */
    public static function fromEntity(Api $api, CoreLocatorInterface $coreLocator, ?string $locale = null): self
    {
        self::setLocator($coreLocator);

        $locale = $locale ?: self::$coreLocator->locale();

        if (isset(self::$cache['tiktok'][$api->getId()][$locale])) {
            return self::$cache['tiktok'][$api->getId()][$locale];
        }

        $tiktok = self::cache($api, 'tiktok', self::$cache);

        self::$cache['tiktok'][$api->getId()][$locale] = new self(
            id: $tiktok?->getId(),
            entity: $tiktok,
            accessToken: self::getContent('accessToken', $tiktok),
            appId: self::getContent('appId', $tiktok),
            appSecret: self::getContent('appSecret', $tiktok),
            nbrItems: self::getContent('nbrItems', $tiktok),
        );

        return self::$cache['tiktok'][$api->getId()][$locale];
    }

    public static function modelCache(object $data): TikTokModel
    {
        return new self(
            id: $data->id ?? null,
            accessToken: $data->accessToken ?? null,
            appId: $data->appId ?? null,
            appSecret: $data->appSecret ?? null,
            nbrItems: $data->nbrItems ?? null,
        );
    }
}
