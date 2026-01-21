<?php

declare(strict_types=1);

namespace App\Entity\Module\Gallery;

use App\Entity\BaseMediaRelation;
use App\Repository\Module\Gallery\GalleryMediaRelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * GalleryMediaRelation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_gallery_media_relations')]
#[ORM\Entity(repositoryClass: GalleryMediaRelationRepository::class)]
class GalleryMediaRelation extends BaseMediaRelation
{
    #[ORM\ManyToOne(targetEntity: Gallery::class, cascade: ['persist'], inversedBy: 'mediaRelations')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Gallery $gallery = null;

    public function getGallery(): ?Gallery
    {
        return $this->gallery;
    }

    public function setGallery(?Gallery $gallery): static
    {
        $this->gallery = $gallery;

        return $this;
    }
}
