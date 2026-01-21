<?php

declare(strict_types=1);

namespace App\Entity\Security;

use App\Entity\BaseEntity;
use App\Repository\Security\LogoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Logo.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'security_company_logo')]
#[ORM\Entity(repositoryClass: LogoRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Logo extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'customer';
    protected static array $interface = [
        'name' => 'logo',
    ];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $filename = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $dirname = null;

    #[ORM\OneToOne(targetEntity: Company::class, inversedBy: 'logo')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getDirname(): ?string
    {
        return $this->dirname;
    }

    public function setDirname(?string $dirname): static
    {
        $this->dirname = $dirname;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): static
    {
        $this->company = $company;

        return $this;
    }
}
