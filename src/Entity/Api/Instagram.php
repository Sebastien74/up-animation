<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Repository\Api\InstagramRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Instagram.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'api_instagram')]
#[ORM\Entity(repositoryClass: InstagramRepository::class)]
class Instagram
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $accessToken = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\NotBlank]
    private ?int $nbrItems = 7;

    #[ORM\OneToOne(targetEntity: Api::class, mappedBy: 'instagram', cascade: ['persist', 'remove'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?Api $api = null;

    #[ORM\OneToMany(targetEntity: InstagramIntl::class, mappedBy: 'instagram', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    /**
     * Facebook constructor.
     */
    public function __construct()
    {
        $this->intls = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(?string $accessToken): static
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function getNbrItems(): ?int
    {
        return $this->nbrItems;
    }

    public function setNbrItems(?int $nbrItems): static
    {
        $this->nbrItems = $nbrItems;

        return $this;
    }

    public function getApi(): ?Api
    {
        return $this->api;
    }

    public function setApi(?Api $api): static
    {
        // unset the owning side of the relation if necessary
        if ($api === null && $this->api !== null) {
            $this->api->setInstagram(null);
        }

        // set the owning side of the relation if necessary
        if ($api !== null && $api->getInstagram() !== $this) {
            $api->setInstagram($this);
        }

        $this->api = $api;

        return $this;
    }

    /**
     * @return Collection<int, InstagramIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(InstagramIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setInstagram($this);
        }

        return $this;
    }

    public function removeIntl(InstagramIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getInstagram() === $this) {
                $intl->setInstagram(null);
            }
        }

        return $this;
    }
}
