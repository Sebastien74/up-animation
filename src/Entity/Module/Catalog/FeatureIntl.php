<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseIntl;
use App\Repository\Module\Catalog\FeatureIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * FeatureIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_catalog_feature_intls')]
#[ORM\Entity(repositoryClass: FeatureIntlRepository::class)]
class FeatureIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Feature::class, cascade: ['persist'], inversedBy: 'intls')]
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
