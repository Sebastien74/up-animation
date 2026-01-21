<?php

declare(strict_types=1);

namespace App\Entity\Module\Newsletter;

use App\Entity\BaseInterface;
use App\Repository\Module\Newsletter\EmailRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Email.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_newsletter_email')]
#[ORM\Entity(repositoryClass: EmailRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Email extends BaseInterface
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'campaign';
    protected static array $interface = [
        'name' => 'newsletteremail',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $email = null;

    #[ORM\Column(type: Types::STRING, length: 10)]
    private ?string $locale = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $token = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $tokenDate = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $accept = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $acceptDate = null;

    #[ORM\ManyToOne(targetEntity: Campaign::class, inversedBy: 'emails')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Campaign $campaign = null;

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->tokenDate = new \DateTime('now', new \DateTimeZone('Europe/Paris'));

        parent::prePersist();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function setTokenDate(?\DateTimeInterface $tokenDate): static
    {
        $this->tokenDate = $tokenDate;
        return $this;
    }

    public function getTokenDate(): ?\DateTimeInterface
    {
        return $this->tokenDate;
    }

    public function isAccept(): ?bool
    {
        return $this->accept;
    }

    public function setAccept(bool $accept): static
    {
        $this->accept = $accept;

        return $this;
    }

    public function setAcceptDate(?\DateTimeInterface $acceptDate): static
    {
        $this->acceptDate = $acceptDate;
        return $this;
    }

    public function getAcceptDate(): ?\DateTimeInterface
    {
        return $this->acceptDate;
    }

    public function getCampaign(): ?Campaign
    {
        return $this->campaign;
    }

    public function setCampaign(?Campaign $campaign): static
    {
        $this->campaign = $campaign;

        return $this;
    }
}
