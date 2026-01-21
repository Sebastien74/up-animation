<?php

declare(strict_types=1);

namespace App\Entity\Module\Form;

use App\Entity\BaseInterface;
use App\Repository\Module\Form\ContactStepFormRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * ContactStepForm.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_form_step_contact')]
#[ORM\Entity(repositoryClass: ContactStepFormRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ContactStepForm extends BaseInterface
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'stepform';
    protected static array $interface = [
        'name' => 'contactstepform',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $email = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $token = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $tokenExpired = false;

    #[ORM\OneToMany(targetEntity: ContactValue::class, mappedBy: 'contactStepForm', cascade: ['persist'])]
    private ArrayCollection|PersistentCollection $contactValues;

    #[ORM\ManyToOne(targetEntity: StepForm::class, inversedBy: 'contacts')]
    #[ORM\JoinColumn(nullable: true)]
    private ?StepForm $stepform = null;

    /**
     * ContactStepForm constructor.
     */
    public function __construct()
    {
        $this->contactValues = new ArrayCollection();
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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

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

    public function isTokenExpired(): ?bool
    {
        return $this->tokenExpired;
    }

    public function setTokenExpired(bool $tokenExpired): static
    {
        $this->tokenExpired = $tokenExpired;

        return $this;
    }

    /**
     * @return Collection<int, ContactValue>
     */
    public function getContactValues(): Collection
    {
        return $this->contactValues;
    }

    public function addContactValue(ContactValue $contactValue): static
    {
        if (!$this->contactValues->contains($contactValue)) {
            $this->contactValues->add($contactValue);
            $contactValue->setContactStepForm($this);
        }

        return $this;
    }

    public function removeContactValue(ContactValue $contactValue): static
    {
        if ($this->contactValues->removeElement($contactValue)) {
            // set the owning side to null (unless already changed)
            if ($contactValue->getContactStepForm() === $this) {
                $contactValue->setContactStepForm(null);
            }
        }

        return $this;
    }

    public function getStepform(): ?StepForm
    {
        return $this->stepform;
    }

    public function setStepform(?StepForm $stepform): static
    {
        $this->stepform = $stepform;

        return $this;
    }
}
