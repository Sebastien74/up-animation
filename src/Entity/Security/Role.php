<?php

declare(strict_types=1);

namespace App\Entity\Security;

use App\Entity\BaseEntity;
use App\Repository\Security\RoleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Role.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'security_role')]
#[ORM\Entity(repositoryClass: RoleRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity('name')]
class Role extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'securityrole',
    ];

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
