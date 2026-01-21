<?php

declare(strict_types=1);

namespace App\Entity\Module\Form;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Repository\Module\Form\StepFormRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * StepForm.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_form_step')]
#[ORM\Entity(repositoryClass: StepFormRepository::class)]
#[ORM\HasLifecycleCallbacks]
class StepForm extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'stepform',
        'buttons' => [
            'admin_form_index',
            'admin_contactstepform_index',
        ],
    ];
    protected static array $labels = [
        'admin_form_index' => 'Formulaires',
        'admin_contactstepform_index' => 'Contacts',
    ];

    #[ORM\OneToOne(targetEntity: Configuration::class, mappedBy: 'stepform', cascade: ['persist', 'remove'])]
    private ?Configuration $configuration = null;

    #[ORM\OneToMany(targetEntity: Form::class, mappedBy: 'stepform', orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private ArrayCollection|PersistentCollection $forms;

    #[ORM\OneToMany(targetEntity: ContactStepForm::class, mappedBy: 'stepform', cascade: ['persist'], orphanRemoval: true)]
    private ArrayCollection|PersistentCollection $contacts;

    #[ORM\OneToMany(targetEntity: StepFormIntl::class, mappedBy: 'stepForm', cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    /**
     * StepForm constructor.
     */
    public function __construct()
    {
        $this->forms = new ArrayCollection();
        $this->contacts = new ArrayCollection();
        $this->intls = new ArrayCollection();
    }

    public function getConfiguration(): ?Configuration
    {
        return $this->configuration;
    }

    public function setConfiguration(?Configuration $configuration): static
    {
        // unset the owning side of the relation if necessary
        if ($configuration === null && $this->configuration !== null) {
            $this->configuration->setStepform(null);
        }

        // set the owning side of the relation if necessary
        if ($configuration !== null && $configuration->getStepform() !== $this) {
            $configuration->setStepform($this);
        }

        $this->configuration = $configuration;

        return $this;
    }

    /**
     * @return Collection<int, Form>
     */
    public function getForms(): Collection
    {
        return $this->forms;
    }

    public function addForm(Form $form): static
    {
        if (!$this->forms->contains($form)) {
            $this->forms->add($form);
            $form->setStepform($this);
        }

        return $this;
    }

    public function removeForm(Form $form): static
    {
        if ($this->forms->removeElement($form)) {
            // set the owning side to null (unless already changed)
            if ($form->getStepform() === $this) {
                $form->setStepform(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ContactStepForm>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(ContactStepForm $contact): static
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts->add($contact);
            $contact->setStepform($this);
        }

        return $this;
    }

    public function removeContact(ContactStepForm $contact): static
    {
        if ($this->contacts->removeElement($contact)) {
            // set the owning side to null (unless already changed)
            if ($contact->getStepform() === $this) {
                $contact->setStepform(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StepFormIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(StepFormIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setStepForm($this);
        }

        return $this;
    }

    public function removeIntl(StepFormIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getStepForm() === $this) {
                $intl->setStepForm(null);
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
