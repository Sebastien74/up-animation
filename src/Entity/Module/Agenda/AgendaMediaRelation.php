<?php

declare(strict_types=1);

namespace App\Entity\Module\Agenda;

use App\Entity\BaseMediaRelation;
use App\Repository\Module\Agenda\AgendaMediaRelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * AgendaMediaRelation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_agenda_media_relations')]
#[ORM\Entity(repositoryClass: AgendaMediaRelationRepository::class)]
class AgendaMediaRelation extends BaseMediaRelation
{
    #[ORM\ManyToOne(targetEntity: Agenda::class, cascade: ['persist'], inversedBy: 'mediaRelations')]
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
