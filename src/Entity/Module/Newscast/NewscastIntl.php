<?php

declare(strict_types=1);

namespace App\Entity\Module\Newscast;

use App\Entity\BaseIntl;
use App\Repository\Module\Newscast\NewscastIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * NewscastIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_newscast_intls')]
#[ORM\Entity(repositoryClass: NewscastIntlRepository::class)]
class NewscastIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Newscast::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Newscast $newscast = null;

    public function getNewscast(): ?Newscast
    {
        return $this->newscast;
    }

    public function setNewscast(?Newscast $newscast): static
    {
        $this->newscast = $newscast;

        return $this;
    }
}
