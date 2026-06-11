<?php

declare(strict_types=1);

namespace App\MessageHandler\Axonaut;

use App\Message\Axonaut\PushContactToAxonaut;
use App\Service\Axonaut\AxonautClientInterface;
use App\Service\Interface\CoreLocatorInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * PushContactToAxonautHandler.
 *
 * Creates a prospect company, the related contact (employee) and an opportunity
 * in Axonaut from a form submission. Failures are logged but never rethrown.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsMessageHandler]
final class PushContactToAxonautHandler
{
    public function __construct(
        private readonly AxonautClientInterface $axonaut,
        private readonly CoreLocatorInterface $coreLocator,
    ) {
    }

    public function __invoke(PushContactToAxonaut $message): void
    {
        $logger = new Logger('axonaut_handler');
        $logger->pushHandler(new RotatingFileHandler($this->coreLocator->logDir().'/axonaut-handler.log', 10, Level::Info));

        if (!$this->axonaut->isAvailable()) {
            return;
        }

        $companyName = $this->resolveCompanyName($message);
        $comments = $this->buildComments($message);

        $companyId = $this->axonaut->createCompany($companyName, $comments);
        if (!$companyId) {
            $logger->error('Axonaut: company not created', ['name' => $companyName]);

            return;
        }

        if ($message->firstname || $message->lastname || $message->email) {
            $this->axonaut->createEmployee($companyId, $message->firstname, $message->lastname, $message->email, $message->phone);
        }

        $opportunityName = $message->opportunityName ?: $companyName;
        $this->axonaut->createOpportunity($companyId, $opportunityName, $comments);

        $logger->info('Axonaut: contact pushed', ['company_id' => $companyId, 'name' => $companyName]);
    }

    /**
     * Prefer the submitted company; fall back to the person name, then the email.
     */
    private function resolveCompanyName(PushContactToAxonaut $message): string
    {
        if ($message->company) {
            return $message->company;
        }
        $person = trim(($message->firstname ?? '').' '.($message->lastname ?? ''));
        if ('' !== $person) {
            return $person;
        }

        return $message->email ?: 'Contact site web';
    }

    private function buildComments(PushContactToAxonaut $message): string
    {
        $lines = [];
        if ($message->email) {
            $lines[] = 'Email : '.$message->email;
        }
        if ($message->phone) {
            $lines[] = 'Téléphone : '.$message->phone;
        }
        if ($message->comments) {
            $lines[] = $message->comments;
        }

        return implode("\n", $lines);
    }
}
