<?php

declare(strict_types=1);

namespace App\Entity\Module\Newscast;

use App\Entity\BaseListing;
use App\Repository\Module\Newscast\ListingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Listing.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_newscast_listing')]
#[ORM\Entity(repositoryClass: ListingRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'categories',
        joinColumns: [new ORM\JoinColumn(name: 'listing_id', referencedColumnName: 'id', onDelete: 'cascade')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'category_id', referencedColumnName: 'id', onDelete: 'cascade')],
        joinTable: new ORM\JoinTable(name: 'module_newscast_listing_categories')
    ),
])]
class Listing extends BaseListing
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'newscastlisting',
    ];

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $asEvents = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $pastEvents = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $counter = false;

    #[ORM\ManyToMany(targetEntity: Category::class, cascade: ['persist'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $categories;

    /**
     * Listing constructor.
     */
    public function __construct()
    {
        $this->categories = new ArrayCollection();
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

    public function isCounter(): ?bool
    {
        return $this->counter;
    }

    public function setCounter(bool $counter): static
    {
        $this->counter = $counter;

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
