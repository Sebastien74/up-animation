<?php

declare(strict_types=1);

namespace App\Entity\Layout;

use App\Entity\BaseEntity;
use App\Entity\Core\Transition;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * BaseConfiguration.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
class BaseConfiguration extends BaseEntity
{
    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $fullSize = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $customId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $customClass = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $idAsAnchor = false;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    protected ?string $hexadecimalCode = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    protected ?string $zIndex = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    protected ?string $shadow = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    protected ?string $shadowMobile = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $standardizeElements = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $hide = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $verticalAlign = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $endAlign = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $reverse = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $hideMobile = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $hideTablet = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $hideMiniPc = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $hideDesktop = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $backgroundColor = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $alignmentMobile = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $alignmentTablet = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $alignmentMiniPc = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $alignment = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $elementsAlignment = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $mobilePosition = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $tabletPosition = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $miniPcPosition = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $marginTop = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $marginRight = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $marginBottom = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $marginLeft = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $paddingTop = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $paddingRight = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $paddingBottom = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $paddingLeft = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $marginTopMobile = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $marginRightMobile = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $marginBottomMobile = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $marginLeftMobile = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $paddingTopMobile = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $paddingRightMobile = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $paddingBottomMobile = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $paddingLeftMobile = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $marginTopTablet = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $marginRightTablet = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $marginBottomTablet = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $marginLeftTablet = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $paddingTopTablet = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $paddingRightTablet = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $paddingBottomTablet = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $paddingLeftTablet = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $marginTopMiniPc = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $marginRightMiniPc = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $marginBottomMiniPc = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $marginLeftMiniPc = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $paddingTopMiniPc = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $paddingRightMiniPc = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $paddingBottomMiniPc = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $paddingLeftMiniPc = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $duration = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $delay = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $radius = false;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $hideLocales = [];

    #[ORM\ManyToOne(targetEntity: Transition::class, cascade: ['persist'])]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?Transition $transition = null;

    public function isFullSize(): ?bool
    {
        return $this->fullSize;
    }

    public function setFullSize(bool $fullSize): static
    {
        $this->fullSize = $fullSize;

        return $this;
    }

    public function getCustomId(): ?string
    {
        return $this->customId;
    }

    public function setCustomId(?string $customId): static
    {
        $this->customId = $customId;

        return $this;
    }

    public function getCustomClass(): ?string
    {
        return $this->customClass;
    }

    public function setCustomClass(?string $customClass): static
    {
        $this->customClass = $customClass;

        return $this;
    }

    public function isIdAsAnchor(): ?bool
    {
        return $this->idAsAnchor;
    }

    public function setIdAsAnchor(bool $idAsAnchor): static
    {
        $this->idAsAnchor = $idAsAnchor;

        return $this;
    }

    public function getHexadecimalCode(): ?string
    {
        return $this->hexadecimalCode;
    }

    public function setHexadecimalCode(?string $hexadecimalCode): static
    {
        $this->hexadecimalCode = $hexadecimalCode;

        return $this;
    }

    public function getZIndex(): ?string
    {
        return $this->zIndex;
    }

    public function setZIndex(?string $zIndex): static
    {
        $this->zIndex = $zIndex;

        return $this;
    }

    public function getShadow(): ?string
    {
        return $this->shadow;
    }

    public function setShadow(?string $shadow): static
    {
        $this->shadow = $shadow;

        return $this;
    }

    public function getShadowMobile(): ?string
    {
        return $this->shadowMobile;
    }

    public function setShadowMobile(?string $shadowMobile): static
    {
        $this->shadowMobile = $shadowMobile;

        return $this;
    }

    public function isStandardizeElements(): ?bool
    {
        return $this->standardizeElements;
    }

    public function setStandardizeElements(bool $standardizeElements): static
    {
        $this->standardizeElements = $standardizeElements;

        return $this;
    }

    public function isHide(): ?bool
    {
        return $this->hide;
    }

    public function setHide(bool $hide): static
    {
        $this->hide = $hide;

        return $this;
    }

    public function isVerticalAlign(): ?bool
    {
        return $this->verticalAlign;
    }

