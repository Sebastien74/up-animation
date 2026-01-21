<?php

declare(strict_types=1);

namespace App\Entity\Layout;

use App\Repository\Layout\ActionIntlRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * ActionIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'layout_action_intl')]
#[ORM\Entity(repositoryClass: ActionIntlRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ActionIntl
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 10)]
    private ?string $locale = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $actionFilter = null;

    #[ORM\ManyToOne(targetEntity: Block::class, cascade: ['persist'], inversedBy: 'actionIntls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Block $block = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getActionFilter(): ?int
    {
        return $this->actionFilter;
    }

    public function setActionFilter(?int $actionFilter): static
    {
        $this->actionFilter = $actionFilter;

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
