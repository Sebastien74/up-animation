<?php

declare(strict_types=1);

namespace App\Entity\Layout;

use App\Entity\BaseMediaRelation;
use App\Repository\Layout\BlockMediaRelationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * BlockMediaRelation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'layout_block_media_relations')]
#[ORM\Entity(repositoryClass: BlockMediaRelationRepository::class)]
class BlockMediaRelation extends BaseMediaRelation
{
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $pictogramMaxWidth = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $pictogramMaxHeight = null;

    #[ORM\ManyToOne(targetEntity: Block::class, cascade: ['persist'], inversedBy: 'mediaRelations')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Block $block = null;

    public function getPictogramMaxWidth(): ?int
    {
        return $this->pictogramMaxWidth;
    }

    public function setPictogramMaxWidth(?int $pictogramMaxWidth): static
    {
        $this->pictogramMaxWidth = $pictogramMaxWidth;

        return $this;
    }

    public function getPictogramMaxHeight(): ?int
    {
        return $this->pictogramMaxHeight;
    }

    public function setPictogramMaxHeight(?int $pictogramMaxHeight): static
    {
        $this->pictogramMaxHeight = $pictogramMaxHeight;

        return $this;
    }

    public function getBlock(): ?Block
    {
        return $this->block;
    }

    public function setBlock(?Block $block): static
    {
        $this->block = $block;

        return $this;
    }
}