    public function setVerticalAlign(bool $verticalAlign): static
    {
        $this->verticalAlign = $verticalAlign;

        return $this;
    }

    public function isEndAlign(): ?bool
    {
        return $this->endAlign;
    }

    public function setEndAlign(bool $endAlign): static
    {
        $this->endAlign = $endAlign;

        return $this;
    }

    public function isReverse(): ?bool
    {
        return $this->reverse;
    }

    public function setReverse(bool $reverse): static
    {
        $this->reverse = $reverse;

        return $this;
    }

    public function isHideMobile(): ?bool
    {
        return $this->hideMobile;
    }

    public function setHideMobile(bool $hideMobile): static
    {
        $this->hideMobile = $hideMobile;

        return $this;
    }

    public function isHideTablet(): ?bool
    {
        return $this->hideTablet;
    }

    public function setHideTablet(bool $hideTablet): static
    {
        $this->hideTablet = $hideTablet;

        return $this;
    }

    public function isHideMiniPc(): ?bool
    {
        return $this->hideMiniPc;
    }

    public function setHideMiniPc(bool $hideMiniPc): static
    {
        $this->hideMiniPc = $hideMiniPc;

        return $this;
    }

    public function isHideDesktop(): ?bool
    {
        return $this->hideDesktop;
    }

