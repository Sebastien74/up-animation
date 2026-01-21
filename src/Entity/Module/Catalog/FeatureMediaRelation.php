<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseMediaRelation;
use App\Repository\Module\Catalog\FeatureMediaRelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * FeatureMediaRelation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_catalog_feature_media_relations')]
#[ORM\Entity(repositoryClass: FeatureMediaRelationRepository::class)]
class FeatureMediaRelation extends BaseMediaRelation
{
    #[ORM\ManyToOne(targetEntity: Feature::class, cascade: ['persist'], inversedBy: 'mediaRelations')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Feature $feature = null;

    public function getFeature(): ?Feature
    {
        return $this->feature;
    }

    public function setFeature(?Feature $feature): static
    {
        $this->feature = $feature;

        return $this;
    }
}
