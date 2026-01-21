<?php

declare(strict_types=1);

namespace App\Entity\Module\Contact;

use App\Entity\BaseIntl;
use App\Repository\Module\Contact\ContactIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * ContactIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_contact_intls')]
#[ORM\Entity(repositoryClass: ContactIntlRepository::class)]
class ContactIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Contact::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Contact $contact = null;

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(?Contact $contact): static
    {
        $this->contact = $contact;

        return $this;
    }
}
