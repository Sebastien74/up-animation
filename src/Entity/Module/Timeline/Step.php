<?php

declare(strict_types=1);

namespace App\Entity\Module\Timeline;

use App\Entity\BaseEntity;
use App\Repository\Module\Timeline\StepRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Step.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_timeline_step')]
#[ORM\Entity(repositoryClass: StepRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Step extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'timeline';
    protected static array $interface = [
        'name' => 'timelinestep',
    ];

    #[ORM\OneToMany(mappedBy: 'step', targetEntity: StepMediaRelation::class, cascade: ['persist'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['position' => 'ASC', 'locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $mediaRelations;

    #[ORM\OneToMany(mappedBy: 'step', targetEntity: StepIntl::class, cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Timeline::class, inversedBy: 'steps')]
    private ?Timeline $timeline = null;

    /**
     * Step constructor.
     */
    public function __construct()
    {
        $this->mediaRelations = new ArrayCollection();
        $this->intls = new ArrayCollection();
    }

    /**
     * @return Collection<int, StepMediaRelation>
     */
    public function getMediaRelations(): Collection
    {
        return $this->mediaRelations;
    }

    public function addMediaRelation(StepMediaRelation $mediaRelation): static
    {
        if (!$this->mediaRelations->contains($mediaRelation)) {
            $this->mediaRelations->add($mediaRelation);
            $mediaRelation->setStep($this);
        }

        return $this;
    }

    public function removeMediaRelation(StepMediaRelation $mediaRelation): static
    {
        if ($this->mediaRelations->removeElement($mediaRelation)) {
            // set the owning side to null (unless already changed)
            if ($mediaRelation->getStep() === $this) {
                $mediaRelation->setStep(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StepIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(StepIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setStep($this);
        }

        return $this;
    }

    public function removeIntl(StepIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getStep() === $this) {
                $intl->setStep(null);
            }
        }

        return $this;
    }

    public function getTimeline(): ?Timeline
    {
        return $this->timeline;
    }

    public function setTimeline(?Timeline $timeline): static
    {
        $this->timeline = $timeline;

        return $this;
    }
}
