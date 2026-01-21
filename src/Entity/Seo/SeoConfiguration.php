<?php

declare(strict_types=1);

namespace App\Entity\Seo;

use App\Entity\BaseInterface;
use App\Entity\Core\Website;
use App\Repository\Seo\SeoConfigurationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * SeoConfiguration.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'seo_configuration')]
#[ORM\Entity(repositoryClass: SeoConfigurationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SeoConfiguration extends BaseInterface
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'seoconfiguration',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $microData = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $disableAfterDash = false;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $disabledIps = ['::1', '127.0.0.1', 'fe80::1', '194.51.155.21', '195.135.16.88', '176.135.112.19', '2a02:8440:5341:81fb:fd04:6bf3:c8c7:1edb', '88.173.106.115', '2001:861:43c3:ce70:bd5f:81d1:7710:888b', '2001:861:43c3:ce70:45e7:2aa7:ab50:c245'];

    #[ORM\OneToOne(targetEntity: Website::class, mappedBy: 'seoConfiguration')]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?Website $website = null;

    #[ORM\OneToMany(targetEntity: SeoConfigurationIntl::class, mappedBy: 'seoConfiguration', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    public function __construct()
    {
        $this->intls = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isMicroData(): ?bool
    {
        return $this->microData;
    }

    public function setMicroData(bool $microData): static
    {
        $this->microData = $microData;

        return $this;
    }

    public function isDisableAfterDash(): ?bool
    {
        return $this->disableAfterDash;
    }

    public function setDisableAfterDash(bool $disableAfterDash): static
    {
        $this->disableAfterDash = $disableAfterDash;

        return $this;
    }

    public function getDisabledIps(): ?array
    {
        return $this->disabledIps;
    }

    public function setDisabledIps(?array $disabledIps): static
    {
        $this->disabledIps = $disabledIps;

        return $this;
    }

    public function getWebsite(): ?Website
    {
        return $this->website;
    }

    public function setWebsite(?Website $website): static
    {
        // unset the owning side of the relation if necessary
        if ($website === null && $this->website !== null) {
            $this->website->setSeoConfiguration(null);
        }

        // set the owning side of the relation if necessary
        if ($website !== null && $website->getSeoConfiguration() !== $this) {
            $website->setSeoConfiguration($this);
        }

        $this->website = $website;

        return $this;
    }

    /**
     * @return Collection<int, SeoConfigurationIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(SeoConfigurationIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setSeoConfiguration($this);
        }

        return $this;
    }

    public function removeIntl(SeoConfigurationIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getSeoConfiguration() === $this) {
                $intl->setSeoConfiguration(null);
            }
        }

        return $this;
    }
}
