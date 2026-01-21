<?php

declare(strict_types=1);

namespace App\Entity\Security;

use App\Entity\BaseSecurity;
use App\Entity\Core\Website;
use App\Repository\Security\UserFrontRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * UserFront.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'security_user_front')]
#[ORM\Entity(repositoryClass: UserFrontRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(
    fields: ['website', 'email'],
    message: '',
    errorPath: 'email'
)]
#[UniqueEntity(
    fields: ['website', 'login'],
    message: '',
    errorPath: 'login'
)]
class UserFront extends BaseSecurity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'userfront',
    ];
    protected static array $labels = [];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $facebookId = null;

    #[ORM\OneToOne(targetEntity: Picture::class, inversedBy: 'userFront', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'cascade')]
    private ?Picture $picture = null;

    #[ORM\OneToOne(targetEntity: Profile::class, inversedBy: 'userFront', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'cascade')]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?Profile $profile = null;

    #[ORM\OneToOne(targetEntity: UserRequest::class, inversedBy: 'userFront', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?UserRequest $userRequest = null;

    #[ORM\ManyToOne(targetEntity: UserCategory::class, cascade: ['persist'], inversedBy: 'userFronts')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?UserCategory $category = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Company $company = null;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFacebookId(): ?string
    {
        return $this->facebookId;
    }

    public function setFacebookId(?string $facebookId): static
    {
        $this->facebookId = $facebookId;

        return $this;
    }

    public function getPicture(): ?Picture
    {
        return $this->picture;
    }

    public function setPicture(?Picture $picture): static
    {
        $this->picture = $picture;

        return $this;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setProfile(?Profile $profile): static
    {
        $this->profile = $profile;

        return $this;
    }

    public function getUserRequest(): ?UserRequest
    {
        return $this->userRequest;
    }

    public function setUserRequest(?UserRequest $userRequest): static
    {
        $this->userRequest = $userRequest;

        return $this;
    }

    public function getCategory(): ?UserCategory
    {
        return $this->category;
    }

    public function setCategory(?UserCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

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
