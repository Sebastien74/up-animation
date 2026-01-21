<?php

declare(strict_types=1);

namespace App\Entity\Module\Agenda;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Repository\Module\Agenda\InformationRepository;
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
#[ORM\Table(name: 'module_agenda_information')]
#[ORM\Entity(repositoryClass: InformationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Information extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'agendainformation',
    ];

    #[ORM\OneToMany(targetEntity: InformationIntl::class, mappedBy: 'information', cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    /**
     * Information constructor.
     */
    public function __construct()
    {
        $this->intls = new ArrayCollection();
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
