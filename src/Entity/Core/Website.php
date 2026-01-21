<?php

declare(strict_types=1);

namespace App\Entity\Core;

use App\Entity\Api\Api;
use App\Entity\BaseEntity;
use App\Entity\Information\Information;
use App\Entity\Seo\Redirection;
use App\Entity\Seo\SeoConfiguration;
use App\Repository\Core\WebsiteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * WebsiteModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'core_website')]
#[ORM\Entity(repositoryClass: WebsiteRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Website extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'website',
    ];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $active = false;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $uploadDirname = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $cacheClearDate = null;

    #[ORM\OneToOne(targetEntity: Security::class, inversedBy: 'website', cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'cascade')]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?Security $security = null;

    #[ORM\OneToOne(targetEntity: Information::class, inversedBy: 'website', cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'cascade')]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?Information $information = null;

    #[ORM\OneToOne(targetEntity: SeoConfiguration::class, inversedBy: 'website', cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'cascade')]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?SeoConfiguration $seoConfiguration = null;

    #[ORM\OneToOne(targetEntity: Configuration::class, inversedBy: 'website', cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'cascade')]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?Configuration $configuration = null;

    #[ORM\OneToOne(targetEntity: Api::class, inversedBy: 'website', cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY')]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?Api $api = null;

    #[ORM\OneToMany(targetEntity: Redirection::class, mappedBy: 'website', cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    #[ORM\OrderBy(['id' => 'DESC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $redirections;

    /**
     * WebsiteModel constructor.
     */
    public function __construct()
    {
        $this->redirections = new ArrayCollection();
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getUploadDirname(): ?string
    {
        return $this->uploadDirname;
    }

    public function setUploadDirname(string $uploadDirname): static
    {
        $this->uploadDirname = $uploadDirname;

        return $this;
    }

    public function getCacheClearDate(): ?\DateTimeInterface
    {
        return $this->cacheClearDate;
    }

    public function setCacheClearDate(?\DateTimeInterface $cacheClearDate): static
    {
        $this->cacheClearDate = $cacheClearDate;

        return $this;
    }

    public function getSecurity(): ?Security
    {
        return $this->security;
    }

    public function setSecurity(?Security $security): static
    {
        $this->security = $security;

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

    public function getSeoConfiguration(): ?SeoConfiguration
    {
        return $this->seoConfiguration;
    }

    public function setSeoConfiguration(?SeoConfiguration $seoConfiguration): static
    {
        $this->seoConfiguration = $seoConfiguration;

        return $this;
    }

    public function getConfiguration(): ?Configuration
    {
        return $this->configuration;
    }

    public function setConfiguration(?Configuration $configuration): static
    {
        $this->configuration = $configuration;

        return $this;
    }

    public function getApi(): ?Api
    {
        return $this->api;
    }

    public function setApi(?Api $api): static
    {
        $this->api = $api;

        return $this;
    }

    /**
     * @return Collection<int, Redirection>
     */
    public function getRedirections(): Collection
    {
        return $this->redirections;
    }

    public function addRedirection(Redirection $redirection): static
    {
        if (!$this->redirections->contains($redirection)) {
            $this->redirections->add($redirection);
            $redirection->setWebsite($this);
        }

        return $this;
    }

    public function removeRedirection(Redirection $redirection): static
    {
        if ($this->redirections->removeElement($redirection)) {
            // set the owning side to null (unless already changed)
            if ($redirection->getWebsite() === $this) {
                $redirection->setWebsite(null);
            }
        }

        return $this;
    }
}
