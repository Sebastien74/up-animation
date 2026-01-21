<?php

declare(strict_types=1);

namespace App\Entity\Media;

use App\Entity\BaseInterface;
use App\Repository\Media\ThumbRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Thumb.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'media_thumb')]
#[ORM\Entity(repositoryClass: ThumbRepository::class)]
class Thumb extends BaseInterface
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'thumb',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $width = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $height = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $dataX = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $dataY = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $rotate = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $scale = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $scaleX = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $scaleY = null;

    #[ORM\ManyToOne(targetEntity: Media::class, cascade: ['persist'], inversedBy: 'thumbs')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Media $media = null;

    #[ORM\ManyToOne(targetEntity: ThumbConfiguration::class, cascade: ['persist'], inversedBy: 'thumbs')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?ThumbConfiguration $configuration = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getDataX(): ?int
    {
        return $this->dataX;
    }

    public function setDataX(?int $dataX): static
    {
        $this->dataX = $dataX;

        return $this;
    }

    public function getDataY(): ?int
    {
        return $this->dataY;
    }

    public function setDataY(?int $dataY): static
    {
        $this->dataY = $dataY;

        return $this;
    }

    public function getRotate(): ?int
    {
        return $this->rotate;
    }

    public function setRotate(?int $rotate): static
    {
        $this->rotate = $rotate;

        return $this;
    }

    public function getScale(): ?int
    {
        return $this->scale;
    }

    public function setScale(?int $scale): static
    {
        $this->scale = $scale;

        return $this;
    }

    public function getScaleX(): ?int
    {
        return $this->scaleX;
    }

    public function setScaleX(?int $scaleX): static
    {
        $this->scaleX = $scaleX;

        return $this;
    }

    public function getScaleY(): ?int
    {
        return $this->scaleY;
    }

    public function setScaleY(?int $scaleY): static
    {
        $this->scaleY = $scaleY;

        return $this;
    }

    public function getMedia(): ?Media
    {
        return $this->media;
    }

    public function setMedia(?Media $media): static
    {
        $this->media = $media;

        return $this;
    }

    public function getConfiguration(): ?ThumbConfiguration
    {
        return $this->configuration;
    }

    public function setConfiguration(?ThumbConfiguration $configuration): static
    {
        $this->configuration = $configuration;

        return $this;
    }
}
