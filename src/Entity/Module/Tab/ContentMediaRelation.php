<?php

declare(strict_types=1);

namespace App\Entity\Module\Tab;

use App\Entity\BaseMediaRelation;
use App\Repository\Module\Tab\ContentMediaRelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * ContentMediaRelation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_tab_content_media_relations')]
#[ORM\Entity(repositoryClass: ContentMediaRelationRepository::class)]
class ContentMediaRelation extends BaseMediaRelation
{
    #[ORM\ManyToOne(targetEntity: Content::class, cascade: ['persist'], inversedBy: 'mediaRelations')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Content $content = null;

    public function getContent(): ?Content
    {
        return $this->content;
    }

    public function setContent(?Content $content): static
    {
        $this->content = $content;

        return $this;
    }
}
