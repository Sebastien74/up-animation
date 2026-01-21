<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseEntity;
use App\Repository\Module\Catalog\ListingFeatureValueRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * ListingFeatureValue.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_catalog_listing_feature_value')]
#[ORM\Entity(repositoryClass: ListingFeatureValueRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ListingFeatureValue extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'listing';
    protected static array $interface = [
        'name' => 'cataloglistingfeature',
    ];

    #[ORM\ManyToOne(targetEntity: Listing::class, inversedBy: 'featuresValues')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Listing $listing = null;

    #[ORM\ManyToOne(targetEntity: FeatureValue::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?FeatureValue $value = null;

    public function getListing(): ?Listing
    {
        return $this->listing;
    }

    public function setListing(?Listing $listing): static
    {
        $this->listing = $listing;

        return $this;
    }

    public function getValue(): ?FeatureValue
    {
        return $this->value;
    }

    public function setValue(?FeatureValue $value): static
    {
        $this->value = $value;

        return $this;
    }
}
