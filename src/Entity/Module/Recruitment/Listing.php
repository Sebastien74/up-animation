<?php

declare(strict_types=1);

namespace App\Entity\Module\Recruitment;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Repository\Module\Recruitment\ListingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Listing
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
#[ORM\Table(name: 'module_recruitment_listing')]
#[ORM\Entity(repositoryClass: ListingRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Listing extends BaseEntity
{
	/**
	 * Configurations
	 */
	protected static string $masterField = 'website';
	protected static array $interface = [
		'name' => 'recruitmentlisting'
	];

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    private string $orderBy = 'publicationStart';

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    private string $orderSort = 'DESC';

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $displayFilters = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $displayPromote = true;

	#[ORM\ManyToOne(targetEntity: Website::class)]
	#[ORM\JoinColumn(nullable: false)]
	private ?Website $website = null;

    public function getOrderBy(): ?string
    {
        return $this->orderBy;
    }

    public function setOrderBy(string $orderBy): static
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    public function getOrderSort(): ?string
    {
        return $this->orderSort;
    }

    public function setOrderSort(string $orderSort): static
    {
        $this->orderSort = $orderSort;

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

    public function isDisplayPromote(): ?bool
    {
        return $this->displayPromote;
    }

    public function setDisplayPromote(bool $displayPromote): static
    {
        $this->displayPromote = $displayPromote;

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
