<?php

declare(strict_types=1);

namespace App\Entity\Module\Form;

use App\Entity\BaseIntl;
use App\Repository\Module\Form\FormIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * FormIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_form_intls')]
#[ORM\Entity(repositoryClass: FormIntlRepository::class)]
class FormIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Form::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Form $form = null;

    public function getForm(): ?Form
    {
        return $this->form;
    }

    public function setForm(?Form $form): static
    {
        $this->form = $form;

        return $this;
    }
}
