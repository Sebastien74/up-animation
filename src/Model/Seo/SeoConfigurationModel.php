<?php

declare(strict_types=1);

namespace App\Model\Seo;

use App\Entity\Core\Website;
use App\Entity\Seo\SeoConfiguration;
use App\Model\BaseModel;
use App\Model\IntlModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;

/**
 * SeoConfigurationModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class SeoConfigurationModel extends BaseModel
{
    private static array $cache = [];

    /**
     * SeoConfigurationModel constructor.
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?SeoConfiguration $entity = null,
        public readonly ?bool $microData = null,
        public readonly ?bool $disableAfterDash = null,
        public readonly ?array $disabledIps = null,
        public readonly ?IntlModel $intl = null,
    ) {
    }

    /**
     * Get model.
     *
     * @throws NonUniqueResultException|MappingException
     */
    public static function fromEntity(Website $website, CoreLocatorInterface $coreLocator, ?string $locale = null): self
    {
        self::setLocator($coreLocator);

        $locale = $locale ?: self::$coreLocator->locale();

        if (isset(self::$cache['response'][$website->getId()][$locale])) {
            return self::$cache['response'][$website->getId()][$locale];
        }

        $seoConfiguration = $website->getSeoConfiguration();

        self::$cache['response'][$seoConfiguration->getId()][$locale] = new self(
            id: self::getContent('id', $seoConfiguration),
            entity: $seoConfiguration,
            microData: self::getContent('microData', $seoConfiguration, true),
            disableAfterDash: self::getContent('disableAfterDash', $seoConfiguration, true),
            disabledIps: self::getContent('disableAfterDash', $seoConfiguration, false, true),
            intl: IntlModel::fromEntity($seoConfiguration, $coreLocator, false),
        );

        return self::$cache['response'][$seoConfiguration->getId()][$locale];
    }
}
