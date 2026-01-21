<?php

declare(strict_types=1);

namespace App\Entity\Media;

use App\Entity\BaseInterface;
use App\Entity\Core\Website;
use App\Repository\Media\MediaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Media.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'media')]
#[ORM\Index(columns: ['filename'], flags: ['fulltext'])]
#[ORM\Index(columns: ['name'], flags: ['fulltext'])]
#[ORM\Entity(repositoryClass: MediaRepository::class)]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'categories',
        joinColumns: [new ORM\JoinColumn(name: 'media_id', referencedColumnName: 'id', onDelete: 'cascade')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'category_id', referencedColumnName: 'id', onDelete: 'cascade')],
        joinTable: new ORM\JoinTable(name: 'media_core_categories')
    ),
])]
class Media extends BaseInterface
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'media',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $filename = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $extension = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $copyright = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $titlePosition = 'bottom-start';

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $deletable = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $compress = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $notContractual = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $hideHover = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $haveMediaScreens = false;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $screen = 'desktop';

    #[ORM\Column(type: Types::INTEGER, length: 10)]
    private int $quality = 85;

    #[ORM\OneToMany(targetEntity: Thumb::class, mappedBy: 'media', cascade: ['persist'], fetch: 'EAGER')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $thumbs;

    #[ORM\OneToMany(targetEntity: Media::class, mappedBy: 'media', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['id' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $mediaScreens;

    #[ORM\OneToMany(targetEntity: MediaIntl::class, mappedBy: 'media', cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Folder::class, inversedBy: 'medias')]
    #[ORM\JoinColumn(name: 'folder_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?Folder $folder = null;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    #[ORM\ManyToOne(targetEntity: Media::class, cascade: ['persist', 'remove'], inversedBy: 'mediaScreens')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?Media $media = null;

    #[ORM\ManyToMany(targetEntity: Category::class, cascade: ['persist'])]
    #[ORM\OrderBy(['adminName' => 'ASC'])]
    private ArrayCollection|PersistentCollection $categories;

    /**
     * Media constructor.
     */
    public function __construct()
    {
        $this->thumbs = new ArrayCollection();
        $this->mediaScreens = new ArrayCollection();
        $this->intls = new ArrayCollection();
        $this->categories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(?string $extension): static
    {
        $this->extension = $extension;

        return $this;
    }

    public function getCopyright(): ?string
    {
        return $this->copyright;
    }

    public function setCopyright(?string $copyright): static
    {
        $this->copyright = $copyright;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getTitlePosition(): ?string
    {
        return $this->titlePosition;
    }

    public function setTitlePosition(?string $titlePosition): static
    {
        $this->titlePosition = $titlePosition;

        return $this;
    }

    public function isDeletable(): ?bool
    {
        return $this->deletable;
    }

    public function setDeletable(bool $deletable): static
    {
        $this->deletable = $deletable;

        return $this;
    }

    public function isCompress(): ?bool
    {
        return $this->compress;
    }

    public function setCompress(bool $compress): static
    {
        $this->compress = $compress;

        return $this;
    }

    public function isNotContractual(): ?bool
    {
        return $this->notContractual;
    }

    public function setNotContractual(bool $notContractual): static
    {
        $this->notContractual = $notContractual;

        return $this;
    }

    public function isHideHover(): ?bool
    {
        return $this->hideHover;
    }

    public function setHideHover(bool $hideHover): static
    {
        $this->hideHover = $hideHover;

        return $this;
    }

    public function isHaveMediaScreens(): ?bool
    {
        return $this->haveMediaScreens;
    }

    public function setHaveMediaScreens(bool $haveMediaScreens): static
    {
        $this->haveMediaScreens = $haveMediaScreens;

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

    public function getQuality(): ?int
    {
        return $this->quality;
    }

    public function setQuality(int $quality): static
    {
        $this->quality = $quality;

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
            $thumb->setMedia($this);
        }

        return $this;
    }

    public function removeThumb(Thumb $thumb): static
    {
        if ($this->thumbs->removeElement($thumb)) {
            // set the owning side to null (unless already changed)
            if ($thumb->getMedia() === $this) {
                $thumb->setMedia(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Media>
     */
    public function getMediaScreens(): Collection
    {
        return $this->mediaScreens;
    }

    public function addMediaScreen(Media $mediaScreen): static
    {
        if (!$this->mediaScreens->contains($mediaScreen)) {
            $this->mediaScreens->add($mediaScreen);
            $mediaScreen->setMedia($this);
        }

        return $this;
    }

    public function removeMediaScreen(Media $mediaScreen): static
    {
        if ($this->mediaScreens->removeElement($mediaScreen)) {
            // set the owning side to null (unless already changed)
            if ($mediaScreen->getMedia() === $this) {
                $mediaScreen->setMedia(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MediaIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(MediaIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setMedia($this);
        }

        return $this;
    }

    public function removeIntl(MediaIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getMedia() === $this) {
                $intl->setMedia(null);
            }
        }

        return $this;
    }

    public function getFolder(): ?Folder
    {
        return $this->folder;
    }

    public function setFolder(?Folder $folder): static
    {
        $this->folder = $folder;

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

    public function getMedia(): ?self
    {
        return $this->media;
    }

    public function setMedia(?self $media): static
    {
        $this->media = $media;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }
}
