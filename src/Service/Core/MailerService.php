<?php

declare(strict_types=1);

namespace App\Service\Core;

use App\Model\Core\WebsiteModel;
use App\Service\Interface\CoreLocatorInterface;
use Exception;
use ForceUTF8\Encoding;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Random\RandomException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * MailerService.
 *
 * To send email
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 *
 * @doc https://symfony.com/doc/current/mailer.html
 */
class MailerService
{
    private const bool SET_HEADER = true;
    private const int MAX_ATTEMPTS = 2;
    private const int RETRY_DELAY_MS = 500;

    private ?WebsiteModel $website = null;
    private ?string $envName;
    private ?string $subject = null;
    private ?string $name = 'Up Animations!';
    private ?string $from = null;
    private bool $fromSet = false;
    private array $to = [];
    private array $cc = [];
    private ?string $replyTo = null;
    private ?string $baseTemplate = 'base';
    private ?string $template = 'core/email/email.html.twig';
    private array $arguments = [];
    private array $attachments = [];
    private ?string $locale = null;
    private ?string $host = null;
    private ?string $schemeAndHttpHost = null;

    /**
     * MailerService constructor.
     */
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $templating,
        private readonly CoreLocatorInterface $coreLocator,
    ) {
        $this->envName = 'prod' !== $_ENV['APP_ENV'] ? strtoupper($_ENV['APP_ENV']) : null;
    }

    /**
     * Send email.
     *
     * @throws Exception
     */
    public function send(): object
    {
        $this->default();

        $logger = new Logger('symfony_mailer');
        $logger->pushHandler(new RotatingFileHandler($this->coreLocator->logDir().'/mailer.log', 10, Level::Info));
        $criticalLogger = new Logger('symfony_mailer');
        $criticalLogger->pushHandler(new RotatingFileHandler($this->coreLocator->logDir().'/mailer-critical.log', 10, Level::Critical));

        $sent = [];
        $failed = [];

        try {
            $information = $this->website?->information;
            if (empty($this->to) && is_object($information) && property_exists($information, 'email') && $information->email) {
                $this->to = [$information->email];
            }

            if (!$this->from || !filter_var($this->from, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Invalid or missing sender address: '.($this->from ?: 'NULL'));
            }

            foreach ($this->to as $key => $emailAddress) {
                if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
                    unset($this->to[$key]);
                    $failed[$emailAddress] = 'Invalid recipient address';
                    $criticalLogger->critical('Invalid recipient address skipped: '.$emailAddress);
                }
            }

            if (empty($this->to)) {
                throw new \RuntimeException('No valid recipient address.');
            }

            $email = new TemplatedEmail();
            $this->setMessage($email);
            if (self::SET_HEADER) {
                $this->setHeaders($email);
            }

            $bounce = new Address($this->from, 'Bounce Handler');
            foreach ($this->to as $emailAddress) {
                try {
                    $rcpt = new Address($emailAddress);
                    $msg = clone $email;
                    $msg->to($rcpt);
                    $this->sendWithRetry($msg, new Envelope($bounce, [$rcpt]));
                    $sent[] = $emailAddress;
                    $logger->info('Send to '.$emailAddress.' from '.$this->from.' at '.(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))->format('Y-m-d H:i:s'));
                } catch (TransportExceptionInterface|Exception $exception) {
                    $failed[$emailAddress] = $exception->getMessage();
                    $criticalLogger->critical($exception->getMessage().' to '.$emailAddress);
                    $this->coreLocator->jsonLog($exception->getMessage().' to '.$emailAddress, 'email');
                }
            }
        } catch (Exception $exception) {
            $this->reset();
            $errorMessage = $exception->getMessage().' in '.get_class($this).' at line '.$exception->getLine();
            $criticalLogger->critical($errorMessage);
            $this->coreLocator->jsonLog($errorMessage, 'email');
            $message = $this->errorMessage($errorMessage);
            $this->addErrorFlash($message);

            return (object) [
                'success' => false,
                'exception' => $exception,
                'message' => $message,
                'sent' => $sent,
                'failed' => $failed,
            ];
        }

        $this->reset();

        if (empty($sent)) {
            $parts = [];
            foreach ($failed as $address => $error) {
                $parts[] = $address.': '.$error;
            }
            $message = $this->errorMessage(implode(' | ', $parts));
            $this->addErrorFlash($message);

            return (object) [
                'success' => false,
                'exception' => null,
                'message' => $message,
                'sent' => $sent,
                'failed' => $failed,
            ];
        }

        return (object) [
            'success' => true,
            'sent' => $sent,
            'failed' => $failed,
        ];
    }

    /**
     * Send with retry on transport failure.
     *
     * @throws TransportExceptionInterface
     */
    private function sendWithRetry(TemplatedEmail $email, Envelope $envelope): void
    {
        for ($attempt = 1; true; ++$attempt) {
            try {
                $this->mailer->send($email, $envelope);

                return;
            } catch (TransportExceptionInterface $exception) {
                if ($attempt >= self::MAX_ATTEMPTS) {
                    throw $exception;
                }
                usleep(self::RETRY_DELAY_MS * 1000);
            }
        }
    }

    /**
     * User error message.
     */
    private function errorMessage(string $errorMessage): string
    {
        return $this->coreLocator->isDebug() || $this->coreLocator->checkIP()
            ? Encoding::fixUTF8($errorMessage)
            : $this->coreLocator->translator()->trans("Une erreur est survenue lors de l'envoie du mail. Veuillez réessayer ou contacter le webmaster.", [], 'validators');
    }

    /**
     * Add error flash if a session is available (HTTP context only).
     */
    private function addErrorFlash(string $message): void
    {
        $request = $this->coreLocator->requestStack()->getMainRequest();
        if ($request && $request->hasSession()) {
            $request->getSession()->getFlashBag()->add('error', $message);
        }
    }

    /**
     * Reset state to defaults: the service is shared, without this
     * a second send() in the same request inherits previous values (attachments, cc...).
     */
    private function reset(): void
    {
        $this->website = null;
        $this->subject = null;
        $this->name = 'Up Animations!';
        $this->from = null;
        $this->fromSet = false;
        $this->to = [];
        $this->cc = [];
        $this->replyTo = null;
        $this->baseTemplate = 'base';
        $this->template = 'core/email/email.html.twig';
        $this->arguments = [];
        $this->attachments = [];
        $this->locale = null;
        $this->host = null;
        $this->schemeAndHttpHost = null;
    }

    /**
     * Set default values by WebsiteModel information.
     */
    private function default(): void
    {
        $request = $this->coreLocator->requestStack()->getMainRequest();
        $this->website = $this->website ?: $this->coreLocator->website();

        /* To set locale */
        if (!$this->locale) {
            $this->locale = is_object($request) && method_exists($request, 'getLocale')
                ? $request->getLocale() : ($this->website ? $this->website->configuration->locale : 'fr');
        }

        if ($this->locale) {
            $this->coreLocator->translator()->setLocale($this->locale);
        }

        $information = $this->website?->information;
        $hosts = $this->website?->hosts;

        if (is_object($information) && property_exists($information, 'companyName') && $information->companyName) {
            $this->name = $information->companyName;
        }

        if (!$this->fromSet && is_object($information) && property_exists($information, 'emailFrom')) {
            $this->from = $information->emailFrom;
        }

        if (!$this->replyTo && 'disabled' !== $this->replyTo && is_object($information) && property_exists($information, 'emailNoReply')) {
            $this->replyTo = $information->emailNoReply;
        } elseif ('disabled' === $this->replyTo) {
            $this->replyTo = null;
        }

        if (is_object($hosts) && property_exists($hosts, 'host')) {
            $this->host = $hosts->host;
        }

        if (is_object($hosts) && property_exists($hosts, 'schemeAndHttpHost')) {
            $this->schemeAndHttpHost = $hosts->schemeAndHttpHost;
        }
    }

    /**
     * Set message.
     *
     * @throws Exception
     */
    private function setMessage(TemplatedEmail $email): void
    {
        $this->arguments['website'] = $this->website;
        $this->arguments['base'] = $this->baseTemplate;
        $this->arguments['attachments'] = $this->attachments;
        $this->arguments['host'] = $this->host;
        $this->arguments['schemeAndHttpHost'] = $this->schemeAndHttpHost ?: ($this->website ? $this->website->schemeAndHttpHost : '');
        $this->arguments['locale'] = $this->locale;

        $subject = $this->envName ? '['.$this->envName.'] - '.$this->subject : $this->subject;

        $email->subject($subject);
        $email->date(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
        $this->name = $this->name ?: 'Up Animations!';
        $email->from(new Address($this->from, $this->name));
        foreach ($this->cc as $emailAddress) {
            if (filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
                $email->addCc(new Address($emailAddress));
            } else {
                $this->coreLocator->jsonLog('Invalid cc address skipped: '.$emailAddress, 'email');
            }
        }
        if ($this->replyTo && $this->replyTo !== $this->from && filter_var($this->replyTo, FILTER_VALIDATE_EMAIL)) {
            $email->replyTo(new Address($this->replyTo));
        }
        $email->generateMessageId();
        $email->htmlTemplate($this->template);
        $html = $this->templating->render($this->template, array_merge($this->arguments, ['isText' => true]));
        $email->text($this->minifyHtml($html));
        $email->context($this->arguments);

        foreach ($this->attachments as $attachment) {
            if (is_string($attachment) && is_file($attachment)) {
                $email->attachFromPath($attachment);
            } else {
                $this->coreLocator->jsonLog('Attachment not found: '.(is_string($attachment) ? $attachment : gettype($attachment)), 'email');
            }
        }
    }

    /**
     * Set headers.
     *
     * @throws RandomException
     */
    private function setHeaders(TemplatedEmail $email): void
    {
        $headers = $email->getHeaders();

        // Remove threading/priority noise
        foreach (['In-Reply-To', 'References', 'Importance', 'X-Priority', 'Priority'] as $x) {
            if ($headers->has($x)) {
                $headers->remove($x);
            }
        }
        $email->priority(Email::PRIORITY_NORMAL);

        // Pick a proper domain: From-domain > $this->host
        $domain = null;
        $from = $email->getFrom();
        if (!empty($from)) {
            $addr = $from[0]->getAddress();
            $at = strpos($addr, '@');
            if (false !== $at) {
                $domain = substr($addr, $at + 1);
            }
        }
        if (!$domain && !empty($this->host)) {
            $domain = preg_replace('/^www\./i', '', $this->host);
        }
        if (!$domain) {
            return; // keep the Message-ID generated in setMessage()
        }

        // Normalize domain (IDN -> ASCII) and tidy
        if (function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($domain, 0, INTL_IDNA_VARIANT_UTS46);
            if ($ascii) {
                $domain = $ascii;
            }
        }
        $domain = rtrim(strtolower($domain), '.');   // no trailing dot
        if ('' === $domain) {
            return;
        }

        if ($headers->has('Message-ID')) {
            $headers->remove('Message-ID');
        }
        // High-entropy local-part (only allowed characters)
        $local = bin2hex(random_bytes(16));
        // IMPORTANT: pass WITHOUT angle brackets; Symfony will add them
        $headers->addIdHeader('Message-ID', $local.'@'.$domain);
    }

    /**
     * Set subject.
     */
    public function setSubject(?string $subject = null): void
    {
        if ($subject) {
            $subject = strip_tags($subject);
        }

        $this->subject = $subject;
    }

    /**
     * Set name.
     */
    public function setName(?string $name = null): void
    {
        $this->name = $name ?: $this->name;
    }

    /**
     * Set from.
     */
    public function setFrom(?string $from = null): void
    {
        $this->from = $from ? trim($from) : $from;
        $this->fromSet = !empty($from);
    }

    /**
     * Set to.
     */
    public function setTo(array $to = []): void
    {
        $this->to = $this->normalizeAddresses($to);
    }

    /**
     * Set cc.
     */
    public function setCc(array $cc = []): void
    {
        $this->cc = $this->normalizeAddresses($cc);
    }

    /**
     * Explode comma-separated entries, trim and remove empties/duplicates.
     */
    private function normalizeAddresses(array $items): array
    {
        $emails = [];
        foreach ($items as $item) {
            foreach (explode(',', (string) $item) as $emailAddress) {
                $emailAddress = trim($emailAddress);
                if ('' !== $emailAddress) {
                    $emails[] = $emailAddress;
                }
            }
        }

        return array_values(array_unique($emails));
    }

    /**
     * Set replyTo.
     */
    public function setReplyTo(?string $replyTo = null): void
    {
        $this->replyTo = $replyTo;
    }

    /**
     * Set base template.
     */
    public function setBaseTemplate(?string $baseTemplate = null): void
    {
        $this->baseTemplate = $baseTemplate;
    }

    /**
     * Set template.
     */
    public function setTemplate(?string $template = null): void
    {
        $this->template = $template;
    }

    /**
     * Set arguments.
     */
    public function setArguments(array $arguments = []): void
    {
        $this->arguments = $arguments;
    }

    /**
     * Set attachments.
     */
    public function setAttachments(array $attachments = []): void
    {
        $this->attachments = $attachments;
    }

    /**
     * Set WebsiteModel.
     */
    public function setWebsite(?WebsiteModel $website = null): void
    {
        $this->website = $website;
    }

    /**
     * Set locale.
     */
    public function setLocale(?string $locale = null): void
    {
        $this->locale = $locale;
    }

    /**
     * To minify Html.
     */
    private function minifyHtml(string $html): string
    {
        // 0) Decode HTML entities (&nbsp; -> real NBSP, etc.)
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 1) Strip all HTML tags -> plain text
        $html = strip_tags($html);

        // 2) Normalize newlines to \n
        $html = str_replace(["\r\n", "\r"], "\n", $html);

        // 3) Collapse spaces/tabs/NBSP into a single space (UTF-8 aware)
        $html = preg_replace('/[ \t\x{00A0}]+/u', ' ', $html);

        // 4) Trim spaces around newlines: "  \n  " -> "\n"
        $html = preg_replace('/[ \t\x{00A0}]*\n[ \t\x{00A0}]*/u', "\n", $html);

        // 5) Collapse multiple newlines: "\n\n\n" -> "\n"
        $html = preg_replace("/\n{2,}/", "\n", $html);

        // 6) FIX: remove the exact pattern "space(s) + : + newline(s)" -> keep colon on the same line
        //    Examples fixed: "Nom :\nFélix" -> "Nom: Félix"  (you can choose ": Félix" or " : Félix" as you prefer)
        //    Here we standardize to ": " (colon plus space) with no newline.
        $html = preg_replace('/[ \t\x{00A0}]*:\s*\n+/u', ': ', $html);

        // Optional extras (uncomment if you also want to fix other punctuation-before-newline cases):
        // $html = preg_replace('/[ \t\x{00A0}]*(;|!|\?)\s*\n+/u', '$1 ', $html);

        // 7) Final trim
        return trim($html);
    }
}
