<?php

declare(strict_types=1);

namespace App\Entity\Gdpr;

use App\Entity\BaseEntity;
use App\Entity\Core\Configuration;
use App\Repository\Gdpr\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Category.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'gdpr_category')]
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Category extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'configuration';
    protected static array $interface = [
        'name' => 'gdprcategory',
        'buttons' => [
            'admin_gdprgroup_index',
        ],
    ];
    protected static array $labels = [
        'admin_gdprgroup_index' => 'Groupes',
    ];

    #[ORM\ManyToOne(targetEntity: Configuration::class, inversedBy: 'gdprcategories')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private ?Configuration $configuration = null;

    #[ORM\OneToMany(targetEntity: Group::class, mappedBy: 'gdprcategory')]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $gdprgroups;

    /**
     * Category constructor.
     */
    public function __construct()
    {
        $this->gdprgroups = new ArrayCollection();
    }

    public function getConfiguration(): ?Configuration
    {
        return $this->configuration;
    }

    public function setConfiguration(?Configuration $configuration): static
    {
        $this->configuration = $configuration;

        return $this;
    }

    /**
     * @return Collection<int, Group>
     */
    public function getGdprgroups(): Collection
    {
        return $this->gdprgroups;
    }

    public function addGdprgroup(Group $gdprgroup): static
    {
        if (!$this->gdprgroups->contains($gdprgroup)) {
            $this->gdprgroups->add($gdprgroup);
            $gdprgroup->setGdprcategory($this);
        }

        return $this;
    }

    public function removeGdprgroup(Group $gdprgroup): static
    {
        if ($this->gdprgroups->removeElement($gdprgroup)) {
            // set the owning side to null (unless already changed)
            if ($gdprgroup->getGdprcategory() === $this) {
                $gdprgroup->setGdprcategory(null);
            }
        }

        return $this;
    }
}
