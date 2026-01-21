<?php

declare(strict_types=1);

namespace App\Entity\Module\Search;

use App\Entity\BaseEntity;
use App\Repository\Module\Search\SearchValueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * SearchValue.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_search_value')]
#[ORM\Entity(repositoryClass: SearchValueRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SearchValue extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'search';
    protected static array $interface = [
        'name' => 'searchvalue',
    ];

    #[ORM\Column(type: Types::TEXT)]
    private ?string $text = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private int $counter = 0;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private int $resultCount = 0;

    #[ORM\ManyToOne(targetEntity: Search::class, inversedBy: 'values')]
    private ?Search $search = null;

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getCounter(): ?int
    {
        return $this->counter;
    }

    public function setCounter(?int $counter): static
    {
        $this->counter = $counter;

        return $this;
    }

    public function getResultCount(): ?int
    {
        return $this->resultCount;
    }

    public function setResultCount(?int $resultCount): static
    {
        $this->resultCount = $resultCount;

        return $this;
    }

    public function getSearch(): ?Search
    {
        return $this->search;
    }

    public function setSearch(?Search $search): static
    {
        $this->search = $search;

        return $this;
    }
}
