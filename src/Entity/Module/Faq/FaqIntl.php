<?php

declare(strict_types=1);

namespace App\Entity\Module\Faq;

use App\Entity\BaseIntl;
use App\Repository\Module\Faq\FaqIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * FaqIntl.
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
#[ORM\Table(name: 'module_faq_intls')]
#[ORM\Entity(repositoryClass: FaqIntlRepository::class)]
class FaqIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Faq::class, cascade: ['persist'], inversedBy: 'intls')]
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
