<?php

declare(strict_types=1);

namespace App\Entity\Gdpr;

use App\Entity\BaseMediaRelation;
use App\Repository\Gdpr\GroupMediaRelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * GroupMediaRelation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'gdpr_group_media_relations')]
#[ORM\Entity(repositoryClass: GroupMediaRelationRepository::class)]
class GroupMediaRelation extends BaseMediaRelation
{
    #[ORM\ManyToOne(targetEntity: Group::class, cascade: ['persist'], inversedBy: 'mediaRelations')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Group $group = null;

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function setGroup(?Group $group): static
    {
        $this->group = $group;

        return $this;
    }
}
