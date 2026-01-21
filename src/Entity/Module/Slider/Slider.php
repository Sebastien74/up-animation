<?php

declare(strict_types=1);

namespace App\Entity\Module\Slider;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Repository\Module\Slider\SliderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Slider.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_slider')]
#[ORM\Entity(repositoryClass: SliderRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Slider extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'slider',
        'table_name' => 'module_slider',
    ];

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $template = 'bootstrap';

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\NotBlank]
    private int $intervalDuration = 5000;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\NotBlank]
    private int $itemsPerSlide = 1;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $itemsPerSlideMiniPC = 1;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $itemsPerSlideTablet = 1;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $itemsPerSlideMobile = 1;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $offsetDesktop = 150;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $offsetMiniPC = 50;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $offsetTablet = 50;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $offsetMobile = 50;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $focus = 'left';

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $focusMiniPC = 'left';

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $focusTablet = 'left';

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $focusMobile = 'left';

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $effect = 'fade';

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $standardizeMedia = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $indicators = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $control = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $autoplay = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $pause = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $popup = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $progress = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $thumbnails = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $backgroundColor = 'bg-primary';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $arrowColor = 'btn-primary';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $arrowAlignment = 'bottom-start';

    #[ORM\OneToMany(targetEntity: SliderMediaRelation::class, mappedBy: 'slider', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['position' => 'ASC', 'locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $mediaRelations;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if (!$this->id && 'bootstrap' === $this->template) {
            $this->arrowAlignment = 'bottom-end';
        } elseif (!$this->id && 'splide' === $this->template) {
            $this->arrowAlignment = 'top-end';
        } elseif (!$this->id && 'banner' === $this->template) {
            $this->intervalDuration = 15000;
        }

        if (!$this->id && 'splide' === $this->template) {
            $this->progress = true;
            $this->control = true;
            $this->itemsPerSlide = 4;
            $this->itemsPerSlideMiniPC = 3;
            $this->itemsPerSlideTablet = 2;
            $this->itemsPerSlideMobile = 1;
        }

        parent::prePersist();
    }

    /**
     * Slider constructor.
     */
    public function __construct()
    {
        $this->mediaRelations = new ArrayCollection();
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function setTemplate(string $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function getIntervalDuration(): ?int
    {
        return $this->intervalDuration;
    }

    public function setIntervalDuration(?int $intervalDuration): static
    {
        $this->intervalDuration = $intervalDuration;

        return $this;
    }

    public function getItemsPerSlide(): ?int
    {
        return $this->itemsPerSlide;
    }

    public function setItemsPerSlide(?int $itemsPerSlide): static
    {
        $this->itemsPerSlide = $itemsPerSlide;

        return $this;
    }

    public function getItemsPerSlideMiniPC(): ?int
    {
        return $this->itemsPerSlideMiniPC;
    }

    public function setItemsPerSlideMiniPC(?int $itemsPerSlideMiniPC): static
    {
        $this->itemsPerSlideMiniPC = $itemsPerSlideMiniPC;

        return $this;
    }

    public function getItemsPerSlideTablet(): ?int
    {
        return $this->itemsPerSlideTablet;
    }

    public function setItemsPerSlideTablet(?int $itemsPerSlideTablet): static
    {
        $this->itemsPerSlideTablet = $itemsPerSlideTablet;

        return $this;
    }

    public function getItemsPerSlideMobile(): ?int
    {
        return $this->itemsPerSlideMobile;
    }

    public function setItemsPerSlideMobile(?int $itemsPerSlideMobile): static
    {
        $this->itemsPerSlideMobile = $itemsPerSlideMobile;

        return $this;
    }

    public function getOffsetDesktop(): ?int
    {
        return $this->offsetDesktop;
    }

    public function setOffsetDesktop(?int $offsetDesktop): static
    {
        $this->offsetDesktop = $offsetDesktop;

        return $this;
    }

    public function getOffsetMiniPC(): ?int
    {
        return $this->offsetMiniPC;
    }

    public function setOffsetMiniPC(?int $offsetMiniPC): static
    {
        $this->offsetMiniPC = $offsetMiniPC;

        return $this;
    }

    public function getOffsetTablet(): ?int
    {
        return $this->offsetTablet;
    }

    public function setOffsetTablet(?int $offsetTablet): static
    {
        $this->offsetTablet = $offsetTablet;

        return $this;
    }

    public function getOffsetMobile(): ?int
    {
        return $this->offsetMobile;
    }

    public function setOffsetMobile(?int $offsetMobile): static
    {
        $this->offsetMobile = $offsetMobile;

        return $this;
    }

    public function getFocus(): ?string
    {
        return $this->focus;
    }

    public function setFocus(?string $focus): static
    {
        $this->focus = $focus;

        return $this;
    }

    public function getFocusMiniPC(): ?string
    {
        return $this->focusMiniPC;
    }

    public function setFocusMiniPC(?string $focusMiniPC): static
    {
        $this->focusMiniPC = $focusMiniPC;

        return $this;
    }

    public function getFocusTablet(): ?string
    {
        return $this->focusTablet;
    }

    public function setFocusTablet(?string $focusTablet): static
    {
        $this->focusTablet = $focusTablet;

        return $this;
    }

    public function getFocusMobile(): ?string
    {
        return $this->focusMobile;
    }

    public function setFocusMobile(?string $focusMobile): static
    {
        $this->focusMobile = $focusMobile;

        return $this;
    }

    public function getEffect(): ?string
    {
        return $this->effect;
    }

    public function setEffect(string $effect): static
    {
        $this->effect = $effect;

        return $this;
    }

    public function isStandardizeMedia(): ?bool
    {
        return $this->standardizeMedia;
    }

    public function setStandardizeMedia(bool $standardizeMedia): static
    {
        $this->standardizeMedia = $standardizeMedia;

        return $this;
    }

    public function isIndicators(): ?bool
    {
        return $this->indicators;
    }

    public function setIndicators(bool $indicators): static
    {
        $this->indicators = $indicators;

        return $this;
    }

    public function isControl(): ?bool
    {
        return $this->control;
    }

    public function setControl(bool $control): static
    {
        $this->control = $control;

        return $this;
    }

    public function isAutoplay(): ?bool
    {
        return $this->autoplay;
    }

    public function setAutoplay(bool $autoplay): static
    {
        $this->autoplay = $autoplay;

        return $this;
    }

    public function isPause(): ?bool
    {
        return $this->pause;
    }

    public function setPause(bool $pause): static
    {
        $this->pause = $pause;

        return $this;
    }

    public function isPopup(): ?bool
    {
        return $this->popup;
    }

    public function setPopup(bool $popup): static
    {
        $this->popup = $popup;

        return $this;
    }

    public function isProgress(): ?bool
    {
        return $this->progress;
    }

    public function setProgress(bool $progress): static
    {
        $this->progress = $progress;

        return $this;
    }

    public function isThumbnails(): ?bool
    {
        return $this->thumbnails;
    }

    public function setThumbnails(bool $thumbnails): static
    {
        $this->thumbnails = $thumbnails;

        return $this;
    }

    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    public function setBackgroundColor(?string $backgroundColor): static
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    public function getArrowColor(): ?string
    {
        return $this->arrowColor;
    }

    public function setArrowColor(?string $arrowColor): static
    {
        $this->arrowColor = $arrowColor;

        return $this;
    }

    public function getArrowAlignment(): ?string
    {
        return $this->arrowAlignment;
    }

    public function setArrowAlignment(?string $arrowAlignment): static
    {
        $this->arrowAlignment = $arrowAlignment;

        return $this;
    }

    /**
     * @return Collection<int, SliderMediaRelation>
     */
    public function getMediaRelations(): Collection
    {
        return $this->mediaRelations;
    }

    public function addMediaRelation(SliderMediaRelation $mediaRelation): static
    {
        if (!$this->mediaRelations->contains($mediaRelation)) {
            $this->mediaRelations->add($mediaRelation);
            $mediaRelation->setSlider($this);
        }

        return $this;
    }

    public function removeMediaRelation(SliderMediaRelation $mediaRelation): static
    {
        if ($this->mediaRelations->removeElement($mediaRelation)) {
            // set the owning side to null (unless already changed)
            if ($mediaRelation->getSlider() === $this) {
                $mediaRelation->setSlider(null);
            }
        }

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
