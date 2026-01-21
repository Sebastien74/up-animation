<?php

declare(strict_types=1);

namespace App\Entity\Module\Form;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Entity\Layout\Layout;
use App\Repository\Module\Form\FormRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Form.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_form')]
#[ORM\Entity(repositoryClass: FormRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Form extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static string $parentMasterField = 'stepform';
    protected static array $interface = [
        'name' => 'form',
        'resize' => false,
        'disabledButtons' => true,
        'buttons' => [
            'contacts' => 'admin_formcontact_index',
            //            'calendars' => 'admin_formcalendar_index'
        ],
        'rolesChecker' => [
            'admin_formcalendar_index' => 'ROLE_FORM_CALENDAR',
        ],
        'buttonsChecker' => [
            'admin_formcalendar_index' => 'configuration.calendarsActive',
        ],
    ];
    protected static array $labels = [
        'admin_formcontact_index' => 'Contacts',
        //        "admin_formcalendar_index" => "Calendriers"
    ];

    #[ORM\OneToOne(targetEntity: Configuration::class, mappedBy: 'form', cascade: ['persist', 'remove'])]
    private ?Configuration $configuration = null;

    #[ORM\OneToOne(targetEntity: Layout::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'layout_id', referencedColumnName: 'id')]
    private ?Layout $layout = null;

    #[ORM\OneToMany(targetEntity: ContactForm::class, mappedBy: 'form', cascade: ['persist'], orphanRemoval: true)]
    private ArrayCollection|PersistentCollection $contacts;

    #[ORM\OneToMany(targetEntity: Calendar::class, mappedBy: 'form', cascade: ['persist'], orphanRemoval: true)]
    private ArrayCollection|PersistentCollection $calendars;

    #[ORM\OneToMany(targetEntity: FormIntl::class, mappedBy: 'form', cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: StepForm::class, inversedBy: 'forms')]
    private ?StepForm $stepform = null;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    /**
     * Form constructor.
     */
    public function __construct()
    {
        $this->contacts = new ArrayCollection();
        $this->calendars = new ArrayCollection();
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
            $this->configuration->setForm(null);
        }

        // set the owning side of the relation if necessary
        if ($configuration !== null && $configuration->getForm() !== $this) {
            $configuration->setForm($this);
        }

        $this->configuration = $configuration;

        return $this;
    }

    public function getLayout(): ?Layout
    {
        return $this->layout;
    }

    public function setLayout(?Layout $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * @return Collection<int, ContactForm>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(ContactForm $contact): static
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts->add($contact);
            $contact->setForm($this);
        }

        return $this;
    }

    public function removeContact(ContactForm $contact): static
    {
        if ($this->contacts->removeElement($contact)) {
            // set the owning side to null (unless already changed)
            if ($contact->getForm() === $this) {
                $contact->setForm(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Calendar>
     */
    public function getCalendars(): Collection
    {
        return $this->calendars;
    }

    public function addCalendar(Calendar $calendar): static
    {
        if (!$this->calendars->contains($calendar)) {
            $this->calendars->add($calendar);
            $calendar->setForm($this);
        }

        return $this;
    }

    public function removeCalendar(Calendar $calendar): static
    {
        if ($this->calendars->removeElement($calendar)) {
            // set the owning side to null (unless already changed)
            if ($calendar->getForm() === $this) {
                $calendar->setForm(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FormIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(FormIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setForm($this);
        }

        return $this;
    }

    public function removeIntl(FormIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getForm() === $this) {
                $intl->setForm(null);
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
