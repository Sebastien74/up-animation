<?php

declare(strict_types=1);

namespace App\Entity\Layout;

use App\Entity\BaseMediaRelation;
use App\Repository\Layout\PageMediaRelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * PageMediaRelation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'layout_page_media_relations')]
#[ORM\Entity(repositoryClass: PageMediaRelationRepository::class)]
class PageMediaRelation extends BaseMediaRelation
{
    #[ORM\ManyToOne(targetEntity: Page::class, cascade: ['persist'], inversedBy: 'mediaRelations')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Page $page = null;

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function setPage(?Page $page): static
    {
        $this->page = $page;

        return $this;
    }
}
