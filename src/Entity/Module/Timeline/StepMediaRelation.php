<?php

declare(strict_types=1);

namespace App\Entity\Module\Timeline;

use App\Entity\BaseMediaRelation;
use App\Repository\Module\Timeline\StepMediaRelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * StepMediaRelation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_timeline_step_media_relations')]
#[ORM\Entity(repositoryClass: StepMediaRelationRepository::class)]
class StepMediaRelation extends BaseMediaRelation
{
    #[ORM\ManyToOne(targetEntity: Step::class, cascade: ['persist'], inversedBy: 'mediaRelations')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Step $step = null;

    public function getStep(): ?Step
    {
        return $this->step;
    }

    public function setStep(?Step $step): static
    {
        $this->step = $step;

        return $this;
    }
}
