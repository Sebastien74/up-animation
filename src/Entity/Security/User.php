<?php

declare(strict_types=1);

namespace App\Entity\Security;

use App\Entity\BaseSecurity;
use App\Entity\Core\Website;
use App\Repository\Security\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'security_user')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'companies',
        joinColumns: [new ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'cascade')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'company_id', referencedColumnName: 'id')],
        joinTable: new ORM\JoinTable(name: 'security_users_companies')
    ),
    new ORM\AssociationOverride(
        name: 'websites',
        joinColumns: [new ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'cascade')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'website_id', referencedColumnName: 'id')],
        joinTable: new ORM\JoinTable(name: 'security_users_websites')
    ),
])]
class User extends BaseSecurity
{
    /**
     * Configurations.
     */
    protected static string $masterField = '';
    protected static array $interface = [
        'name' => 'user',
    ];
    protected static array $labels = [];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $theme = null;

    #[ORM\OneToOne(targetEntity: Picture::class, inversedBy: 'user', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'cascade')]
    private ?Picture $picture = null;

    #[ORM\OneToOne(targetEntity: Profile::class, inversedBy: 'user', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'cascade')]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?Profile $profile = null;

    #[ORM\ManyToMany(targetEntity: Company::class)]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private ArrayCollection|PersistentCollection $companies;

    #[ORM\ManyToMany(targetEntity: Website::class)]
    #[ORM\OrderBy(['adminName' => 'ASC'])]
    private ArrayCollection|PersistentCollection $websites;

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->companies = new ArrayCollection();
        $this->websites = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(?string $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    public function getPicture(): ?Picture
    {
        return $this->picture;
    }

    public function setPicture(?Picture $picture): static
    {
        $this->picture = $picture;

        return $this;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setProfile(?Profile $profile): static
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * @return Collection<int, Company>
     */
    public function getCompanies(): Collection
    {
        return $this->companies;
    }

    public function addCompany(Company $company): static
    {
        if (!$this->companies->contains($company)) {
            $this->companies->add($company);
        }

        return $this;
    }

    public function removeCompany(Company $company): static
    {
        $this->companies->removeElement($company);

        return $this;
    }

    /**
     * @return Collection<int, Website>
     */
    public function getWebsites(): Collection
    {
        return $this->websites;
    }

    public function addWebsite(Website $website): static
    {
        if (!$this->websites->contains($website)) {
            $this->websites->add($website);
        }

        return $this;
    }

    public function removeWebsite(Website $website): static
    {
        $this->websites->removeElement($website);

        return $this;
    }
}
