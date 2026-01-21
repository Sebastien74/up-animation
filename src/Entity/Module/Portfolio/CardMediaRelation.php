<?php

declare(strict_types=1);

namespace App\Entity\Module\Portfolio;

use App\Entity\BaseMediaRelation;
use App\Repository\Module\Portfolio\CardMediaRelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * CardMediaRelation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_portfolio_card_media_relations')]
#[ORM\Entity(repositoryClass: CardMediaRelationRepository::class)]
class CardMediaRelation extends BaseMediaRelation
{
    #[ORM\ManyToOne(targetEntity: Card::class, cascade: ['persist'], inversedBy: 'mediaRelations')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Card $card = null;

    public function getCard(): ?Card
    {
        return $this->card;
    }

    public function setCard(?Card $card): static
    {
        $this->card = $card;

        return $this;
    }
}
