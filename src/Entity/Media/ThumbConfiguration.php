<?php

declare(strict_types=1);

namespace App\Entity\Media;

use App\Entity\BaseEntity;
use App\Entity\Core\Configuration;
use App\Repository\Media\ThumbConfigurationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ThumbConfiguration.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'media_thumb_configuration')]
#[ORM\Entity(repositoryClass: ThumbConfigurationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ThumbConfiguration extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'configuration';
    protected static array $interface = [
        'name' => 'thumbconfiguration',
    ];

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $width = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $height = null;

    #[ORM\Column(type: Types::STRING, length: 10)]
    private string $screen = 'desktop';

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $fixedHeight = false;

    #[ORM\OneToMany(targetEntity: ThumbAction::class, mappedBy: 'configuration', cascade: ['persist'])]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $actions;

    #[ORM\OneToMany(targetEntity: Thumb::class, mappedBy: 'configuration', cascade: ['persist'])]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $thumbs;

    #[ORM\ManyToOne(targetEntity: Configuration::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Configuration $configuration = null;

    /**
     * ThumbConfiguration constructor.
     */
    public function __construct()
    {
        $this->actions = new ArrayCollection();
        $this->thumbs = new ArrayCollection();
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

    public function getScreen(): ?string
    {
        return $this->screen;
    }

    public function setScreen(string $screen): static
    {
        $this->screen = $screen;

        return $this;
    }

    public function isFixedHeight(): ?bool
    {
        return $this->fixedHeight;
    }

    public function setFixedHeight(bool $fixedHeight): static
    {
        $this->fixedHeight = $fixedHeight;

        return $this;
    }

    /**
     * @return Collection<int, ThumbAction>
     */
    public function getActions(): Collection
    {
        return $this->actions;
    }

    public function addAction(ThumbAction $action): static
    {
        if (!$this->actions->contains($action)) {
            $this->actions->add($action);
            $action->setConfiguration($this);
        }

        return $this;
    }

    public function removeAction(ThumbAction $action): static
    {
        if ($this->actions->removeElement($action)) {
            // set the owning side to null (unless already changed)
            if ($action->getConfiguration() === $this) {
                $action->setConfiguration(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Thumb>
     */
    public function getThumbs(): Collection
    {
        return $this->thumbs;
    }

    public function addThumb(Thumb $thumb): static
    {
        if (!$this->thumbs->contains($thumb)) {
            $this->thumbs->add($thumb);
            $thumb->setConfiguration($this);
        }

        return $this;
    }

    public function removeThumb(Thumb $thumb): static
    {
        if ($this->thumbs->removeElement($thumb)) {
            // set the owning side to null (unless already changed)
            if ($thumb->getConfiguration() === $this) {
                $thumb->setConfiguration(null);
            }
        }

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
}
