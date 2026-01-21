<?php

declare(strict_types=1);

namespace App\Entity\Module\Agenda;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Repository\Module\Agenda\AgendaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Agenda.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_agenda')]
#[ORM\Entity(repositoryClass: AgendaRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Agenda extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'agenda',
        'buttons' => [
            'periods' => 'admin_agendaperiod_edit',
        ],
    ];
    protected static array $labels = [
        'admin_agendaperiod_edit' => 'Calendrier',
    ];

    #[ORM\OneToMany(targetEntity: Period::class, mappedBy: 'agenda', orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['publicationStart' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $periods;

    #[ORM\OneToMany(targetEntity: AgendaMediaRelation::class, mappedBy: 'agenda', cascade: ['persist'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['position' => 'ASC', 'locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $mediaRelations;

    #[ORM\OneToMany(targetEntity: AgendaIntl::class, mappedBy: 'agenda', cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    /**
     * Agenda constructor.
     */
    public function __construct()
    {
        $this->periods = new ArrayCollection();
        $this->mediaRelations = new ArrayCollection();
        $this->intls = new ArrayCollection();
    }

    /**
     * @return Collection<int, Period>
     */
    public function getPeriods(): Collection
    {
        return $this->periods;
    }

    public function addPeriod(Period $period): static
    {
        if (!$this->periods->contains($period)) {
            $this->periods->add($period);
            $period->setAgenda($this);
        }

        return $this;
    }

    public function removePeriod(Period $period): static
    {
        if ($this->periods->removeElement($period)) {
            // set the owning side to null (unless already changed)
            if ($period->getAgenda() === $this) {
                $period->setAgenda(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AgendaMediaRelation>
     */
    public function getMediaRelations(): Collection
    {
        return $this->mediaRelations;
    }

    public function addMediaRelation(AgendaMediaRelation $mediaRelation): static
    {
        if (!$this->mediaRelations->contains($mediaRelation)) {
            $this->mediaRelations->add($mediaRelation);
            $mediaRelation->setAgenda($this);
        }

        return $this;
    }

    public function removeMediaRelation(AgendaMediaRelation $mediaRelation): static
    {
        if ($this->mediaRelations->removeElement($mediaRelation)) {
            // set the owning side to null (unless already changed)
            if ($mediaRelation->getAgenda() === $this) {
                $mediaRelation->setAgenda(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AgendaIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(AgendaIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setAgenda($this);
        }

        return $this;
    }

    public function removeIntl(AgendaIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getAgenda() === $this) {
                $intl->setAgenda(null);
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
