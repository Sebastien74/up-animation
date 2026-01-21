<?php

declare(strict_types=1);

namespace App\Entity\Security;

use App\Entity\BaseEntity;
use App\Entity\Information\Address;
use App\Entity\Information\Phone;
use App\Repository\Security\ProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * Profile.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'security_profile')]
#[ORM\Entity(repositoryClass: ProfileRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'phones',
        joinColumns: [new ORM\JoinColumn(name: 'profile_id', referencedColumnName: 'id', onDelete: 'cascade')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'phone_id', referencedColumnName: 'id', unique: true, onDelete: 'cascade')],
        joinTable: new ORM\JoinTable(name: 'security_profile_phones')
    ),
    new ORM\AssociationOverride(
        name: 'addresses',
        joinColumns: [new ORM\JoinColumn(name: 'profile_id', referencedColumnName: 'id', onDelete: 'cascade')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'address_id', referencedColumnName: 'id', unique: true, onDelete: 'cascade')],
        joinTable: new ORM\JoinTable(name: 'security_profile_addresses')
    ),
])]
class Profile extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'profile',
    ];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $gender = null;

    #[ORM\OneToOne(targetEntity: User::class, mappedBy: 'profile', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    #[ORM\OneToOne(targetEntity: UserFront::class, mappedBy: 'profile', cascade: ['persist', 'remove'])]
    private ?UserFront $userFront = null;

    #[ORM\OneToMany(targetEntity: Link::class, mappedBy: 'profile', cascade: ['persist', 'remove'])]
    private ArrayCollection|PersistentCollection $links;

    #[ORM\ManyToMany(targetEntity: Phone::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    private ArrayCollection|PersistentCollection $phones;

    #[ORM\ManyToMany(targetEntity: Address::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    private ArrayCollection|PersistentCollection $addresses;

    /**
     * Profile constructor.
     */
    public function __construct()
    {
        $this->links = new ArrayCollection();
        $this->phones = new ArrayCollection();
        $this->addresses = new ArrayCollection();
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        // unset the owning side of the relation if necessary
        if ($user === null && $this->user !== null) {
            $this->user->setProfile(null);
        }

        // set the owning side of the relation if necessary
        if ($user !== null && $user->getProfile() !== $this) {
            $user->setProfile($this);
        }

        $this->user = $user;

        return $this;
    }

    public function getUserFront(): ?UserFront
    {
        return $this->userFront;
    }

    public function setUserFront(?UserFront $userFront): static
    {
        // unset the owning side of the relation if necessary
        if ($userFront === null && $this->userFront !== null) {
            $this->userFront->setProfile(null);
        }

        // set the owning side of the relation if necessary
        if ($userFront !== null && $userFront->getProfile() !== $this) {
            $userFront->setProfile($this);
        }

        $this->userFront = $userFront;

        return $this;
    }

    /**
     * @return Collection<int, Link>
     */
    public function getLinks(): Collection
    {
        return $this->links;
    }

    public function addLink(Link $link): static
    {
        if (!$this->links->contains($link)) {
            $this->links->add($link);
            $link->setProfile($this);
        }

        return $this;
    }

    public function removeLink(Link $link): static
    {
        if ($this->links->removeElement($link)) {
            // set the owning side to null (unless already changed)
            if ($link->getProfile() === $this) {
                $link->setProfile(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Phone>
     */
    public function getPhones(): Collection
    {
        return $this->phones;
    }

    public function addPhone(Phone $phone): static
    {
        if (!$this->phones->contains($phone)) {
            $this->phones->add($phone);
        }

        return $this;
    }

    public function removePhone(Phone $phone): static
    {
        $this->phones->removeElement($phone);

        return $this;
    }

    /**
     * @return Collection<int, Address>
     */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function addAddress(Address $address): static
    {
        if (!$this->addresses->contains($address)) {
            $this->addresses->add($address);
        }

        return $this;
    }

    public function removeAddress(Address $address): static
    {
        $this->addresses->removeElement($address);

        return $this;
    }
}
