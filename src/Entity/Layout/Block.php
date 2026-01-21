<?php

declare(strict_types=1);

namespace App\Entity\Layout;

use App\Repository\Layout\BlockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Block.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'layout_block')]
#[ORM\Entity(repositoryClass: BlockRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Block extends BaseConfiguration
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'col';
    protected static array $interface = [
        'name' => 'block',
        'search' => true,
    ];

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private int $size = 12;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $mobileSize = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $tabletSize = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $miniPcSize = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $width = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $height = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $timer = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $template = 'default';

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $customTemplate = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $italic = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $uppercase = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $useForThumb = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $backgroundFullSize = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $backgroundFullHeight = true;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $backgroundColorType = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $iconSize = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $iconPosition = 'left';

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $fontWeight = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $fontWeightSecondary = null;

    #[ORM\Column(type: Types::STRING, length: 5, nullable: true)]
    private ?string $fontSize = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $fontFamily = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $script = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $autoplay = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $asLoop = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $playInHover = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $controls = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $soundControls = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $large = false;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $data = [];

    #[ORM\OneToOne(targetEntity: FieldConfiguration::class, inversedBy: 'block', cascade: ['persist', 'remove'], fetch: 'EAGER')]
    private ?FieldConfiguration $fieldConfiguration = null;

    #[ORM\OneToMany(targetEntity: ActionIntl::class, mappedBy: 'block', cascade: ['persist'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $actionIntls;

    #[ORM\OneToMany(targetEntity: BlockMediaRelation::class, mappedBy: 'block', cascade: ['persist'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['position' => 'ASC', 'locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $mediaRelations;

    #[ORM\OneToMany(targetEntity: BlockIntl::class, mappedBy: 'block', cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Col::class, cascade: ['persist'], inversedBy: 'blocks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Col $col = null;

    #[ORM\ManyToOne(targetEntity: BlockType::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?BlockType $blockType = null;

    #[ORM\ManyToOne(targetEntity: Action::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Action $action = null;

    /**
     * Block constructor.
     */
    public function __construct()
    {
        $this->actionIntls = new ArrayCollection();
        $this->mediaRelations = new ArrayCollection();
        $this->intls = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $blockType = $this->getBlockType();
        if ($blockType instanceof BlockType) {
            $this->setDefaultValuesByBlockType($blockType->getSlug());
        }

        parent::prePersist();
    }

    /**
     * To set margin by BlockType.
     */
    private function setDefaultValuesByBlockType(?string $blockTypeSlug = null): void
    {
        if (!$this->getId()) {
            $margins['title']['marginBottom'] = 'mb-md';
            $margins['zones-navigation']['marginBottom'] = 'mb-0';
            if (!empty($margins[$blockTypeSlug])) {
                foreach ($margins[$blockTypeSlug] as $key => $margin) {
                    $setter = 'set'.ucfirst($key);
                    $this->$setter($margin);
                }
            }
        }
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getMobileSize(): ?int
    {
        return $this->mobileSize;
    }

    public function setMobileSize(?int $mobileSize): static
    {
        $this->mobileSize = $mobileSize;

        return $this;
    }

    public function getTabletSize(): ?int
    {
        return $this->tabletSize;
    }

    public function setTabletSize(?int $tabletSize): static
    {
        $this->tabletSize = $tabletSize;

        return $this;
    }

    public function getMiniPcSize(): ?int
    {
        return $this->miniPcSize;
    }

    public function setMiniPcSize(?int $miniPcSize): static
    {
        $this->miniPcSize = $miniPcSize;

        return $this;
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

    public function getTimer(): ?int
    {
        return $this->timer;
    }

    public function setTimer(?int $timer): static
    {
        $this->timer = $timer;

        return $this;
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

    public function getCustomTemplate(): ?string
    {
        return $this->customTemplate;
    }

    public function setCustomTemplate(?string $customTemplate): static
    {
        $this->customTemplate = $customTemplate;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function isItalic(): ?bool
    {
        return $this->italic;
    }

    public function setItalic(bool $italic): static
    {
        $this->italic = $italic;

        return $this;
    }

    public function isUppercase(): ?bool
    {
        return $this->uppercase;
    }

    public function setUppercase(bool $uppercase): static
    {
        $this->uppercase = $uppercase;

        return $this;
    }

    public function isUseForThumb(): ?bool
    {
        return $this->useForThumb;
    }

    public function setUseForThumb(bool $useForThumb): static
    {
        $this->useForThumb = $useForThumb;

        return $this;
    }

    public function isBackgroundFullSize(): ?bool
    {
        return $this->backgroundFullSize;
    }

    public function setBackgroundFullSize(bool $backgroundFullSize): static
    {
        $this->backgroundFullSize = $backgroundFullSize;

        return $this;
    }

    public function isBackgroundFullHeight(): ?bool
    {
        return $this->backgroundFullHeight;
    }

    public function setBackgroundFullHeight(bool $backgroundFullHeight): static
    {
        $this->backgroundFullHeight = $backgroundFullHeight;

        return $this;
    }

    public function getBackgroundColorType(): ?string
    {
        return $this->backgroundColorType;
    }

    public function setBackgroundColorType(?string $backgroundColorType): static
    {
        $this->backgroundColorType = $backgroundColorType;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIconSize(): ?string
    {
        return $this->iconSize;
    }

    public function setIconSize(?string $iconSize): static
    {
        $this->iconSize = $iconSize;

        return $this;
    }

    public function getIconPosition(): ?string
    {
        return $this->iconPosition;
    }

    public function setIconPosition(?string $iconPosition): static
    {
        $this->iconPosition = $iconPosition;

        return $this;
    }

    public function getFontWeight(): ?int
    {
        return $this->fontWeight;
    }

    public function setFontWeight(?int $fontWeight): static
    {
        $this->fontWeight = $fontWeight;

        return $this;
    }

    public function getFontWeightSecondary(): ?int
    {
        return $this->fontWeightSecondary;
    }

    public function setFontWeightSecondary(?int $fontWeightSecondary): static
    {
        $this->fontWeightSecondary = $fontWeightSecondary;

        return $this;
    }

    public function getFontSize(): ?string
    {
        return $this->fontSize;
    }

    public function setFontSize(?string $fontSize): static
    {
        $this->fontSize = $fontSize;

        return $this;
    }

    public function getFontFamily(): ?string
    {
        return $this->fontFamily;
    }

    public function setFontFamily(?string $fontFamily): static
    {
        $this->fontFamily = $fontFamily;

        return $this;
    }

    public function getScript(): ?string
    {
        return $this->script;
    }

    public function setScript(?string $script): static
    {
        $this->script = $script;

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

    public function isAsLoop(): ?bool
    {
        return $this->asLoop;
    }

    public function setAsLoop(bool $asLoop): static
    {
        $this->asLoop = $asLoop;

        return $this;
    }

    public function isPlayInHover(): ?bool
    {
        return $this->playInHover;
    }

    public function setPlayInHover(bool $playInHover): static
    {
        $this->playInHover = $playInHover;

        return $this;
    }

    public function isControls(): ?bool
    {
        return $this->controls;
    }

    public function setControls(bool $controls): static
    {
        $this->controls = $controls;

        return $this;
    }

    public function isSoundControls(): ?bool
    {
        return $this->soundControls;
    }

    public function setSoundControls(bool $soundControls): static
    {
        $this->soundControls = $soundControls;

        return $this;
    }

    public function isLarge(): ?bool
    {
        return $this->large;
    }

    public function setLarge(bool $large): static
    {
        $this->large = $large;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getFieldConfiguration(): ?FieldConfiguration
    {
        return $this->fieldConfiguration;
    }

    public function setFieldConfiguration(?FieldConfiguration $fieldConfiguration): static
    {
        $this->fieldConfiguration = $fieldConfiguration;

        return $this;
    }

    /**
     * @return Collection<int, ActionIntl>
     */
    public function getActionIntls(): Collection
    {
        return $this->actionIntls;
    }

    public function addActionIntl(ActionIntl $actionIntl): static
    {
        if (!$this->actionIntls->contains($actionIntl)) {
            $this->actionIntls->add($actionIntl);
            $actionIntl->setBlock($this);
        }

        return $this;
    }

    public function removeActionIntl(ActionIntl $actionIntl): static
    {
        if ($this->actionIntls->removeElement($actionIntl)) {
            // set the owning side to null (unless already changed)
            if ($actionIntl->getBlock() === $this) {
                $actionIntl->setBlock(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, BlockMediaRelation>
     */
    public function getMediaRelations(): Collection
    {
        return $this->mediaRelations;
    }

    public function addMediaRelation(BlockMediaRelation $mediaRelation): static
    {
        if (!$this->mediaRelations->contains($mediaRelation)) {
            $this->mediaRelations->add($mediaRelation);
            $mediaRelation->setBlock($this);
        }

        return $this;
    }

    public function removeMediaRelation(BlockMediaRelation $mediaRelation): static
    {
        if ($this->mediaRelations->removeElement($mediaRelation)) {
            // set the owning side to null (unless already changed)
            if ($mediaRelation->getBlock() === $this) {
                $mediaRelation->setBlock(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, BlockIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(BlockIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setBlock($this);
        }

        return $this;
    }

    public function removeIntl(BlockIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getBlock() === $this) {
                $intl->setBlock(null);
            }
        }

        return $this;
    }

    public function getCol(): ?Col
    {
        return $this->col;
    }

    public function setCol(?Col $col): static
    {
        $this->col = $col;

        return $this;
    }

    public function getBlockType(): ?BlockType
    {
        return $this->blockType;
    }

    public function setBlockType(?BlockType $blockType): static
    {
        $this->blockType = $blockType;

        return $this;
    }

    public function getAction(): ?Action
    {
        return $this->action;
    }

    public function setAction(?Action $action): static
    {
        $this->action = $action;

        return $this;
    }
}
