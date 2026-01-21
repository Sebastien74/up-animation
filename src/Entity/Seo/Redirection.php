<?php

declare(strict_types=1);

namespace App\Entity\Seo;

use App\Entity\BaseInterface;
use App\Entity\Core\Website;
use App\Repository\Seo\RedirectionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Redirection.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'seo_redirection')]
#[ORM\Entity(repositoryClass: RedirectionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Redirection extends BaseInterface
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'redirection',
        'disabled_flash_bag' => true,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 10)]
    private ?string $locale = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $old = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $new = null;

    #[ORM\ManyToOne(targetEntity: Website::class, inversedBy: 'redirections')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getOld(): ?string
    {
        return $this->old;
    }

    public function setOld(string $old): static
    {
        $this->old = $old;

        return $this;
    }

    public function getNew(): ?string
    {
        return $this->new;
    }

    public function setNew(string $new): static
    {
        $this->new = $new;

        return $this;
    }

    public function getWebsite(): ?Website
    {
        return $this->website;
    }

    public function setWebsite(?Website $website): static
    {
        $this->website = $website;

        return $this;
    }
}
