<?php

declare(strict_types=1);

namespace App\Entity\Media;

use App\Entity\BaseInterface;
use App\Entity\Layout\BlockType;
use App\Repository\Media\ThumbActionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * ThumbAction.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'media_thumb_action')]
#[ORM\Entity(repositoryClass: ThumbActionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ThumbAction extends BaseInterface
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'thumbaction',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $adminName = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $namespace = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $action = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $actionFilter = null;

    #[ORM\ManyToOne(targetEntity: BlockType::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?BlockType $blockType = null;

    #[ORM\ManyToOne(targetEntity: ThumbConfiguration::class, cascade: ['persist'], inversedBy: 'actions')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?ThumbConfiguration $configuration = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdminName(): ?string
    {
        return $this->adminName;
    }

    public function setAdminName(string $adminName): static
    {
        $this->adminName = $adminName;

        return $this;
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    public function setNamespace(string $namespace): static
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getActionFilter(): ?string
    {
        return $this->actionFilter;
    }

    public function setActionFilter(?string $actionFilter): static
    {
        $this->actionFilter = $actionFilter;

        return $this;
    }

    public function getBlockType(): ?BlockType
    {
        return $this->blockType;
    }

    public function setBlockType(?BlockType $blockType): static
    {
        $this->blockType = $blockType;

        return $this;
    }

    public function getConfiguration(): ?ThumbConfiguration
    {
        return $this->configuration;
    }

    public function setConfiguration(?ThumbConfiguration $configuration): static
    {
        $this->configuration = $configuration;

        return $this;
    }
}
