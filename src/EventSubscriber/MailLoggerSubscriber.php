<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Core\MailLog;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\FailedMessageEvent;
use Symfony\Component\Mailer\Event\SentMessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Persists a MailLog entry for every email dispatched through the Symfony Mailer,
 * regardless of which service originated the send. Failures during persistence are
 * swallowed so a logging error never breaks delivery.
 */
final class MailLoggerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SentMessageEvent::class => 'onSent',
            FailedMessageEvent::class => 'onFailed',
        ];
    }

    public function onSent(SentMessageEvent $event): void
    {
        $sent = $event->getMessage();
        $original = $sent->getOriginalMessage();
        if (!$original instanceof Email) {
            return;
        }

        $this->persist(
            $original,
            MailLog::STATUS_SUCCESS,
            null,
            $sent->getMessageId() ?: $this->readMessageIdHeader($original),
        );
    }

    public function onFailed(FailedMessageEvent $event): void
    {
        $message = $event->getMessage();
        if (!$message instanceof Email) {
            return;
        }

        $this->persist(
            $message,
            MailLog::STATUS_FAILED,
            $event->getError()->getMessage(),
            $this->readMessageIdHeader($message),
        );
    }

    private function persist(Email $email, string $status, ?string $error, ?string $messageId): void
    {
        try {
            $recipients = $this->extractAddresses($email->getTo());
            if (empty($recipients)) {
                return;
            }

            $from = $email->getFrom()[0] ?? null;
            $replyTo = $this->extractAddresses($email->getReplyTo());
            $cc = $this->extractAddresses($email->getCc()) ?: null;
            $template = $email instanceof TemplatedEmail ? $email->getHtmlTemplate() : null;
            $locale = $email instanceof TemplatedEmail ? ($email->getContext()['locale'] ?? null) : null;
            $attachments = $this->extractAttachmentNames($email);

            $htmlBody = is_string($email->getHtmlBody()) ? $email->getHtmlBody() : null;
            $textBody = is_string($email->getTextBody()) ? $email->getTextBody() : null;

            foreach ($recipients as $recipient) {
                $log = (new MailLog())
                    ->setStatus($status)
                    ->setFromEmail($from?->getAddress())
                    ->setFromName($from?->getName() ?: null)
                    ->setToEmails([$recipient])
                    ->setCcEmails($cc)
                    ->setReplyTo($replyTo[0] ?? null)
                    ->setSubject($email->getSubject())
                    ->setHtmlBody($htmlBody)
                    ->setTextBody($textBody)
                    ->setAttachments($attachments ?: null)
                    ->setTemplate($template)
                    ->setLocale(is_string($locale) ? $locale : null)
                    ->setMessageId($messageId)
                    ->setErrorMessage($error);
                $this->entityManager->persist($log);
            }
            $this->entityManager->flush();
        } catch (\Throwable $exception) {
            $this->logger->error('MailLog persistence failed: '.$exception->getMessage());
        }
    }

    /**
     * @param Address[] $addresses
     *
     * @return string[]
     */
    private function extractAddresses(array $addresses): array
    {
        return array_values(array_map(static fn (Address $address): string => $address->getAddress(), $addresses));
    }

    /**
     * @return string[]
     */
    private function extractAttachmentNames(Email $email): array
    {
        $names = [];
        foreach ($email->getAttachments() as $part) {
            $name = $part->getPreparedHeaders()->getHeaderParameter('Content-Disposition', 'filename');
            if (is_string($name) && '' !== $name) {
                $names[] = $name;
            }
        }

        return $names;
    }

    private function readMessageIdHeader(Email $email): ?string
    {
        $headers = $email->getHeaders();
        if (!$headers->has('Message-ID')) {
            return null;
        }

        return $headers->get('Message-ID')?->getBodyAsString();
    }
}
