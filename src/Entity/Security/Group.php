<?php

declare(strict_types=1);

namespace App\Entity\Security;

use App\Entity\BaseEntity;
use App\Repository\Security\GroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * Group.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'security_group')]
#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'roles',
        joinColumns: [new ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id', onDelete: 'CASCADE')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'role_id', referencedColumnName: 'id')],
        joinTable: new ORM\JoinTable(name: 'security_groups_roles')
    ),
])]
class Group extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'securitygroup',
    ];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $loginRedirection = null;

    #[ORM\ManyToMany(targetEntity: Role::class, fetch: 'EXTRA_LAZY')]
    private ArrayCollection|PersistentCollection $roles;

    /**
     * Group constructor.
     */
    public function __construct()
    {
        $this->roles = new ArrayCollection();
    }

    public function getLoginRedirection(): ?string
    {
        return $this->loginRedirection;
    }

    public function setLoginRedirection(?string $loginRedirection): static
    {
        $this->loginRedirection = $loginRedirection;

        return $this;
    }

    /**
     * @return Collection<int, Role>
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function addRole(Role $role): static
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
        }

        return $this;
    }

    public function removeRole(Role $role): static
    {
        $this->roles->removeElement($role);

        return $this;
    }
}
