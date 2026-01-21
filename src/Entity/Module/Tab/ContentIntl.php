<?php

declare(strict_types=1);

namespace App\Entity\Module\Tab;

use App\Entity\BaseIntl;
use App\Repository\Module\Tab\ContentIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * ContentIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_tab_content_intls')]
#[ORM\Entity(repositoryClass: ContentIntlRepository::class)]
class ContentIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Content::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Content $content = null;

    public function getContent(): ?Content
    {
        return $this->content;
    }

    public function setContent(?Content $content): static
    {
        $this->content = $content;

        return $this;
    }
}
