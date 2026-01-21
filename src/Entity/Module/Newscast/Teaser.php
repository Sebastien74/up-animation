<?php

declare(strict_types=1);

namespace App\Entity\Module\Newscast;

use App\Entity\BaseTeaser;
use App\Repository\Module\Newscast\TeaserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Teaser.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_newscast_teaser')]
#[ORM\Entity(repositoryClass: TeaserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'categories',
        joinColumns: [new ORM\JoinColumn(name: 'teaser_id', referencedColumnName: 'id', onDelete: 'cascade')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'category_id', referencedColumnName: 'id', onDelete: 'cascade')],
        joinTable: new ORM\JoinTable(name: 'module_newscast_teaser_categories')
    ),
])]
class Teaser extends BaseTeaser
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'newscastteaser',
        'module' => 'newscast',
        'prePersistTitle' => false,
    ];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $displayFilters = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $asEvents = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $pastEvents = false;

    #[ORM\OneToMany(mappedBy: 'teaser', targetEntity: TeaserIntl::class, cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToMany(targetEntity: Category::class, cascade: ['persist'])]
    #[ORM\OrderBy(['adminName' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $categories;

    /**
     * Teaser constructor.
     */
    public function __construct()
    {
        $this->intls = new ArrayCollection();
        $this->categories = new ArrayCollection();
    }

    public function isDisplayFilters(): ?bool
    {
        return $this->displayFilters;
    }

    public function setDisplayFilters(bool $displayFilters): static
    {
        $this->displayFilters = $displayFilters;

        return $this;
    }

    public function isAsEvents(): ?bool
    {
        return $this->asEvents;
    }

    public function setAsEvents(bool $asEvents): static
    {
        $this->asEvents = $asEvents;

        return $this;
    }

    public function isPastEvents(): ?bool
    {
        return $this->pastEvents;
    }

    public function setPastEvents(bool $pastEvents): static
    {
        $this->pastEvents = $pastEvents;

        return $this;
    }

    /**
     * @return Collection<int, TeaserIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(TeaserIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setTeaser($this);
        }

        return $this;
    }

    public function removeIntl(TeaserIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getTeaser() === $this) {
                $intl->setTeaser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }
}
