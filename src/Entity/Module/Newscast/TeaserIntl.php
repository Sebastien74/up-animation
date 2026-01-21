<?php

declare(strict_types=1);

namespace App\Entity\Module\Newscast;

use App\Entity\BaseIntl;
use App\Repository\Module\Newscast\TeaserIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * TeaserIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_newscast_teaser_intls')]
#[ORM\Entity(repositoryClass: TeaserIntlRepository::class)]
class TeaserIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Teaser::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Teaser $teaser = null;

    public function getTeaser(): ?Teaser
    {
        return $this->teaser;
    }

    public function setTeaser(?Teaser $teaser): static
    {
        $this->teaser = $teaser;

        return $this;
    }
}
