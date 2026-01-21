<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseEntity;
use App\Entity\Information\Address;
use App\Entity\Information\SocialNetwork;
use App\Repository\Module\Catalog\ProductInformationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Information.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_catalog_product_information')]
#[ORM\Entity(repositoryClass: ProductInformationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ProductInformation extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'information',
    ];

    #[ORM\OneToOne(targetEntity: Product::class, mappedBy: 'information')]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?Product $product = null;

    #[ORM\OneToOne(targetEntity: Address::class, cascade: ['persist', 'remove'], fetch: 'EAGER')]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?Address $address = null;

    #[ORM\OneToOne(targetEntity: SocialNetwork::class, fetch: 'EAGER')]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?SocialNetwork $socialNetworks = null;

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        // unset the owning side of the relation if necessary
        if (null === $product && null !== $this->product) {
            $this->product->setInformation(null);
        }

        // set the owning side of the relation if necessary
        if (null !== $product && $product->getInformation() !== $this) {
            $product->setInformation($this);
        }

        $this->product = $product;

        return $this;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getSocialNetworks(): ?SocialNetwork
    {
        return $this->socialNetworks;
    }

    public function setSocialNetworks(?SocialNetwork $socialNetworks): static
    {
        $this->socialNetworks = $socialNetworks;

        return $this;
    }
}
