<?php

declare(strict_types=1);

namespace App\Entity\Module\Newscast;

use App\Entity\BaseEntity;
use App\Repository\Module\Newscast\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * Tag.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_newscast_tag')]
#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Tag extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'newscasttag',
    ];

    #[ORM\ManyToMany(targetEntity: Newscast::class, mappedBy: 'tags')]
    private ArrayCollection|PersistentCollection $newscasts;

    /**
     * Tag constructor.
     */
    public function __construct()
    {
        $this->newscasts = new ArrayCollection();
    }

    /**
     * @return Collection<int, Newscast>
     */
    public function getNewscasts(): Collection
    {
        return $this->newscasts;
    }

    public function addNewscast(Newscast $newscast): static
    {
        if (!$this->newscasts->contains($newscast)) {
            $this->newscasts->add($newscast);
            $newscast->addTag($this);
        }

        return $this;
    }

    public function removeNewscast(Newscast $newscast): static
    {
        if ($this->newscasts->removeElement($newscast)) {
            $newscast->removeTag($this);
        }

        return $this;
    }
}
