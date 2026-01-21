<?php

declare(strict_types=1);

namespace App\Entity\Module\Tab;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Repository\Module\Tab\TabRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Tab.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_tab')]
#[ORM\Entity(repositoryClass: TabRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Tab extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'tab',
        'buttons' => [
            'admin_tabcontent_tree',
        ],
    ];
    protected static array $labels = [
        'admin_tabcontent_tree' => 'Contenus',
    ];

    #[ORM\Column(type: Types::STRING, length: 100)]
    protected ?string $template = 'horizontal';

    #[ORM\OneToMany(targetEntity: Content::class, mappedBy: 'tab', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $contents;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    /**
     * Tab constructor.
     */
    public function __construct()
    {
        $this->contents = new ArrayCollection();
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

    /**
     * @return Collection<int, Content>
     */
    public function getContents(): Collection
    {
        return $this->contents;
    }

    public function addContent(Content $content): static
    {
        if (!$this->contents->contains($content)) {
            $this->contents->add($content);
            $content->setTab($this);
        }

        return $this;
    }

    public function removeContent(Content $content): static
    {
        if ($this->contents->removeElement($content)) {
            // set the owning side to null (unless already changed)
            if ($content->getTab() === $this) {
                $content->setTab(null);
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
