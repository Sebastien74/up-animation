<?php

declare(strict_types=1);

namespace App\Entity\Core;

use App\Entity\BaseEntity;
use App\Repository\Core\EntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entity.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'core_entity')]
#[ORM\Entity(repositoryClass: EntityRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Entity extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'entity',
    ];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\NotBlank]
    private ?string $className = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $entityId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\NotBlank]
    private ?string $orderBy = 'position';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\NotBlank]
    private ?string $orderSort = 'ASC';

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Assert\NotBlank]
    private ?array $columns = ['adminName'];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $searchFields = ['adminName'];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $searchFilters = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $showView = ['id', 'adminName', 'createdAt', 'updatedAt', 'createdBy', 'updatedBy', 'position'];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $exports = [];

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\NotBlank]
    private ?int $adminLimit = 15;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $uniqueLocale = false;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $saveArea = 'bottom';

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $mediaMulti = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $card = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $inFieldConfiguration = false;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    public function getClassName(): ?string
    {
        return $this->className;
    }

    public function setClassName(?string $className): static
    {
        $this->className = $className;

        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId): static
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getOrderBy(): ?string
    {
        return $this->orderBy;
    }

    public function setOrderBy(?string $orderBy): static
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    public function getOrderSort(): ?string
    {
        return $this->orderSort;
    }

    public function setOrderSort(?string $orderSort): static
    {
        $this->orderSort = $orderSort;

        return $this;
    }

    public function getColumns(): ?array
    {
        return $this->columns;
    }

    public function setColumns(?array $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    public function getSearchFields(): ?array
    {
        return $this->searchFields;
    }

    public function setSearchFields(?array $searchFields): static
    {
        $this->searchFields = $searchFields;

        return $this;
    }

    public function getSearchFilters(): ?array
    {
        return $this->searchFilters;
    }

    public function setSearchFilters(?array $searchFilters): static
    {
        $this->searchFilters = $searchFilters;

        return $this;
    }

    public function getShowView(): ?array
    {
        return $this->showView;
    }

    public function setShowView(?array $showView): static
    {
        $this->showView = $showView;

        return $this;
    }

    public function getExports(): ?array
    {
        return $this->exports;
    }

    public function setExports(?array $exports): static
    {
        $this->exports = $exports;

        return $this;
    }

    public function getAdminLimit(): ?int
    {
        return $this->adminLimit;
    }

    public function setAdminLimit(?int $adminLimit): static
    {
        $this->adminLimit = $adminLimit;

        return $this;
    }

    public function isUniqueLocale(): ?bool
    {
        return $this->uniqueLocale;
    }

    public function setUniqueLocale(bool $uniqueLocale): static
    {
        $this->uniqueLocale = $uniqueLocale;

        return $this;
    }

    public function getSaveArea(): ?string
    {
        return $this->saveArea;
    }

    public function setSaveArea(string $saveArea): static
    {
        $this->saveArea = $saveArea;

        return $this;
    }

    public function isMediaMulti(): ?bool
    {
        return $this->mediaMulti;
    }

    public function setMediaMulti(bool $mediaMulti): static
    {
        $this->mediaMulti = $mediaMulti;

        return $this;
    }

    public function isCard(): ?bool
    {
        return $this->card;
    }

    public function setCard(bool $card): static
    {
        $this->card = $card;

        return $this;
    }

    public function isInFieldConfiguration(): ?bool
    {
        return $this->inFieldConfiguration;
    }

    public function setInFieldConfiguration(bool $inFieldConfiguration): static
    {
        $this->inFieldConfiguration = $inFieldConfiguration;

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
