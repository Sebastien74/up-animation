<?php

declare(strict_types=1);

namespace App\Entity\Module\Slider;

use App\Entity\BaseMediaRelation;
use App\Repository\Module\Slider\SliderMediaRelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * SliderMediaRelation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_slider_media_relations')]
#[ORM\Entity(repositoryClass: SliderMediaRelationRepository::class)]
class SliderMediaRelation extends BaseMediaRelation
{
    #[ORM\ManyToOne(targetEntity: Slider::class, cascade: ['persist'], inversedBy: 'mediaRelations')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Slider $slider = null;

    public function getSlider(): ?Slider
    {
        return $this->slider;
    }

    public function setSlider(?Slider $slider): static
    {
        $this->slider = $slider;

        return $this;
    }
}
