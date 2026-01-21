<?php

declare(strict_types=1);

namespace App\Entity\Information;

use App\Entity\BaseEntity;
use App\Repository\Information\EmailRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Email.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'information_email')]
#[ORM\Entity(repositoryClass: EmailRepository::class)]
class Email extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'email',
    ];

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    private ?string $locale = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $entitled = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $email = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $zones = [];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $deletable = true;

    #[ORM\OneToMany(targetEntity: EmailIntl::class, mappedBy: 'email', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Address::class, cascade: ['persist'], inversedBy: 'emails')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Address $address = null;

    #[ORM\ManyToOne(targetEntity: Information::class, cascade: ['persist'], inversedBy: 'emails')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Information $information = null;

    /**
     * Email constructor.
     */
    public function __construct()
    {
        $this->intls = new ArrayCollection();
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getEntitled(): ?string
    {
        return $this->entitled;
    }

    public function setEntitled(?string $entitled): static
    {
        $this->entitled = $entitled;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getZones(): ?array
    {
        return $this->zones;
    }

    public function setZones(?array $zones): static
    {
        $this->zones = $zones;

        return $this;
    }

    public function isDeletable(): ?bool
    {
        return $this->deletable;
    }

    public function setDeletable(bool $deletable): static
    {
        $this->deletable = $deletable;

        return $this;
    }

    /**
     * @return Collection<int, EmailIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(EmailIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setEmail($this);
        }

        return $this;
    }

    public function removeIntl(EmailIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getEmail() === $this) {
                $intl->setEmail(null);
            }
        }

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

    public function getInformation(): ?Information
    {
        return $this->information;
    }

    public function setInformation(?Information $information): static
    {
        $this->information = $information;

        return $this;
    }
}
