<?php

declare(strict_types=1);

namespace App\Entity\Module\Newscast;

use App\Entity\BaseMediaRelation;
use App\Repository\Module\Newscast\NewscastMediaRelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * NewscastMediaRelation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_newscast_media_relations')]
#[ORM\Entity(repositoryClass: NewscastMediaRelationRepository::class)]
class NewscastMediaRelation extends BaseMediaRelation
{
    #[ORM\ManyToOne(targetEntity: Newscast::class, cascade: ['persist'], inversedBy: 'mediaRelations')]
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
