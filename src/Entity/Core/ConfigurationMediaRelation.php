<?php

declare(strict_types=1);

namespace App\Entity\Core;

use App\Entity\BaseMediaRelation;
use App\Repository\Core\ConfigurationMediaRelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * ConfigurationMediaRelation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'core_configuration_media_relations')]
#[ORM\Entity(repositoryClass: ConfigurationMediaRelationRepository::class)]
class ConfigurationMediaRelation extends BaseMediaRelation
{
    #[ORM\ManyToOne(targetEntity: Configuration::class, cascade: ['persist'], inversedBy: 'mediaRelations')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Configuration $configuration = null;

    public function getConfiguration(): ?Configuration
    {
        return $this->configuration;
    }

    public function setConfiguration(?Configuration $configuration): static
    {
        $this->configuration = $configuration;

        return $this;
    }
}
