<?php

declare(strict_types=1);

namespace App\Entity\Module\Portfolio;

use App\Entity\BaseIntl;
use App\Repository\Module\Portfolio\CardIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * CardIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_portfolio_card_intls')]
#[ORM\Entity(repositoryClass: CardIntlRepository::class)]
class CardIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Card::class, cascade: ['persist'], inversedBy: 'intls')]
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
