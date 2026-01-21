<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Core\Website;
use App\Message\SendEmail;
use App\Model\Core\WebsiteModel;
use App\Service\Core\MailerService;
use App\Service\Interface\CoreLocatorInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * SendEmailHandler.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsMessageHandler]
final class SendEmailHandler
{
    /**
     * SendEmailHandler constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly MailerService $mailer,
    ) {

    }

    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(SendEmail $message): void
    {
        $logger = new Logger('send_mailer_handler');
        if ($message->getTo()) {
            try {
                $website = $this->coreLocator->em()->getRepository(Website::class)->find($message->getWebsiteId());
                $websiteModel = WebsiteModel::fromEntity($website, $this->coreLocator, $message->getLocale());
                $this->mailer->setLocale($message->getLocale());
                $this->mailer->setWebsite($websiteModel);
                $this->mailer->setSubject($message->getSubject());
                $this->mailer->setTo($message->getTo());
                $this->mailer->setCc($message->getCc());
                $this->mailer->setName($message->getName());
                $this->mailer->setFrom($message->getFrom());
                $this->mailer->setReplyTo($message->getReplyTo());
                $this->mailer->setTemplate($message->getTemplate());
                $this->mailer->setArguments($message->getArguments());
                $this->mailer->setAttachments($message->getAttachments());
                $this->mailer->send();
                $logger->pushHandler(new RotatingFileHandler($this->coreLocator->logDir().'/mailer-handler.log', 10, Level::Info));
                $logger->info('Success: '.$message->getSubject());
                foreach ($message->getTo() as $to) {
                    $logger->info('Success to:'.$to);
                }
            } catch (\Exception $exception) {
                $logger->pushHandler(new RotatingFileHandler($this->coreLocator->logDir().'/mailer-handler-critical.log', 10, Level::Critical));
                $logger->critical('Failed : '.$exception->getMessage());
                throw new \RuntimeException();
            }
        } else {
            $logger->pushHandler(new RotatingFileHandler($this->coreLocator->logDir().'/mailer-handler-critical.log', 10, Level::Warning));
            $logger->warning('Failed');
            foreach ($message->getTo() as $to) {
                $logger->critical('Failed to: '.$to);
            }
            throw new \RuntimeException();
        }
    }
}
