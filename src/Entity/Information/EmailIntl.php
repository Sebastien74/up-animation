<?php

declare(strict_types=1);

namespace App\Entity\Information;

use App\Entity\BaseIntl;
use App\Repository\Information\EmailIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * EmailIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'information_email_intls')]
#[ORM\Entity(repositoryClass: EmailIntlRepository::class)]
class EmailIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Email::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Email $email = null;

    public function getEmail(): ?Email
    {
        return $this->email;
    }

    public function setEmail(?Email $email): static
    {
        $this->email = $email;

        return $this;
    }
}
