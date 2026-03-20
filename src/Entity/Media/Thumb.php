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

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $dataX = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $dataY = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $rotate = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $scale = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $scaleX = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $scaleY = null;

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

    public function getDataX(): ?float
    {
        return $this->dataX;
    }

    public function setDataX(?float $dataX): static
    {
        $this->dataX = $dataX;

        return $this;
    }

    public function getDataY(): ?float
    {
        return $this->dataY;
    }

    public function setDataY(?float $dataY): static
    {
        $this->dataY = $dataY;

        return $this;
    }

    public function getRotate(): ?float
    {
        return $this->rotate;
    }

    public function setRotate(?float $rotate): static
    {
        $this->rotate = $rotate;

        return $this;
    }

    public function getScale(): ?float
    {
        return $this->scale;
    }

    public function setScale(?float $scale): static
    {
        $this->scale = $scale;

        return $this;
    }

    public function getScaleX(): ?float
    {
        return $this->scaleX;
    }

    public function setScaleX(?float $scaleX): static
    {
        $this->scaleX = $scaleX;

        return $this;
    }

    public function getScaleY(): ?float
    {
        return $this->scaleY;
    }

    public function setScaleY(?float $scaleY): static
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
