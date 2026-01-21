<?php

declare(strict_types=1);

namespace App\Entity\Module\Recruitment;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Repository\Module\Recruitment\ContractRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Contract.
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
#[ORM\Table(name: 'module_recruitment_contract')]
#[ORM\Entity(repositoryClass: ContractRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Contract extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'recruitmentcontract',
        'resize' => true,
    ];

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    #[ORM\OneToMany(mappedBy: 'contract', targetEntity: ContractIntl::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    /**
     * Contract constructor.
     */
    public function __construct()
    {
        $this->intls = new ArrayCollection();
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

    /**
     * @return Collection<int, ContractIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(ContractIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setContract($this);
        }

        return $this;
    }

    public function removeIntl(ContractIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getContract() === $this) {
                $intl->setContract(null);
            }
        }

        return $this;
    }
}
