<?php

declare(strict_types=1);

namespace App\Entity\Module\Menu;

use App\Entity\BaseIntl;
use App\Repository\Module\Menu\LinkIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * LinkIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_menu_link_intl')]
#[ORM\Entity(repositoryClass: LinkIntlRepository::class)]
#[ORM\HasLifecycleCallbacks]
class LinkIntl extends BaseIntl
{
    #[ORM\OneToOne(targetEntity: Link::class, mappedBy: 'intl', cascade: ['persist', 'remove'])]
    private ?Link $link = null;

    public function getLink(): ?Link
    {
        return $this->link;
    }

    public function setLink(?Link $link): static
    {
        // unset the owning side of the relation if necessary
        if (null === $link && null !== $this->link) {
            $this->link->setIntl(null);
        }

        // set the owning side of the relation if necessary
        if (null !== $link && $link->getIntl() !== $this) {
            $link->setIntl($this);
        }

        $this->link = $link;

        return $this;
    }
}
