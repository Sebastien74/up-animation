<?php

declare(strict_types=1);

namespace App\Model\Module;

use App\Entity\Module\Tab\Tab;
use App\Model\BaseModel;
use App\Model\EntityModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;

/**
 * TabModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class TabModel extends BaseModel
{
    /**
     * fromEntity.
     *
     * @throws MappingException|NonUniqueResultException
     */
    public static function fromEntity(Tab $tab, CoreLocatorInterface $coreLocator, array $options = []): object
    {
        $model = (array) EntityModel::fromEntity($tab, $coreLocator, array_merge($options))->response;
        $tree = self::$coreLocator->treeService()->execute($tab->getContents());

        return (object) array_merge($model, [
            'tabs' => !empty($tree['main']) ? self::tabs($tree, $tree['main']) : [],
        ]);
    }

    /**
     * To set tabs as models.
     *
     * @throws MappingException|NonUniqueResultException
     */
    private static function tabs(array $tree, array $tabs): array
    {
        $result = [];
        foreach ($tabs as $key => $tab) {
            $result[$key]['entity'] = EntityModel::fromEntity($tab, self::$coreLocator)->response;
            $result[$key]['children'] = [];
            if (!empty($tree[$key])) {
                foreach ($tree[$key] as $child) {
                    $result[$key]['children'][] = EntityModel::fromEntity($child, self::$coreLocator)->response;
                }
            }
        }

        return $result;
    }
}
