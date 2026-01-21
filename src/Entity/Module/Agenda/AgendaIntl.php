<?php

declare(strict_types=1);

namespace App\Entity\Module\Agenda;

use App\Entity\BaseIntl;
use App\Repository\Module\Agenda\AgendaIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * AgendaIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_agenda_intls')]
#[ORM\Entity(repositoryClass: AgendaIntlRepository::class)]
class AgendaIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Agenda::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Agenda $agenda = null;

    public function getAgenda(): ?Agenda
    {
        return $this->agenda;
    }

    public function setAgenda(?Agenda $agenda): static
    {
        $this->agenda = $agenda;

        return $this;
    }
}
