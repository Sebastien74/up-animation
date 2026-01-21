<?php

declare(strict_types=1);

namespace App\Entity\Module\Agenda;

use App\Entity\BaseIntl;
use App\Repository\Module\Agenda\InformationIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * InformationIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_agenda_information_intls')]
#[ORM\Entity(repositoryClass: InformationIntlRepository::class)]
class InformationIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Information::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Information $information = null;

    public function getInformation(): ?Information
    {
        return $this->information;
    }

    public function setInformation(?Information $information): static
    {
        $this->information = $information;

        return $this;
    }
}
