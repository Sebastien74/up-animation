<?php

declare(strict_types=1);

namespace App\Entity\Layout;

use App\Entity\BaseIntl;
use App\Repository\Layout\BlockIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * BlockTypeIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'layout_block_type_intls')]
#[ORM\Entity(repositoryClass: BlockIntlRepository::class)]
class BlockTypeIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: BlockType::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?BlockType $blockType = null;

    public function getBlockType(): ?BlockType
    {
        return $this->blockType;
    }

    public function setBlockType(?BlockType $blockType): static
    {
        $this->blockType = $blockType;

        return $this;
    }
}
