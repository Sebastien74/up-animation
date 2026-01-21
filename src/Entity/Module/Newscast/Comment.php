<?php

declare(strict_types=1);

namespace App\Entity\Module\Newscast;

use App\Entity\BaseEntity;
use App\Repository\Module\Newscast\CommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Comment.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_newscast_comment')]
#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Comment extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'newscast';
    protected static array $interface = [
        'name' => 'newscastcomment',
    ];

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $authorName = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\ManyToOne(targetEntity: Newscast::class, inversedBy: 'comments')]
    private ?Newscast $newscast = null;

    public function getAuthorName(): ?string
    {
        return $this->authorName;
    }

    public function setAuthorName(string $authorName): static
    {
        $this->authorName = $authorName;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getNewscast(): ?Newscast
    {
        return $this->newscast;
    }

    public function setNewscast(?Newscast $newscast): static
    {
        $this->newscast = $newscast;

        return $this;
    }
}
