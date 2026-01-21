<?php

declare(strict_types=1);

namespace App\Entity\Module\Faq;

use App\Entity\BaseMediaRelation;
use App\Repository\Module\Faq\FaqMediaRelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * FaqMediaRelation.
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
#[ORM\Table(name: 'module_faq_media_relations')]
#[ORM\Entity(repositoryClass: FaqMediaRelationRepository::class)]
class FaqMediaRelation extends BaseMediaRelation
{
    #[ORM\ManyToOne(targetEntity: Faq::class, cascade: ['persist'], inversedBy: 'mediaRelations')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Faq $faq = null;

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
