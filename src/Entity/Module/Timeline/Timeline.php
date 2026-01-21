<?php

declare(strict_types=1);

namespace App\Entity\Module\Timeline;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Repository\Module\Timeline\TimelineRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Timeline.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_timeline')]
#[ORM\Entity(repositoryClass: TimelineRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Timeline extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'timeline',
        'buttons' => [
            'admin_timelinestep_index',
        ],
    ];
    protected static array $labels = [
        'admin_timelinestep_index' => 'Étapes',
    ];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $displayNumbers = true;

    #[ORM\OneToMany(mappedBy: 'timeline', targetEntity: Step::class, orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $steps;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    /**
     * Timeline constructor.
     */
    public function __construct()
    {
        $this->steps = new ArrayCollection();
    }

    public function isDisplayNumbers(): ?bool
    {
        return $this->displayNumbers;
    }

    public function setDisplayNumbers(bool $displayNumbers): static
    {
        $this->displayNumbers = $displayNumbers;

        return $this;
    }

    /**
     * @return Collection<int, Step>
     */
    public function getSteps(): Collection
    {
        return $this->steps;
    }

    public function addStep(Step $step): static
    {
        if (!$this->steps->contains($step)) {
            $this->steps->add($step);
            $step->setTimeline($this);
        }

        return $this;
    }

    public function removeStep(Step $step): static
    {
        if ($this->steps->removeElement($step)) {
            // set the owning side to null (unless already changed)
            if ($step->getTimeline() === $this) {
                $step->setTimeline(null);
            }
        }

        return $this;
    }

    public function getWebsite(): ?Website
    {
        return $this->website;
    }

    public function setWebsite(?Website $website): static
    {
        $this->website = $website;

        return $this;
    }
}
