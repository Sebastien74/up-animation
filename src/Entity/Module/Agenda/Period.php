<?php

declare(strict_types=1);

namespace App\Entity\Module\Agenda;

use App\Entity\BaseEntity;
use App\Repository\Module\Agenda\PeriodRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Period.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_agenda_period')]
#[ORM\Entity(repositoryClass: PeriodRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Period extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'agenda';
    protected static array $interface = [
        'name' => 'agendaperiod',
    ];

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $publicationStart = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $publicationEnd = null;

    #[ORM\ManyToOne(targetEntity: Information::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Information $information = null;

    #[ORM\ManyToOne(targetEntity: Agenda::class, inversedBy: 'periods')]
    private ?Agenda $agenda = null;

    public function getPublicationStart(): ?\DateTimeInterface
    {
        return $this->publicationStart;
    }

    public function setPublicationStart(?\DateTimeInterface $publicationStart): static
    {
        $this->publicationStart = $publicationStart;

        return $this;
    }

    public function getPublicationEnd(): ?\DateTimeInterface
    {
        return $this->publicationEnd;
    }

    public function setPublicationEnd(?\DateTimeInterface $publicationEnd): static
    {
        $this->publicationEnd = $publicationEnd;

        return $this;
    }

    public function getInformation(): ?Information
    {
        return $this->information;
    }

    public function setInformation(?Information $information): static
    {
        $this->information = $information;

        return $this;
    }

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
