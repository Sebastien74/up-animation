<?php

declare(strict_types=1);

namespace App\Entity\Module\Table;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Repository\Module\Table\TableRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Table.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_table')]
#[ORM\Entity(repositoryClass: TableRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Table extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'table',
    ];

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $headBackgroundColor = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $headColor = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $striped = false;

    #[ORM\OneToMany(targetEntity: Col::class, mappedBy: 'table', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $cols;

    #[ORM\OneToMany(targetEntity: TableIntl::class, mappedBy: 'table', cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    public function __construct()
    {
        $this->cols = new ArrayCollection();
        $this->intls = new ArrayCollection();
    }

    /**
     * Table constructor.
     */

    public function getHeadBackgroundColor(): ?string
    {
        return $this->headBackgroundColor;
    }

    public function setHeadBackgroundColor(?string $headBackgroundColor): static
    {
        $this->headBackgroundColor = $headBackgroundColor;

        return $this;
    }

    public function getHeadColor(): ?string
    {
        return $this->headColor;
    }

    public function setHeadColor(?string $headColor): static
    {
        $this->headColor = $headColor;

        return $this;
    }

    public function isStriped(): ?bool
    {
        return $this->striped;
    }

    public function setStriped(bool $striped): static
    {
        $this->striped = $striped;

        return $this;
    }

    /**
     * @return Collection<int, Col>
     */
    public function getCols(): Collection
    {
        return $this->cols;
    }

    public function addCol(Col $col): static
    {
        if (!$this->cols->contains($col)) {
            $this->cols->add($col);
            $col->setTable($this);
        }

        return $this;
    }

    public function removeCol(Col $col): static
    {
        if ($this->cols->removeElement($col)) {
            // set the owning side to null (unless already changed)
            if ($col->getTable() === $this) {
                $col->setTable(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TableIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(TableIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setTable($this);
        }

        return $this;
    }

    public function removeIntl(TableIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getTable() === $this) {
                $intl->setTable(null);
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
