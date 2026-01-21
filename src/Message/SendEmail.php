<?php

declare(strict_types=1);

namespace App\Message;

use App\Entity\Core\Website;
use App\Model\Core\WebsiteModel;
use Symfony\Component\Messenger\Attribute\AsMessage;

/**
 * SendEmail.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsMessage('async')]
class SendEmail
{
    /**
     * SendEmail constructor.
     */
    public function __construct(
        public ?string $locale = null,
        public ?string $subject = null,
        public ?array $to = [],
        public ?array $cc = [],
        public ?string $name = null,
        public ?string $from = null,
        public ?string $replyTo = null,
        public ?string $template = null,
        public ?array $arguments = [],
        public ?array $attachments = [],
        public ?int $websiteId = null,
    ) {
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = strip_tags($subject);
    }

    public function getTo(): array
    {
        return $this->to;
    }

    public function setTo(array $to): void
    {
        $this->to = $to;
    }

    public function getCc(): array
    {
        return $this->cc;
    }

    public function setCc(array $cc): void
    {
        $this->cc = $cc;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function setFrom(string $from): void
    {
        $this->from = $from;
    }

    public function getReplyTo(): string
    {
        return $this->replyTo;
    }

    public function setReplyTo(string $replyTo): void
    {
        $this->replyTo = $replyTo;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function setArguments(array $arguments): void
    {
        $this->arguments = $arguments;
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function setAttachments(array $attachments): void
    {
        $this->attachments = $attachments;
    }

    public function getWebsiteId(): int
    {
        return $this->websiteId;
    }

    public function setWebsite(mixed $website): void
    {
        $this->websiteId = $website instanceof Website ? $website->getId()
            : ($website instanceof WebsiteModel ? $website->id : null);
    }
}
