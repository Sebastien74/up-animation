<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseMediaRelation;
use App\Repository\Module\Catalog\FeatureValueMediaRelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * FeatureValueMediaRelation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_catalog_feature_value_media_relations')]
#[ORM\Entity(repositoryClass: FeatureValueMediaRelationRepository::class)]
class FeatureValueMediaRelation extends BaseMediaRelation
{
    #[ORM\ManyToOne(targetEntity: FeatureValue::class, cascade: ['persist'], inversedBy: 'mediaRelations')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?FeatureValue $featureValue = null;

    public function getFeatureValue(): ?FeatureValue
    {
        return $this->featureValue;
    }

    public function setFeatureValue(?FeatureValue $featureValue): static
    {
        $this->featureValue = $featureValue;

        return $this;
    }
}
