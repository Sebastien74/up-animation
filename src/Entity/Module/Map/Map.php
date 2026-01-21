<?php

declare(strict_types=1);

namespace App\Entity\Module\Map;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Repository\Module\Map\MapRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Map.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_map')]
#[ORM\Entity(repositoryClass: MapRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Map extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'map',
        'buttons' => [
            'points' => 'admin_mappoint_index',
        ],
    ];
    protected static array $labels = [
        'admin_mappoint_index' => 'Points',
    ];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $layer = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $height = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private int $zoom = 11;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private int $minZoom = 5;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private int $maxZoom = 20;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $latitude = '45.899247';

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $longitude = '6.129384';

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $autoCenter = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $forceZoom = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $popupHover = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $displayFilters = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $displayPointsList = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $markerClusters = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $multiFilters = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $asDefault = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $countriesGeometry = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $departmentsGeometry = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $jsonGeometry = false;

    #[ORM\OneToMany(targetEntity: Point::class, mappedBy: 'map', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $points;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    /**
     * Map constructor.
     */
    public function __construct()
    {
        $this->points = new ArrayCollection();
    }

    public function getLayer(): ?string
    {
        return $this->layer;
    }

    public function setLayer(?string $layer): static
    {
        $this->layer = $layer;

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

    public function getZoom(): ?int
    {
        return $this->zoom;
    }

    public function setZoom(?int $zoom): static
    {
        $this->zoom = $zoom;

        return $this;
    }

    public function getMinZoom(): ?int
    {
        return $this->minZoom;
    }

    public function setMinZoom(?int $minZoom): static
    {
        $this->minZoom = $minZoom;

        return $this;
    }

    public function getMaxZoom(): ?int
    {
        return $this->maxZoom;
    }

    public function setMaxZoom(?int $maxZoom): static
    {
        $this->maxZoom = $maxZoom;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(string $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(string $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function isAutoCenter(): ?bool
    {
        return $this->autoCenter;
    }

    public function setAutoCenter(bool $autoCenter): static
    {
        $this->autoCenter = $autoCenter;

        return $this;
    }

    public function isForceZoom(): ?bool
    {
        return $this->forceZoom;
    }

    public function setForceZoom(bool $forceZoom): static
    {
        $this->forceZoom = $forceZoom;

        return $this;
    }

    public function isPopupHover(): ?bool
    {
        return $this->popupHover;
    }

    public function setPopupHover(bool $popupHover): static
    {
        $this->popupHover = $popupHover;

        return $this;
    }

    public function isDisplayFilters(): ?bool
    {
        return $this->displayFilters;
    }

    public function setDisplayFilters(bool $displayFilters): static
    {
        $this->displayFilters = $displayFilters;

        return $this;
    }

    public function isDisplayPointsList(): ?bool
    {
        return $this->displayPointsList;
    }

    public function setDisplayPointsList(bool $displayPointsList): static
    {
        $this->displayPointsList = $displayPointsList;

        return $this;
    }

    public function isMarkerClusters(): ?bool
    {
        return $this->markerClusters;
    }

    public function setMarkerClusters(bool $markerClusters): static
    {
        $this->markerClusters = $markerClusters;

        return $this;
    }

    public function isMultiFilters(): ?bool
    {
        return $this->multiFilters;
    }

    public function setMultiFilters(bool $multiFilters): static
    {
        $this->multiFilters = $multiFilters;

        return $this;
    }

    public function isAsDefault(): ?bool
    {
        return $this->asDefault;
    }

    public function setAsDefault(bool $asDefault): static
    {
        $this->asDefault = $asDefault;

        return $this;
    }

    public function isCountriesGeometry(): ?bool
    {
        return $this->countriesGeometry;
    }

    public function setCountriesGeometry(bool $countriesGeometry): static
    {
        $this->countriesGeometry = $countriesGeometry;

        return $this;
    }

    public function isDepartmentsGeometry(): ?bool
    {
        return $this->departmentsGeometry;
    }

    public function setDepartmentsGeometry(bool $departmentsGeometry): static
    {
        $this->departmentsGeometry = $departmentsGeometry;

        return $this;
    }

    public function isJsonGeometry(): ?bool
    {
        return $this->jsonGeometry;
    }

    public function setJsonGeometry(bool $jsonGeometry): static
    {
        $this->jsonGeometry = $jsonGeometry;

        return $this;
    }

    /**
     * @return Collection<int, Point>
     */
    public function getPoints(): Collection
    {
        return $this->points;
    }

    public function addPoint(Point $point): static
    {
        if (!$this->points->contains($point)) {
            $this->points->add($point);
            $point->setMap($this);
        }

        return $this;
    }

    public function removePoint(Point $point): static
    {
        if ($this->points->removeElement($point)) {
            // set the owning side to null (unless already changed)
            if ($point->getMap() === $this) {
                $point->setMap(null);
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
