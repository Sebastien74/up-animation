<?php

declare(strict_types=1);

namespace App\Model\Module;

use App\Controller\Front\Action\FaqController;
use App\Entity\Layout\Block;
use App\Entity\Module\Catalog\FeatureValue;
use App\Entity\Module\Catalog\FeatureValueProduct;
use App\Entity\Module\Faq\Faq;
use App\Model\BaseModel;
use App\Model\Core\WebsiteModel;
use App\Model\EntityModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;

/**
 * FeatureValueModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class FeatureValueModel extends BaseModel
{

    /**
     * FeatureValueModel constructor.
     */
    public function __construct(
        public readonly int $id,
    ) {
    }
    private static array $cache = [];

    /**
     * fromEntity.
     */
    public static function fromEntity(FeatureValueProduct $value, CoreLocatorInterface $coreLocator): object
    {
        return new self(
            id: $value->getId(),
        );
    }
}
