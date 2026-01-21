<?php

declare(strict_types=1);

namespace App\Entity\Module\Recruitment;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Entity\Module\Form\Form;
use App\Entity\Seo\Url;
use App\Repository\Module\Recruitment\JobRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Job.
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
#[ORM\Table(name: 'module_recruitment_job')]
#[ORM\Entity(repositoryClass: JobRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'urls',
        joinColumns: [new ORM\JoinColumn(name: 'job_id', referencedColumnName: 'id', onDelete: 'cascade')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'url_id', referencedColumnName: 'id', unique: true, onDelete: 'cascade')],
        joinTable: new ORM\JoinTable(name: 'module_recruitment_job_urls')
    ),
])]
class Job extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'recruitmentjob',
        'card' => true,
        'search' => true,
        'resize' => true,
        'indexPage' => 'website',
        'listingClass' => Listing::class,
        'seo' => [
            'intl.title',
            'intl.introduction',
            'intl.body',
        ],
    ];
    protected static array $labels = [
        'intl.title' => 'Titre',
        'intl.introduction' => 'Introduction',
        'intl.body' => 'Description',
    ];

    #[ORM\Column(type: 'boolean')]
    private bool $promote = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $place = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Assert\Length(max: 20)]
    private ?string $zipCode = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $department = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $remuneration = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $publicationStart = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $publicationEnd = null;

    #[ORM\ManyToOne(targetEntity: Contract::class)]
    #[ORM\JoinColumn(name: 'contract_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Assert\Valid]
    private ?Contract $contract = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Assert\Valid]
    private ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: Form::class)]
    #[ORM\JoinColumn(name: 'form_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Assert\Valid]
    private ?Form $form = null;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    #[ORM\OneToMany(mappedBy: 'job', targetEntity: JobIntl::class, cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToMany(targetEntity: Url::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid]
    private ArrayCollection|PersistentCollection $urls;

    /**
     * Job constructor.
     */
    public function __construct()
    {
        $this->urls = new ArrayCollection();
        $this->intls = new ArrayCollection();
    }

    /**
     * @throws \Exception
     */
    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if (empty($this->publicationStart)) {
            $this->publicationStart = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        }
        parent::prePersist();
    }

    public function isPromote(): ?bool
    {
        return $this->promote;
    }

    public function setPromote(bool $promote): static
    {
        $this->promote = $promote;

        return $this;
    }

    public function getPlace(): ?string
    {
        return $this->place;
    }

    public function setPlace(?string $place): static
    {
        $this->place = $place;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): static
    {
        $this->department = $department;

        return $this;
    }

    public function getRemuneration(): ?string
    {
        return $this->remuneration;
    }

    public function setRemuneration(?string $remuneration): static
    {
        $this->remuneration = $remuneration;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getPublicationStart(): ?\DateTimeInterface
    {
        return $this->publicationStart;
    }

    public function setPublicationStart(?\DateTimeInterface $publicationStart): static
    {
        $this->publicationStart = $publicationStart;

        return $this;
    }

    public function getPublicationEnd(): ?\DateTimeInterface
    {
        return $this->publicationEnd;
    }

    public function setPublicationEnd(?\DateTimeInterface $publicationEnd): static
    {
        $this->publicationEnd = $publicationEnd;

        return $this;
    }

    public function getContract(): ?Contract
    {
        return $this->contract;
    }

    public function setContract(?Contract $contract): static
    {
        $this->contract = $contract;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getForm(): ?Form
    {
        return $this->form;
    }

    public function setForm(?Form $form): static
    {
        $this->form = $form;

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

    /**
     * @return Collection<int, JobIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(JobIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setJob($this);
        }

        return $this;
    }

    public function removeIntl(JobIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getJob() === $this) {
                $intl->setJob(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Url>
     */
    public function getUrls(): Collection
    {
        return $this->urls;
    }

    public function addUrl(Url $url): static
    {
        if (!$this->urls->contains($url)) {
            $this->urls->add($url);
        }

        return $this;
    }

    public function removeUrl(Url $url): static
    {
        $this->urls->removeElement($url);

        return $this;
    }
}
