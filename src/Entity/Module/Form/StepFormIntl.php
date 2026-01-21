<?php

declare(strict_types=1);

namespace App\Entity\Module\Form;

use App\Entity\BaseIntl;
use App\Repository\Module\Form\StepFormIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * StepFormIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_form_step_intls')]
#[ORM\Entity(repositoryClass: StepFormIntlRepository::class)]
class StepFormIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: StepForm::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?StepForm $stepForm = null;

    public function getStepForm(): ?StepForm
    {
        return $this->stepForm;
    }

    public function setStepForm(?StepForm $stepForm): static
    {
        $this->stepForm = $stepForm;

        return $this;
    }
}
