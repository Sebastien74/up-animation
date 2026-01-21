<?php

declare(strict_types=1);

namespace App\Model\Api;

use App\Entity\Api\Api;
use App\Entity\Api\Custom;
use App\Model\BaseModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;

/**
 * CustomModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class CustomModel extends BaseModel
{
    private static array $cache = [];

    /**
     * CustomModel constructor.
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?Custom $entity = null,
        public readonly ?string $matomoId = null,
        public readonly ?string $matomoUrl = null,
        public readonly ?bool $axeptioExternal = null,
        public readonly ?string $axeptioId = null,
        public readonly ?string $axeptioCookieVersion = null,
        public readonly ?string $headScript = null,
        public readonly ?string $topBodyScript = null,
        public readonly ?string $bottomBodyScript = null,
        public readonly ?string $headScriptSeo = null,
        public readonly ?string $topBodyScriptSeo = null,
        public readonly ?string $bottomBodyScriptSeo = null,
        public readonly ?string $aiFelixSiteId = null,
    ) {
    }

    /**
     * fromEntity.
     *
     * @throws NonUniqueResultException|MappingException
     */
    public static function fromEntity(Api $api, CoreLocatorInterface $coreLocator, ?string $locale = null): self
    {
        self::setLocator($coreLocator);

        $locale = $locale ?: self::$coreLocator->locale();

        if (isset(self::$cache['custom'][$api->getId()][$locale])) {
            return self::$cache['custom'][$api->getId()][$locale];
        }

        $custom = self::cache($api, 'custom', self::$cache);

        self::$cache['custom'][$api->getId()][$locale] = new self(
            id: $api->getId(),
            entity: $custom,
            matomoId: self::getContentIntl('matomoId', $locale, $custom),
            matomoUrl: self::getContentIntl('matomoUrl', $locale, $custom),
            axeptioExternal: self::getContentIntl('axeptioExternal', $locale, $custom),
            axeptioId: self::getContentIntl('axeptioId', $locale, $custom),
            axeptioCookieVersion: self::getContentIntl('axeptioCookieVersion', $locale, $custom),
            headScript: self::getContentIntl('headScript', $locale, $custom),
            topBodyScript: self::getContentIntl('topBodyScript', $locale, $custom),
            bottomBodyScript: self::getContentIntl('bottomBodyScript', $locale, $custom),
            headScriptSeo: self::getContentIntl('headScriptSeo', $locale, $custom),
            topBodyScriptSeo: self::getContentIntl('topBodyScriptSeo', $locale, $custom),
            bottomBodyScriptSeo: self::getContentIntl('bottomBodyScriptSeo', $locale, $custom),
            aiFelixSiteId: self::getContentIntl('aiFelixSiteId', $locale, $custom),
        );

        return self::$cache['custom'][$api->getId()][$locale];
    }

    /**
     * Get model by cache.
     */
    public static function modelCache(object $data): CustomModel
    {
        return new self(
            id: $data->id,
            matomoId: $data->matomoId,
            matomoUrl: $data->matomoUrl,
            axeptioExternal: $data->axeptioExternal,
            axeptioId: $data->axeptioId,
            axeptioCookieVersion: $data->axeptioCookieVersion,
            headScript: $data->headScript,
            topBodyScript: $data->topBodyScript,
            bottomBodyScript: $data->bottomBodyScript,
            headScriptSeo: $data->headScriptSeo,
            topBodyScriptSeo: $data->topBodyScriptSeo,
            bottomBodyScriptSeo: $data->bottomBodyScriptSeo,
        );
    }
}
