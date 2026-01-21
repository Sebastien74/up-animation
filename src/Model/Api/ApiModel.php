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
        return new self(
            id: $data ? $data->id : null,
            facebook: $data && $data->facebook ? FacebookModel::modelCache($data->facebook) : null,
            google: $data && $data->google ? GoogleModel::modelCache($data->google) : null,
            instagram: $data && $data->instagram ? InstagramModel::modelCache($data->instagram) : null,
            custom: $data && $data->custom ? CustomModel::modelCache($data->custom) : null,
            addThis: $data ? $data->addThis : null,
            tawkToId: $data ? $data->tawkToId : null,
            securitySecretKey: $data ? $data->securitySecretKey : null,
            securitySecretIv: $data ? $data->securitySecretIv : null,
        );
    }
}
