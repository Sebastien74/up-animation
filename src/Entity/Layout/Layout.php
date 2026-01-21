<?php

declare(strict_types=1);

namespace App\Entity\Layout;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Repository\Layout\LayoutRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Exception;

/**
 * Layout.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'layout')]
#[ORM\Entity(repositoryClass: LayoutRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Layout extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'layout',
    ];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $associatedEntitiesDisplay = 'slider';

    #[ORM\OneToMany(targetEntity: Zone::class, mappedBy: 'layout', cascade: ['persist'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private ArrayCollection|PersistentCollection $zones;

    #[ORM\ManyToOne(targetEntity: LayoutConfiguration::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?LayoutConfiguration $configuration = null;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    /**
     * Layout constructor.
     */
    public function __construct()
    {
        $this->zones = new ArrayCollection();
    }

    /**
     * Get parent entity.
     */
    public function getParent(EntityManagerInterface $entityManager): ?object
    {
        $metasData = $entityManager->getMetadataFactory()->getAllMetadata();
        foreach ($metasData as $metadata) {
            $classname = $metadata->getName();
            $baseEntity = 0 === $metadata->getReflectionClass()->getModifiers() ? new $classname() : null;
            if (is_object($baseEntity) && method_exists($baseEntity, 'getLayout')) {
                $parent = $entityManager->getRepository($classname)->findOneBy(['layout' => $this]);
                if ($parent && method_exists($parent, 'setUpdatedAt')) {
                    return $parent;
                }
            }
        }

        return null;
    }

    /**
     * Set parent entity.
     *
     * @throws Exception
     */
    public function setParent(EntityManagerInterface $entityManager): void
    {
        $parent = $this->getParent($entityManager);
        if (is_object($parent) && method_exists($parent, 'getLayout')) {
            $parent->setUpdatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
            $entityManager->persist($parent);
        }
    }

    public function getAssociatedEntitiesDisplay(): ?string
    {
        return $this->associatedEntitiesDisplay;
    }

    public function setAssociatedEntitiesDisplay(?string $associatedEntitiesDisplay): static
    {
        $this->associatedEntitiesDisplay = $associatedEntitiesDisplay;

        return $this;
    }

    /**
     * @return Collection<int, Zone>
     */
    public function getZones(): Collection
    {
        return $this->zones;
    }

    public function addZone(Zone $zone): static
    {
        if (!$this->zones->contains($zone)) {
            $this->zones->add($zone);
            $zone->setLayout($this);
        }

        return $this;
    }

    public function removeZone(Zone $zone): static
    {
        if ($this->zones->removeElement($zone)) {
            // set the owning side to null (unless already changed)
            if ($zone->getLayout() === $this) {
                $zone->setLayout(null);
            }
        }

        return $this;
    }

    public function getConfiguration(): ?LayoutConfiguration
    {
        return $this->configuration;
    }

    public function setConfiguration(?LayoutConfiguration $configuration): static
    {
        $this->configuration = $configuration;

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
