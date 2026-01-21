<?php

declare(strict_types=1);

namespace App\Entity\Layout;

use App\Entity\BaseIntl;
use App\Repository\Layout\BlockIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * BlockIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'layout_block_intls')]
#[ORM\Entity(repositoryClass: BlockIntlRepository::class)]
class BlockIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Block::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Block $block = null;

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
