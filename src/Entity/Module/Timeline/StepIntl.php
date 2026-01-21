<?php

declare(strict_types=1);

namespace App\Entity\Module\Timeline;

use App\Entity\BaseIntl;
use App\Repository\Module\Timeline\StepIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * StepIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_timeline_step_intls')]
#[ORM\Entity(repositoryClass: StepIntlRepository::class)]
class StepIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Step::class, cascade: ['persist'], inversedBy: 'intls')]
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
