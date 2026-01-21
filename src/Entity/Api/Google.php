<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Repository\Api\GoogleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Google.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'api_google')]
#[ORM\Entity(repositoryClass: GoogleRepository::class)]
class Google
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Api::class, mappedBy: 'google', cascade: ['persist', 'remove'])]
    private ?Api $api = null;

    #[ORM\OneToMany(targetEntity: GoogleIntl::class, mappedBy: 'google', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    /**
     * Google constructor.
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
            $this->api->setGoogle(null);
        }

        // set the owning side of the relation if necessary
        if ($api !== null && $api->getGoogle() !== $this) {
            $api->setGoogle($this);
        }

        $this->api = $api;

        return $this;
    }

    /**
     * @return Collection<int, GoogleIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(GoogleIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setGoogle($this);
        }

        return $this;
    }

    public function removeIntl(GoogleIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getGoogle() === $this) {
                $intl->setGoogle(null);
            }
        }

        return $this;
    }
}
