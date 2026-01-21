<?php

declare(strict_types=1);

namespace App\Entity\Media;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Repository\Media\FolderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * Folder.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'media_folder')]
#[ORM\Entity(repositoryClass: FolderRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Folder extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'folder',
    ];

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private int $level = 1;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $webmaster = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $deletable = true;

    #[ORM\OneToMany(targetEntity: Media::class, mappedBy: 'folder')]
    private ArrayCollection|PersistentCollection $medias;

    #[ORM\OneToMany(targetEntity: Folder::class, mappedBy: 'parent')]
    private ArrayCollection|PersistentCollection $folders;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    #[ORM\ManyToOne(targetEntity: Folder::class, inversedBy: 'folders')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'cascade')]
    private ?Folder $parent = null;

    /**
     * Folder constructor.
     */
    public function __construct()
    {
        $this->medias = new ArrayCollection();
        $this->folders = new ArrayCollection();
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(?int $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function isWebmaster(): ?bool
    {
        return $this->webmaster;
    }

    public function setWebmaster(bool $webmaster): static
    {
        $this->webmaster = $webmaster;

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

    /**
     * @return Collection<int, Media>
     */
    public function getMedias(): Collection
    {
        return $this->medias;
    }

    public function addMedia(Media $media): static
    {
        if (!$this->medias->contains($media)) {
            $this->medias->add($media);
            $media->setFolder($this);
        }

        return $this;
    }

    public function removeMedia(Media $media): static
    {
        if ($this->medias->removeElement($media)) {
            // set the owning side to null (unless already changed)
            if ($media->getFolder() === $this) {
                $media->setFolder(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Folder>
     */
    public function getFolders(): Collection
    {
        return $this->folders;
    }

    public function addFolder(Folder $folder): static
    {
        if (!$this->folders->contains($folder)) {
            $this->folders->add($folder);
            $folder->setParent($this);
        }

        return $this;
    }

    public function removeFolder(Folder $folder): static
    {
        if ($this->folders->removeElement($folder)) {
            // set the owning side to null (unless already changed)
            if ($folder->getParent() === $this) {
                $folder->setParent(null);
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

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }
}
