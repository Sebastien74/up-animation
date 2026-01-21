<?php

declare(strict_types=1);

namespace App\Entity\Information;

use App\Entity\BaseIntl;
use App\Repository\Information\InformationIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * InformationIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'information_intls')]
#[ORM\Entity(repositoryClass: InformationIntlRepository::class)]
class InformationIntl extends BaseIntl
{
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $alertType = 'marquee';

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $alertDuration = 25;

    #[ORM\ManyToOne(targetEntity: Information::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Information $information = null;

    public function getAlertType(): ?string
    {
        return $this->alertType;
    }

    public function setAlertType(?string $alertType): static
    {
        $this->alertType = $alertType;

        return $this;
    }

    public function getAlertDuration(): ?int
    {
        return $this->alertDuration;
    }

    public function setAlertDuration(?int $alertDuration): static
    {
        $this->alertDuration = $alertDuration;

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
}
