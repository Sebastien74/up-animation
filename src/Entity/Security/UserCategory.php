<?php

declare(strict_types=1);

namespace App\Entity\Security;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Repository\Security\UserCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * UserCategory.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'security_user_category')]
#[ORM\Entity(repositoryClass: UserCategoryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class UserCategory extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'securityusercategory',
    ];

    #[ORM\OneToMany(targetEntity: UserFront::class, mappedBy: 'category', cascade: ['persist'], orphanRemoval: true)]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $userFronts;

    #[ORM\OneToMany(targetEntity: UserCategoryIntl::class, mappedBy: 'category', cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    /**
     * UserCategory constructor.
     */
    public function __construct()
    {
        $this->userFronts = new ArrayCollection();
        $this->intls = new ArrayCollection();
    }

    /**
     * @return Collection<int, UserFront>
     */
    public function getUserFronts(): Collection
    {
        return $this->userFronts;
    }

    public function addUserFront(UserFront $userFront): static
    {
        if (!$this->userFronts->contains($userFront)) {
            $this->userFronts->add($userFront);
            $userFront->setCategory($this);
        }

        return $this;
    }

    public function removeUserFront(UserFront $userFront): static
    {
        if ($this->userFronts->removeElement($userFront)) {
            // set the owning side to null (unless already changed)
            if ($userFront->getCategory() === $this) {
                $userFront->setCategory(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserCategoryIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(UserCategoryIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setCategory($this);
        }

        return $this;
    }

    public function removeIntl(UserCategoryIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getCategory() === $this) {
                $intl->setCategory(null);
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
