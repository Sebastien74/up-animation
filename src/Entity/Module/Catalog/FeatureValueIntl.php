<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseIntl;
use App\Repository\Module\Catalog\FeatureValueIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * FeatureValueMediaRelation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_catalog_feature_value_intls')]
#[ORM\Entity(repositoryClass: FeatureValueIntlRepository::class)]
class FeatureValueIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: FeatureValue::class, cascade: ['persist'], inversedBy: 'intls')]
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
