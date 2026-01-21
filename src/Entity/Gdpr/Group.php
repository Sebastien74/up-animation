<?php

declare(strict_types=1);

namespace App\Entity\Gdpr;

use App\Entity\BaseEntity;
use App\Repository\Gdpr\GroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Group.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'gdpr_group')]
#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Group extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'gdprcategory';
    protected static array $interface = [
        'name' => 'gdprgroup',
        'buttons' => [
            'admin_gdprcookie_index',
        ],
    ];
    protected static array $labels = [
        'admin_gdprcookie_index' => 'Cookies',
    ];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $active = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $anonymize = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $scriptInHead = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $service = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $script = null;

    #[ORM\OneToMany(targetEntity: Cookie::class, mappedBy: 'gdprgroup', cascade: ['persist'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $gdprcookies;

    #[ORM\OneToMany(targetEntity: GroupMediaRelation::class, mappedBy: 'group', cascade: ['persist'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['position' => 'ASC', 'locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $mediaRelations;

    #[ORM\OneToMany(targetEntity: GroupIntl::class, mappedBy: 'group', cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'gdprgroups')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private ?Category $gdprcategory = null;

    /**
     * Group constructor.
     */
    public function __construct()
    {
        $this->gdprcookies = new ArrayCollection();
        $this->mediaRelations = new ArrayCollection();
        $this->intls = new ArrayCollection();
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function isAnonymize(): ?bool
    {
        return $this->anonymize;
    }

    public function setAnonymize(bool $anonymize): static
    {
        $this->anonymize = $anonymize;

        return $this;
    }

    public function isScriptInHead(): ?bool
    {
        return $this->scriptInHead;
    }

    public function setScriptInHead(bool $scriptInHead): static
    {
        $this->scriptInHead = $scriptInHead;

        return $this;
    }

    public function getService(): ?string
    {
        return $this->service;
    }

    public function setService(?string $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getScript(): ?string
    {
        return $this->script;
    }

    public function setScript(?string $script): static
    {
        $this->script = $script;

        return $this;
    }

    /**
     * @return Collection<int, Cookie>
     */
    public function getGdprcookies(): Collection
    {
        return $this->gdprcookies;
    }

    public function addGdprcooky(Cookie $gdprcooky): static
    {
        if (!$this->gdprcookies->contains($gdprcooky)) {
            $this->gdprcookies->add($gdprcooky);
            $gdprcooky->setGdprgroup($this);
        }

        return $this;
    }

    public function removeGdprcooky(Cookie $gdprcooky): static
    {
        if ($this->gdprcookies->removeElement($gdprcooky)) {
            // set the owning side to null (unless already changed)
            if ($gdprcooky->getGdprgroup() === $this) {
                $gdprcooky->setGdprgroup(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, GroupMediaRelation>
     */
    public function getMediaRelations(): Collection
    {
        return $this->mediaRelations;
    }

    public function addMediaRelation(GroupMediaRelation $mediaRelation): static
    {
        if (!$this->mediaRelations->contains($mediaRelation)) {
            $this->mediaRelations->add($mediaRelation);
            $mediaRelation->setGroup($this);
        }

        return $this;
    }

    public function removeMediaRelation(GroupMediaRelation $mediaRelation): static
    {
        if ($this->mediaRelations->removeElement($mediaRelation)) {
            // set the owning side to null (unless already changed)
            if ($mediaRelation->getGroup() === $this) {
                $mediaRelation->setGroup(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, GroupIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(GroupIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setGroup($this);
        }

        return $this;
    }

    public function removeIntl(GroupIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getGroup() === $this) {
                $intl->setGroup(null);
            }
        }

        return $this;
    }

    public function getGdprcategory(): ?Category
    {
        return $this->gdprcategory;
    }

    public function setGdprcategory(?Category $gdprcategory): static
    {
        $this->gdprcategory = $gdprcategory;

        return $this;
    }
}
