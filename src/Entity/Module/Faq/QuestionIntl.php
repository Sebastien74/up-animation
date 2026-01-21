<?php

declare(strict_types=1);

namespace App\Entity\Module\Faq;

use App\Entity\BaseIntl;
use App\Repository\Module\Faq\QuestionIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * QuestionIntl.
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
#[ORM\Table(name: 'module_faq_question_intls')]
#[ORM\Entity(repositoryClass: QuestionIntlRepository::class)]
class QuestionIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Question::class, cascade: ['persist'], inversedBy: 'intls')]
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
