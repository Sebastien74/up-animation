<?php

declare(strict_types=1);

namespace App\Entity\Module\Faq;

use App\Entity\BaseEntity;
use App\Repository\Module\Faq\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Question.
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
#[ORM\Table(name: 'module_faq_question')]
#[ORM\Entity(repositoryClass: QuestionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Question extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'faq';
    protected static array $interface = [
        'name' => 'faqquestion',
        'search' => true,
    ];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $promote = false;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $pictogram = null;

    #[ORM\OneToMany(targetEntity: QuestionIntl::class, mappedBy: 'question', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\OneToMany(targetEntity: QuestionMediaRelation::class, mappedBy: 'question', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid]
    private ArrayCollection|PersistentCollection $mediaRelations;

    #[ORM\ManyToOne(targetEntity: Faq::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Faq $faq = null;

    /**
     * Question constructor.
     */
    public function __construct()
    {
        $this->intls = new ArrayCollection();
        $this->mediaRelations = new ArrayCollection();
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

    public function getPictogram(): ?string
    {
        return $this->pictogram;
    }

    public function setPictogram(?string $pictogram): static
    {
        $this->pictogram = $pictogram;

        return $this;
    }

    /**
     * @return Collection<int, QuestionIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(QuestionIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setQuestion($this);
        }

        return $this;
    }

    public function removeIntl(QuestionIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getQuestion() === $this) {
                $intl->setQuestion(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, QuestionMediaRelation>
     */
    public function getMediaRelations(): Collection
    {
        return $this->mediaRelations;
    }

    public function addMediaRelation(QuestionMediaRelation $mediaRelation): static
    {
        if (!$this->mediaRelations->contains($mediaRelation)) {
            $this->mediaRelations->add($mediaRelation);
            $mediaRelation->setQuestion($this);
        }

        return $this;
    }

    public function removeMediaRelation(QuestionMediaRelation $mediaRelation): static
    {
        if ($this->mediaRelations->removeElement($mediaRelation)) {
            // set the owning side to null (unless already changed)
            if ($mediaRelation->getQuestion() === $this) {
                $mediaRelation->setQuestion(null);
            }
        }

        return $this;
    }

    public function getFaq(): ?Faq
    {
        return $this->faq;
    }

    public function setFaq(?Faq $faq): static
    {
        $this->faq = $faq;

        return $this;
    }
}
