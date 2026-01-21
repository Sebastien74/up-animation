<?php

declare(strict_types=1);

namespace App\Entity\Security;

use App\Entity\BaseIntl;
use App\Repository\Security\MessageIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * MessageIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'security_message_intls')]
#[ORM\Entity(repositoryClass: MessageIntlRepository::class)]
class MessageIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Message::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Message $message = null;

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function setMessage(?Message $message): static
    {
        $this->message = $message;

        return $this;
    }
}
