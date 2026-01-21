<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Repository\Api\InstagramIntlRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * InstagramIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'api_instagram_intl')]
#[ORM\Entity(repositoryClass: InstagramIntlRepository::class)]
class InstagramIntl
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 10)]
    private ?string $locale = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $widget = null;

    #[ORM\ManyToOne(targetEntity: Instagram::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Instagram $instagram = null;

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

    public function getWidget(): ?string
    {
        return $this->widget;
    }

    public function setWidget(?string $widget): static
    {
        $this->widget = $widget;

        return $this;
    }

    public function getInstagram(): ?Instagram
    {
        return $this->instagram;
    }

    public function setInstagram(?Instagram $instagram): static
    {
        $this->instagram = $instagram;

        return $this;
    }
}
