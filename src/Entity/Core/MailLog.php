<?php

declare(strict_types=1);

namespace App\Entity\Core;

use App\Entity\BaseInterface;
use App\Repository\Core\MailLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * MailLog.
 *
 * Persistent record of every email handled by MailerService.
 * Stores both successful and failed deliveries for audit, debug
 * and admin dashboard statistics.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'core_mail_log')]
#[ORM\Entity(repositoryClass: MailLogRepository::class)]
#[ORM\Index(name: 'idx_mail_log_created_at', columns: ['createdAt'])]
#[ORM\Index(name: 'idx_mail_log_status', columns: ['status'])]
#[ORM\HasLifecycleCallbacks]
class MailLog extends BaseInterface
{
    public const string STATUS_SUCCESS = 'success';
    public const string STATUS_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $status = self::STATUS_SUCCESS;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $fromEmail = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $fromName = null;

    #[ORM\Column(type: Types::JSON)]
    private array $toEmails = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $ccEmails = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $replyTo = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $subject = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $htmlBody = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $textBody = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $attachments = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $template = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    private ?string $locale = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $messageId = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isSuccess(): bool
    {
        return self::STATUS_SUCCESS === $this->status;
    }

    public function getFromEmail(): ?string
    {
        return $this->fromEmail;
    }

    public function setFromEmail(?string $fromEmail): static
    {
        $this->fromEmail = $fromEmail;

        return $this;
    }

    public function getFromName(): ?string
    {
        return $this->fromName;
    }

    public function setFromName(?string $fromName): static
    {
        $this->fromName = $fromName;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getToEmails(): array
    {
        return $this->toEmails;
    }

    /**
     * @param string[] $toEmails
     */
    public function setToEmails(array $toEmails): static
    {
        $this->toEmails = array_values($toEmails);

        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getCcEmails(): ?array
    {
        return $this->ccEmails;
    }

    /**
     * @param string[]|null $ccEmails
     */
    public function setCcEmails(?array $ccEmails): static
    {
        $this->ccEmails = $ccEmails ? array_values($ccEmails) : null;

        return $this;
    }

    public function getReplyTo(): ?string
    {
        return $this->replyTo;
    }

    public function setReplyTo(?string $replyTo): static
    {
        $this->replyTo = $replyTo;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getHtmlBody(): ?string
    {
        return $this->htmlBody;
    }

    public function setHtmlBody(?string $htmlBody): static
    {
        $this->htmlBody = $htmlBody;

        return $this;
    }

    public function getTextBody(): ?string
    {
        return $this->textBody;
    }

    public function setTextBody(?string $textBody): static
    {
        $this->textBody = $textBody;

        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    /**
     * @param string[]|null $attachments
     */
    public function setAttachments(?array $attachments): static
    {
        $this->attachments = $attachments ? array_values($attachments) : null;

        return $this;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function setTemplate(?string $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    public function setMessageId(?string $messageId): static
    {
        $this->messageId = $messageId;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }
}