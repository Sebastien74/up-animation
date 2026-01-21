<?php

declare(strict_types=1);

namespace App\Entity\Module\Menu;

use App\Entity\BaseMediaRelation;
use App\Repository\Module\Menu\LinkMediaRelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * ConfigurationMediaRelation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_menu_link_media_relation')]
#[ORM\Entity(repositoryClass: LinkMediaRelationRepository::class)]
class LinkMediaRelation extends BaseMediaRelation
{
    #[ORM\OneToOne(targetEntity: Link::class, mappedBy: 'mediaRelation', cascade: ['persist', 'remove'])]
    private ?Link $link = null;

    public function getLink(): ?Link
    {
        return $this->link;
    }

    public function setLink(?Link $link): static
    {
        // unset the owning side of the relation if necessary
        if (null === $link && null !== $this->link) {
            $this->link->setMediaRelation(null);
        }

        // set the owning side of the relation if necessary
        if (null !== $link && $link->getMediaRelation() !== $this) {
            $link->setMediaRelation($this);
        }

        $this->link = $link;

        return $this;
    }
}
