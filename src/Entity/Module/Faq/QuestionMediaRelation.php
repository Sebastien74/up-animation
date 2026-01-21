<?php

declare(strict_types=1);

namespace App\Entity\Module\Faq;

use App\Entity\BaseMediaRelation;
use App\Repository\Module\Faq\QuestionMediaRelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * QuestionMediaRelation.
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
#[ORM\Table(name: 'module_faq_question_media_relations')]
#[ORM\Entity(repositoryClass: QuestionMediaRelationRepository::class)]
class QuestionMediaRelation extends BaseMediaRelation
{
    #[ORM\ManyToOne(targetEntity: Question::class, cascade: ['persist'], inversedBy: 'mediaRelations')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Question $question = null;

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): static
    {
        $this->question = $question;

        return $this;
    }
}
