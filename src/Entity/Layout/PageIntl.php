<?php

declare(strict_types=1);

namespace App\Entity\Layout;

use App\Entity\BaseIntl;
use App\Repository\Layout\PageIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * PageIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'layout_page_intls')]
#[ORM\Entity(repositoryClass: PageIntlRepository::class)]
class PageIntl extends BaseIntl
{
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $countersData = [];

    #[ORM\ManyToOne(targetEntity: Page::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Page $page = null;

    public function getCountersData(): ?array
    {
        return $this->countersData;
    }

    public function setCountersData(?array $countersData): static
    {
        $this->countersData = $countersData;

        return $this;
    }

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function setPage(?Page $page): static
    {
        $this->page = $page;

        return $this;
    }
}