    public function setHideDesktop(bool $hideDesktop): static
    {
        $this->hideDesktop = $hideDesktop;

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

    public function getAlignmentMobile(): ?string
    {
        return $this->alignmentMobile;
    }

    public function setAlignmentMobile(?string $alignmentMobile): static
    {
        $this->alignmentMobile = $alignmentMobile;

        return $this;
    }

    public function getAlignmentTablet(): ?string
    {
        return $this->alignmentTablet;
    }

    public function setAlignmentTablet(?string $alignmentTablet): static
    {
        $this->alignmentTablet = $alignmentTablet;

        return $this;
    }

    public function getAlignmentMiniPc(): ?string
    {
        return $this->alignmentMiniPc;
    }

    public function setAlignmentMiniPc(?string $alignmentMiniPc): static
    {
        $this->alignmentMiniPc = $alignmentMiniPc;

        return $this;
    }

    public function getAlignment(): ?string
    {
        return $this->alignment;
    }

    public function setAlignment(?string $alignment): static
    {
        $this->alignment = $alignment;

        return $this;
    }

    public function getElementsAlignment(): ?string
    {
        return $this->elementsAlignment;
    }

    public function setElementsAlignment(?string $elementsAlignment): static
    {
        $this->elementsAlignment = $elementsAlignment;

        return $this;
    }

    public function getMobilePosition(): ?int
    {
        return $this->mobilePosition;
    }

    public function setMobilePosition(?int $mobilePosition): static
    {
        $this->mobilePosition = $mobilePosition;

        return $this;
    }

    public function getTabletPosition(): ?int
    {
        return $this->tabletPosition;
    }

    public function setTabletPosition(?int $tabletPosition): static
    {
        $this->tabletPosition = $tabletPosition;

        return $this;
    }

    public function getMiniPcPosition(): ?int
    {
        return $this->miniPcPosition;
    }

    public function setMiniPcPosition(?int $miniPcPosition): static
    {
        $this->miniPcPosition = $miniPcPosition;

        return $this;
    }

    public function getMarginTop(): ?string
    {
        return $this->marginTop;
    }

    public function setMarginTop(?string $marginTop): static
    {
        $this->marginTop = $marginTop;

        return $this;
    }

    public function getMarginRight(): ?string
    {
        return $this->marginRight;
    }

    public function setMarginRight(?string $marginRight): static
    {
        $this->marginRight = $marginRight;

        return $this;
    }

    public function getMarginBottom(): ?string
    {
        return $this->marginBottom;
    }

    public function setMarginBottom(?string $marginBottom): static
    {
        $this->marginBottom = $marginBottom;

        return $this;
    }

    public function getMarginLeft(): ?string
    {
        return $this->marginLeft;
    }

    public function setMarginLeft(?string $marginLeft): static
    {
        $this->marginLeft = $marginLeft;

        return $this;
    }

    public function getPaddingTop(): ?string
    {
        return $this->paddingTop;
    }

    public function setPaddingTop(?string $paddingTop): static
    {
        $this->paddingTop = $paddingTop;

        return $this;
    }

    public function getPaddingRight(): ?string
    {
        return $this->paddingRight;
    }

    public function setPaddingRight(?string $paddingRight): static
    {
        $this->paddingRight = $paddingRight;

        return $this;
    }

    public function getPaddingBottom(): ?string
    {
        return $this->paddingBottom;
    }

    public function setPaddingBottom(?string $paddingBottom): static
    {
        $this->paddingBottom = $paddingBottom;

        return $this;
    }

    public function getPaddingLeft(): ?string
    {
        return $this->paddingLeft;
    }

    public function setPaddingLeft(?string $paddingLeft): static
    {
        $this->paddingLeft = $paddingLeft;

        return $this;
    }

    public function getMarginTopMobile(): ?string
    {
        return $this->marginTopMobile;
    }

    public function setMarginTopMobile(?string $marginTopMobile): static
    {
        $this->marginTopMobile = $marginTopMobile;

        return $this;
    }

    public function getMarginRightMobile(): ?string
    {
        return $this->marginRightMobile;
    }

    public function setMarginRightMobile(?string $marginRightMobile): static
    {
        $this->marginRightMobile = $marginRightMobile;

        return $this;
    }

    public function getMarginBottomMobile(): ?string
    {
        return $this->marginBottomMobile;
    }

    public function setMarginBottomMobile(?string $marginBottomMobile): static
    {
        $this->marginBottomMobile = $marginBottomMobile;

        return $this;
    }

    public function getMarginLeftMobile(): ?string
    {
        return $this->marginLeftMobile;
    }

    public function setMarginLeftMobile(?string $marginLeftMobile): static
    {
        $this->marginLeftMobile = $marginLeftMobile;

        return $this;
    }

    public function getPaddingTopMobile(): ?string
    {
        return $this->paddingTopMobile;
    }

    public function setPaddingTopMobile(?string $paddingTopMobile): static
    {
        $this->paddingTopMobile = $paddingTopMobile;

        return $this;
    }

    public function getPaddingRightMobile(): ?string
    {
        return $this->paddingRightMobile;
    }

    public function setPaddingRightMobile(?string $paddingRightMobile): static
    {
        $this->paddingRightMobile = $paddingRightMobile;

        return $this;
    }

    public function getPaddingBottomMobile(): ?string
    {
        return $this->paddingBottomMobile;
    }

    public function setPaddingBottomMobile(?string $paddingBottomMobile): static
    {
        $this->paddingBottomMobile = $paddingBottomMobile;

        return $this;
    }

    public function getPaddingLeftMobile(): ?string
    {
        return $this->paddingLeftMobile;
    }

    public function setPaddingLeftMobile(?string $paddingLeftMobile): static
    {
        $this->paddingLeftMobile = $paddingLeftMobile;

        return $this;
    }

    public function getMarginTopTablet(): ?string
    {
        return $this->marginTopTablet;
    }

    public function setMarginTopTablet(?string $marginTopTablet): static
    {
        $this->marginTopTablet = $marginTopTablet;

        return $this;
    }

    public function getMarginRightTablet(): ?string
    {
        return $this->marginRightTablet;
    }

    public function setMarginRightTablet(?string $marginRightTablet): static
    {
        $this->marginRightTablet = $marginRightTablet;

        return $this;
    }

    public function getMarginBottomTablet(): ?string
    {
        return $this->marginBottomTablet;
    }

    public function setMarginBottomTablet(?string $marginBottomTablet): static
    {
        $this->marginBottomTablet = $marginBottomTablet;

        return $this;
    }

    public function getMarginLeftTablet(): ?string
    {
        return $this->marginLeftTablet;
    }

    public function setMarginLeftTablet(?string $marginLeftTablet): static
    {
        $this->marginLeftTablet = $marginLeftTablet;

        return $this;
    }

    public function getPaddingTopTablet(): ?string
    {
        return $this->paddingTopTablet;
    }

    public function setPaddingTopTablet(?string $paddingTopTablet): static
    {
        $this->paddingTopTablet = $paddingTopTablet;

        return $this;
    }

    public function getPaddingRightTablet(): ?string
    {
        return $this->paddingRightTablet;
    }

    public function setPaddingRightTablet(?string $paddingRightTablet): static
    {
        $this->paddingRightTablet = $paddingRightTablet;

        return $this;
    }

    public function getPaddingBottomTablet(): ?string
    {
        return $this->paddingBottomTablet;
    }

    public function setPaddingBottomTablet(?string $paddingBottomTablet): static
    {
        $this->paddingBottomTablet = $paddingBottomTablet;

        return $this;
    }

    public function getPaddingLeftTablet(): ?string
    {
        return $this->paddingLeftTablet;
    }

    public function setPaddingLeftTablet(?string $paddingLeftTablet): static
    {
        $this->paddingLeftTablet = $paddingLeftTablet;

        return $this;
    }

    public function getMarginTopMiniPc(): ?string
    {
        return $this->marginTopMiniPc;
    }

    public function setMarginTopMiniPc(?string $marginTopMiniPc): static
    {
        $this->marginTopMiniPc = $marginTopMiniPc;

        return $this;
    }

    public function getMarginRightMiniPc(): ?string
    {
        return $this->marginRightMiniPc;
    }

    public function setMarginRightMiniPc(?string $marginRightMiniPc): static
    {
        $this->marginRightMiniPc = $marginRightMiniPc;

        return $this;
    }

    public function getMarginBottomMiniPc(): ?string
    {
        return $this->marginBottomMiniPc;
    }

    public function setMarginBottomMiniPc(?string $marginBottomMiniPc): static
    {
        $this->marginBottomMiniPc = $marginBottomMiniPc;

        return $this;
    }

    public function getMarginLeftMiniPc(): ?string
    {
        return $this->marginLeftMiniPc;
    }

    public function setMarginLeftMiniPc(?string $marginLeftMiniPc): static
    {
        $this->marginLeftMiniPc = $marginLeftMiniPc;

        return $this;
    }

    public function getPaddingTopMiniPc(): ?string
    {
        return $this->paddingTopMiniPc;
    }

    public function setPaddingTopMiniPc(?string $paddingTopMiniPc): static
    {
        $this->paddingTopMiniPc = $paddingTopMiniPc;

        return $this;
    }

    public function getPaddingRightMiniPc(): ?string
    {
        return $this->paddingRightMiniPc;
    }

    public function setPaddingRightMiniPc(?string $paddingRightMiniPc): static
    {
        $this->paddingRightMiniPc = $paddingRightMiniPc;

        return $this;
    }

    public function getPaddingBottomMiniPc(): ?string
    {
        return $this->paddingBottomMiniPc;
    }

    public function setPaddingBottomMiniPc(?string $paddingBottomMiniPc): static
    {
        $this->paddingBottomMiniPc = $paddingBottomMiniPc;

        return $this;
    }

    public function getPaddingLeftMiniPc(): ?string
    {
        return $this->paddingLeftMiniPc;
    }

    public function setPaddingLeftMiniPc(?string $paddingLeftMiniPc): static
    {
        $this->paddingLeftMiniPc = $paddingLeftMiniPc;

        return $this;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(?string $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getDelay(): ?string
    {
        return $this->delay;
    }

    public function setDelay(?string $delay): static
    {
        $this->delay = $delay;

        return $this;
    }

    public function isRadius(): ?bool
    {
        return $this->radius;
    }

    public function setRadius(bool $radius): static
    {
        $this->radius = $radius;

        return $this;
    }

    public function getHideLocales(): array
    {
        return $this->hideLocales;
    }

    public function setHideLocales(?array $hideLocales): static
    {
        $this->hideLocales = $hideLocales;

        return $this;
    }

    public function getTransition(): ?Transition
    {
        return $this->transition;
    }

    public function setTransition(?Transition $transition): static
    {
        $this->transition = $transition;

        return $this;
    }
}
