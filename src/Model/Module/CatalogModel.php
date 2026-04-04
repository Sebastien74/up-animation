<?php

declare(strict_types=1);

namespace App\Model\Module;

use App\Entity\Module\Catalog\Catalog;
use App\Entity\Module\Catalog\Product;
use App\Model\BaseModel;
use App\Model\EntityModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Psr\Cache\InvalidArgumentException;
use ReflectionException;

/**
 * CatalogModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class CatalogModel extends BaseModel
{
    /**
     * CatalogModel constructor.
     */
    public function __construct(
        public readonly array $products = [],
        public readonly ?object $catalog = null,
    ) {
    }

    /**
     * fromEntity.
     *
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException|ReflectionException|QueryException
     */
    public static function fromEntity(Catalog $catalog, CoreLocatorInterface $coreLocator, array $options = []): object
    {
        $disabledInfo = array_key_exists('disabledInfo', $options) ? $options['disabledInfo'] : false;

        if (isset($options['onlyForUrl'])) {
           $options = array_merge($options, [
               'disabledValues' => true,
               'disabledProducts' => true,
               'disabledLayout' => true,
               'disabledMedias' => true,
               'disabledCategories' => true,
               'disabledInfo' => $disabledInfo,
               'disabledCategory' => true
           ]);
        }

        self::setLocator($coreLocator);
        $website = self::$coreLocator->website();
        $model = EntityModel::fromEntity($catalog, $coreLocator, array_merge($options))->response;
        $productsDb = self::$coreLocator->em()->getRepository(Product::class)->findOnlineByCatalogs($website->entity, self::$coreLocator->locale(), [$catalog], null, $options);
        $products = [];
        foreach ($productsDb as $product) {
            $products[$product->getPosition()] = ProductModel::fromEntity($product, $coreLocator, array_merge($options));
        }
        ksort($products);

        return new self(
            products: $products,
            catalog: $model,
        );
    }
}
