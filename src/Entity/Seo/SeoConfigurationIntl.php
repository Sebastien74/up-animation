<?php

declare(strict_types=1);

namespace App\Entity\Seo;

use App\Entity\BaseIntl;
use App\Repository\Seo\SeoConfigurationIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * BlockIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'seo_configuration_intls')]
#[ORM\Entity(repositoryClass: SeoConfigurationIntlRepository::class)]
class SeoConfigurationIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: SeoConfiguration::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?SeoConfiguration $seoConfiguration = null;

    public function getSeoConfiguration(): ?SeoConfiguration
    {
        return $this->seoConfiguration;
    }

    public function setSeoConfiguration(?SeoConfiguration $seoConfiguration): static
    {
        $this->seoConfiguration = $seoConfiguration;

        return $this;
    }
}
