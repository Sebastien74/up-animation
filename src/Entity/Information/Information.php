<?php

declare(strict_types=1);

namespace App\Entity\Information;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Repository\Information\InformationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Information.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'information')]
#[ORM\Entity(repositoryClass: InformationRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'addresses',
        joinColumns: [new ORM\JoinColumn(name: 'information_id', referencedColumnName: 'id', onDelete: 'cascade')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'address_id', referencedColumnName: 'id', unique: true, onDelete: 'cascade')],
        joinTable: new ORM\JoinTable(name: 'information_addresses')
    ),
])]
class Information extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'information',
    ];

    #[ORM\OneToOne(targetEntity: Website::class, mappedBy: 'information')]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?Website $website = null;

    #[ORM\OneToMany(targetEntity: SocialNetwork::class, mappedBy: 'information', cascade: ['persist'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $socialNetworks;

    #[ORM\OneToMany(targetEntity: Phone::class, mappedBy: 'information', cascade: ['persist'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $phones;

    #[ORM\OneToMany(targetEntity: Email::class, mappedBy: 'information', cascade: ['persist'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private ArrayCollection|PersistentCollection $emails;

    #[ORM\OneToMany(targetEntity: Legal::class, mappedBy: 'information', cascade: ['persist'], fetch: 'EAGER', orphanRemoval: true)]
    private ArrayCollection|PersistentCollection $legals;

    #[ORM\OneToMany(targetEntity: ScheduleDay::class, mappedBy: 'information', cascade: ['persist'], fetch: 'EAGER', orphanRemoval: true)]
    private ArrayCollection|PersistentCollection $scheduleDays;

    #[ORM\OneToMany(targetEntity: InformationIntl::class, mappedBy: 'information', cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToMany(targetEntity: Address::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    private ArrayCollection|PersistentCollection $addresses;

    /**
     * Information constructor.
     */
    public function __construct()
    {
        $this->socialNetworks = new ArrayCollection();
        $this->phones = new ArrayCollection();
        $this->emails = new ArrayCollection();
        $this->legals = new ArrayCollection();
        $this->scheduleDays = new ArrayCollection();
        $this->intls = new ArrayCollection();
        $this->addresses = new ArrayCollection();
    }

    public function getWebsite(): ?Website
    {
        return $this->website;
    }

    public function setWebsite(?Website $website): static
    {
        // unset the owning side of the relation if necessary
        if ($website === null && $this->website !== null) {
            $this->website->setInformation(null);
        }

        // set the owning side of the relation if necessary
        if ($website !== null && $website->getInformation() !== $this) {
            $website->setInformation($this);
        }

        $this->website = $website;

        return $this;
    }

    /**
     * @return Collection<int, SocialNetwork>
     */
    public function getSocialNetworks(): Collection
    {
        return $this->socialNetworks;
    }

    public function addSocialNetwork(SocialNetwork $socialNetwork): static
    {
        if (!$this->socialNetworks->contains($socialNetwork)) {
            $this->socialNetworks->add($socialNetwork);
            $socialNetwork->setInformation($this);
        }

        return $this;
    }

    public function removeSocialNetwork(SocialNetwork $socialNetwork): static
    {
        if ($this->socialNetworks->removeElement($socialNetwork)) {
            // set the owning side to null (unless already changed)
            if ($socialNetwork->getInformation() === $this) {
                $socialNetwork->setInformation(null);
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
            $phone->setInformation($this);
        }

        return $this;
    }

    public function removePhone(Phone $phone): static
    {
        if ($this->phones->removeElement($phone)) {
            // set the owning side to null (unless already changed)
            if ($phone->getInformation() === $this) {
                $phone->setInformation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Email>
     */
    public function getEmails(): Collection
    {
        return $this->emails;
    }

    public function addEmail(Email $email): static
    {
        if (!$this->emails->contains($email)) {
            $this->emails->add($email);
            $email->setInformation($this);
        }

        return $this;
    }

    public function removeEmail(Email $email): static
    {
        if ($this->emails->removeElement($email)) {
            // set the owning side to null (unless already changed)
            if ($email->getInformation() === $this) {
                $email->setInformation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Legal>
     */
    public function getLegals(): Collection
    {
        return $this->legals;
    }

    public function addLegal(Legal $legal): static
    {
        if (!$this->legals->contains($legal)) {
            $this->legals->add($legal);
            $legal->setInformation($this);
        }

        return $this;
    }

    public function removeLegal(Legal $legal): static
    {
        if ($this->legals->removeElement($legal)) {
            // set the owning side to null (unless already changed)
            if ($legal->getInformation() === $this) {
                $legal->setInformation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ScheduleDay>
     */
    public function getScheduleDays(): Collection
    {
        return $this->scheduleDays;
    }

    public function addScheduleDay(ScheduleDay $scheduleDay): static
    {
        if (!$this->scheduleDays->contains($scheduleDay)) {
            $this->scheduleDays->add($scheduleDay);
            $scheduleDay->setInformation($this);
        }

        return $this;
    }

    public function removeScheduleDay(ScheduleDay $scheduleDay): static
    {
        if ($this->scheduleDays->removeElement($scheduleDay)) {
            // set the owning side to null (unless already changed)
            if ($scheduleDay->getInformation() === $this) {
                $scheduleDay->setInformation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, InformationIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(InformationIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setInformation($this);
        }

        return $this;
    }

    public function removeIntl(InformationIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getInformation() === $this) {
                $intl->setInformation(null);
            }
        }

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
