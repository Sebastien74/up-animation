<?php

declare(strict_types=1);

namespace App\Entity\Layout;

use App\Entity\BaseInterface;
use App\Entity\Core\Configuration;
use App\Repository\Layout\CssClassRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * CssClass.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'layout_css_class')]
#[ORM\Entity(repositoryClass: CssClassRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CssClass extends BaseInterface
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'cssclass',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Configuration::class, inversedBy: 'cssClasses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Configuration $configuration = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getConfiguration(): ?Configuration
    {
        return $this->configuration;
    }

    public function setConfiguration(?Configuration $configuration): static
    {
        $this->configuration = $configuration;

        return $this;
    }
}
