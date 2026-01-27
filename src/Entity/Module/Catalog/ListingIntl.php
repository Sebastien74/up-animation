<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseIntl;
use App\Repository\Module\Catalog\ListingIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * ListingIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_catalog_listing_intls')]
#[ORM\Entity(repositoryClass: ListingIntlRepository::class)]
class ListingIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Listing::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Listing $listing = null;

    public function getListing(): ?Listing
    {
        return $this->listing;
    }

    public function setListing(?Listing $listing): static
    {
        $this->listing = $listing;

        return $this;
    }
}
