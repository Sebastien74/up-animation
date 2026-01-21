<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Repository\Api\CustomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Custom.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'api_custom')]
#[ORM\Entity(repositoryClass: CustomRepository::class)]
class Custom
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Api::class, mappedBy: 'custom', cascade: ['persist', 'remove'])]
    private ?Api $api = null;

    #[ORM\OneToMany(targetEntity: CustomIntl::class, mappedBy: 'custom', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    /**
     * Custom constructor.
     */
    public function __construct()
    {
        $this->intls = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApi(): ?Api
    {
        return $this->api;
    }

    public function setApi(?Api $api): static
    {
        // unset the owning side of the relation if necessary
        if ($api === null && $this->api !== null) {
            $this->api->setCustom(null);
        }

        // set the owning side of the relation if necessary
        if ($api !== null && $api->getCustom() !== $this) {
            $api->setCustom($this);
        }

        $this->api = $api;

        return $this;
    }

    /**
     * @return Collection<int, CustomIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(CustomIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setCustom($this);
        }

        return $this;
    }

    public function removeIntl(CustomIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getCustom() === $this) {
                $intl->setCustom(null);
            }
        }

        return $this;
    }
}
