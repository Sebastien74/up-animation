<?php

declare(strict_types=1);

namespace App\Model\Api;

use App\Entity\Api\Api;
use App\Model\BaseModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;

/**
 * ApiModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class ApiModel extends BaseModel
{
    private static array $cache = [];

    /**
     * ApiModel constructor.
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?Api $entity = null,
        public readonly ?FacebookModel $facebook = null,
        public readonly ?GoogleModel $google = null,
        public readonly ?InstagramModel $instagram = null,
        public readonly ?TikTokModel $tiktok = null,
        public readonly ?CustomModel $custom = null,
        public readonly ?string $addThis = null,
        public readonly ?string $tawkToId = null,
        public readonly ?string $securitySecretKey = null,
        public readonly ?string $securitySecretIv = null,
    ) {
    }

    /**
     * Get model.
     *
     * @throws NonUniqueResultException|MappingException
     */
    public static function fromEntity(\App\Entity\Core\Website $website, CoreLocatorInterface $coreLocator, ?string $locale = null): self
    {
        self::setLocator($coreLocator);

        $locale = $locale ?: self::$coreLocator->locale();
        if (isset(self::$cache['api'][$website->getId()][$locale])) {
            return self::$cache['api'][$website->getId()][$locale];
        }

        $api = self::cache($website, 'api', self::$cache);
        $api = $api ? self::$coreLocator->em()->getRepository(Api::class)->findObjectByLocale($api->getId(), $locale) : false;

        self::$cache['api'][$website->getId()][$locale] = new self(
            id: self::getContent('id', $api),
            entity: $api,
            facebook: $api ? FacebookModel::fromEntity($api, $coreLocator, $locale) : null,
            google: $api ? GoogleModel::fromEntity($api, $coreLocator, $locale) : null,
            instagram: $api ? InstagramModel::fromEntity($api, $coreLocator, $locale) : null,
            tiktok: $api ? TikTokModel::fromEntity($api, $coreLocator, $locale) : null,
            custom: $api ? CustomModel::fromEntity($api, $coreLocator, $locale) : null,
            addThis: self::getContent('addThis', $api),
            tawkToId: self::getContent('tawkToId', $api),
            securitySecretKey: self::getContent('securitySecretKey', $api),
            securitySecretIv: self::getContent('securitySecretIv', $api),
        );

        return self::$cache['api'][$website->getId()][$locale];
    }

    /**
     * Get model by cache.
     */
    protected static function modelCache(mixed $data): ApiModel
    {
        $facebook = $data->facebook ?? null;
        $google = $data->google ?? null;
        $instagram = $data->instagram ?? null;
        $tiktok = $data->tiktok ?? null;
        $custom = $data->custom ?? null;

        return new self(
            id: $data?->id,
            facebook: $facebook ? FacebookModel::modelCache($facebook) : null,
            google: $google ? GoogleModel::modelCache($google) : null,
            instagram: $instagram ? InstagramModel::modelCache($instagram) : null,
            tiktok: $tiktok ? TikTokModel::modelCache($tiktok) : null,
            custom: $custom ? CustomModel::modelCache($custom) : null,
            addThis: $data->addThis ?? null,
            tawkToId: $data->tawkToId ?? null,
            securitySecretKey: $data->securitySecretKey ?? null,
            securitySecretIv: $data->securitySecretIv ?? null,
        );
    }
}
