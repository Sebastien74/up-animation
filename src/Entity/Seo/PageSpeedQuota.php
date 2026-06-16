<?php

declare(strict_types=1);

namespace App\Entity\Seo;

use App\Repository\Seo\PageSpeedQuotaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * PageSpeedQuota.
 *
 * Daily counter of Google PageSpeed Insights API requests consumed (one row per day),
 * used to guard the admin tools against exceeding the configured daily quota. One page
 * measurement consumes one request per strategy (mobile, desktop).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'seo_pagespeed_quota')]
#[ORM\UniqueConstraint(name: 'uniq_seo_pagespeed_quota_day', columns: ['day'])]
#[ORM\Entity(repositoryClass: PageSpeedQuotaRepository::class)]
class PageSpeedQuota
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $day = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $count = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDay(): ?\DateTimeImmutable
    {
        return $this->day;
    }

    public function setDay(?\DateTimeImmutable $day): static
    {
        $this->day = $day;

        return $this;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): static
    {
        $this->count = $count;

        return $this;
    }
}
