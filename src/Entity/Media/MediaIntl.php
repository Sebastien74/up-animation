<?php

declare(strict_types=1);

namespace App\Entity\Media;

use App\Entity\BaseIntl;
use App\Repository\Media\MediaIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * MediaIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'media_intls')]
#[ORM\Entity(repositoryClass: MediaIntlRepository::class)]
class MediaIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Media::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Media $media = null;

    public function getMedia(): ?Media
    {
        return $this->media;
    }

    public function setMedia(?Media $media): static
    {
        $this->media = $media;

        return $this;
    }
}
