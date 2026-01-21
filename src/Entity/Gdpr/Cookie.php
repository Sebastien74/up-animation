<?php

declare(strict_types=1);

namespace App\Entity\Gdpr;

use App\Entity\BaseEntity;
use App\Repository\Gdpr\CookieRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Cookie.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'gdpr_cookie')]
#[ORM\Entity(repositoryClass: CookieRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Cookie extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'gdprgroup';
    protected static array $interface = [
        'name' => 'gdprcookie',
    ];

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $code = null;

    #[ORM\ManyToOne(targetEntity: Group::class, cascade: ['persist'], inversedBy: 'gdprcookies')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private ?Group $gdprgroup = null;

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getGdprgroup(): ?Group
    {
        return $this->gdprgroup;
    }

    public function setGdprgroup(?Group $gdprgroup): static
    {
        $this->gdprgroup = $gdprgroup;

        return $this;
    }
}
