<?php

declare(strict_types=1);

namespace App\Entity\Module\Recruitment;

use App\Entity\BaseIntl;
use App\Repository\Module\Newscast\CategoryIntlRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * JobIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_recruitment_job_intls')]
#[ORM\Entity(repositoryClass: CategoryIntlRepository::class)]
class JobIntl extends BaseIntl
{
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $skills = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $profil = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $profile = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $duration = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $remuneration = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $company = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $diploma = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $drivingLicence = null;

    #[ORM\ManyToOne(targetEntity: Job::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Job $job = null;

    public function getSkills(): ?string
    {
        return $this->skills;
    }

    public function setSkills(?string $skills): static
    {
        $this->skills = $skills;

        return $this;
    }

    public function getProfil(): ?string
    {
        return $this->profil;
    }

    public function setProfil(?string $profil): static
    {
        $this->profil = $profil;

        return $this;
    }

    public function getProfile(): ?string
    {
        return $this->profile;
    }

    public function setProfile(?string $profile): static
    {
        $this->profile = $profile;

        return $this;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(?string $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getRemuneration(): ?string
    {
        return $this->remuneration;
    }

    public function setRemuneration(?string $remuneration): static
    {
        $this->remuneration = $remuneration;

        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getDiploma(): ?string
    {
        return $this->diploma;
    }

    public function setDiploma(?string $diploma): static
    {
        $this->diploma = $diploma;

        return $this;
    }

    public function getDrivingLicence(): ?string
    {
        return $this->drivingLicence;
    }

    public function setDrivingLicence(?string $drivingLicence): static
    {
        $this->drivingLicence = $drivingLicence;

        return $this;
    }

    public function getJob(): ?Job
    {
        return $this->job;
    }

    public function setJob(?Job $job): static
    {
        $this->job = $job;

        return $this;
    }
}
