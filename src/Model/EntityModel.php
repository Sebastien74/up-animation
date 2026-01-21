<?php

declare(strict_types=1);

namespace App\Model;

use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;

/**
 * EntityModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class EntityModel extends BaseModel
{
    /**
     * EntityModel constructor.
     */
    public function __construct(
        public readonly object $response,
    ) {
    }

    /**
     * fromEntity.
     *
     * @throws NonUniqueResultException|MappingException
     */
    public static function fromEntity(mixed $entity, CoreLocatorInterface $coreLocator, array $options = []): self
    {
        self::setLocator($coreLocator);
        $locale = self::$coreLocator->locale();

        $disabledIntl = isset($options['disabledIntl']) && $options['disabledIntl'];
        $disabledMedias = isset($options['disabledMedias']) && $options['disabledMedias'];
        $disabledLayout = isset($options['disabledLayout']) && $options['disabledLayout'];
        $medias = !$disabledMedias ? MediasModel::fromEntity($entity, $coreLocator, $locale, false) : null;
        $layout = !$disabledLayout ? self::getContent('layout', $entity) : null;

        $response = [
            'entity' => $entity,
            'intl' => !$disabledIntl ? IntlModel::fromEntity($entity, $coreLocator, false) : null,
            'medias' => !$disabledMedias ? $medias->list : null,
            'mediasWithoutMain' => !$disabledMedias ? $medias->withoutMain : null,
            'mainMedia' => !$disabledMedias && $medias->main && property_exists($medias->main, 'media') ? $medias->main : null,
            'haveMedias' => !$disabledMedias ? $medias->haveMain : false,
            'haveMainMedia' => !$disabledMedias ? $medias->haveMedias : false,
            'layout' => $layout && !$layout->getZones()->isEmpty() ? $layout : null,
        ];

        $excluded = [];
        $metadata = $entity && method_exists($entity, 'getId') ? self::$coreLocator->em()->getClassMetadata(get_class($entity)) : [];
        if ($metadata) {
            foreach ($metadata->getFieldNames() as $name) {
                if (!in_array($name, $excluded) && !isset($response[$name])) {
                    $getMethod = 'get'.ucfirst($name);
                    $isMethod = 'is'.ucfirst($name);
                    $response[$name] = method_exists($entity, $getMethod) ? $entity->$getMethod() : (method_exists($entity, $isMethod) ? $entity->$isMethod() : null);
                }
            }
        }
        $response['categories'] = self::getContent('categories', $entity, false, true, true);

        return new self(
            response: (object) $response,
        );
    }
}
