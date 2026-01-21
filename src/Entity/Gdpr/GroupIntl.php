<?php

declare(strict_types=1);

namespace App\Entity\Gdpr;

use App\Entity\BaseIntl;
use App\Repository\Gdpr\GroupIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * GroupIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'gdpr_group_intls')]
#[ORM\Entity(repositoryClass: GroupIntlRepository::class)]
class GroupIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Group::class, cascade: ['persist'], inversedBy: 'intls')]
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
