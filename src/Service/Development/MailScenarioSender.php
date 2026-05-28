<?php

declare(strict_types=1);

namespace App\Service\Development;

use App\Entity\Core\Website;
use App\Model\Core\WebsiteModel;
use App\Service\Core\MailerService;
use App\Service\Interface\CoreLocatorInterface;
use Throwable;

final class MailScenarioSender
{
    public function __construct(
        private readonly MailerService $mailer,
        private readonly CoreLocatorInterface $coreLocator,
        private readonly MailScenarioCatalog $catalog,
    ) {
    }

    /**
     * @return array{success: bool, error: ?string}
     */
    public function send(string $scenarioId, string $recipient): array
    {
        if (!$this->catalog->has($scenarioId)) {
            return ['success' => false, 'error' => 'Unknown scenario id.'];
        }

        if ('' === $recipient || false === filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid recipient email.'];
        }

        try {
            $website = $this->resolveWebsite();
            $config = $this->buildConfig($scenarioId, $website);

            $this->mailer->setWebsite($website);
            $this->mailer->setLocale($website->configuration?->locale ?? 'fr');
            $this->mailer->setSubject($config['subject']);
            $this->mailer->setTo([$recipient]);
            $this->mailer->setTemplate($config['template']);
            $this->mailer->setArguments($config['arguments']);

            if (isset($config['from'])) {
                $this->mailer->setFrom($config['from']);
            }
            if (array_key_exists('replyTo', $config)) {
                $this->mailer->setReplyTo($config['replyTo']);
            }

            $result = $this->mailer->send();

            return [
                'success' => (bool) ($result->success ?? false),
                'error' => ($result->success ?? false) ? null : ($result->message ?? 'Unknown error'),
            ];
        } catch (Throwable $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }
    }

    /**
     * @return list<array{id: string, label: string, success: bool, error: ?string}>
     */
    public function sendAll(string $recipient): array
    {
        $results = [];
        foreach ($this->catalog->all() as $scenario) {
            $outcome = $this->send($scenario['id'], $recipient);
            $results[] = [
                'id' => $scenario['id'],
                'label' => $scenario['label'],
                'success' => $outcome['success'],
                'error' => $outcome['error'],
            ];
        }

        return $results;
    }

    private function resolveWebsite(): WebsiteModel
    {
        $model = $this->coreLocator->website();
        if ($model instanceof WebsiteModel) {
            return $model;
        }

        $entity = $this->coreLocator->em()->getRepository(Website::class)->findOneBy([]);
        if (!$entity instanceof Website) {
            throw new \RuntimeException('No Website configured in database.');
        }

        return WebsiteModel::fromEntity($entity, $this->coreLocator);
    }

    /**
     * @return array{subject: string, template: string, arguments: array<string, mixed>, from?: string, replyTo?: ?string}
     */
    private function buildConfig(string $id, WebsiteModel $website): array
    {
        $base = $website->schemeAndHttpHost ?: 'https://localhost';

        return match ($id) {
            'newsletter-confirmation' => [
                'subject' => '[DEMO] Confirmez votre inscription à notre newsletter',
                'template' => 'front/default/actions/newsletter/email/confirmation.html.twig',
                'arguments' => [
                    'stringEmail' => 'demo-subscriber@example.test',
                    'confirmationLink' => $base.'/newsletter/confirm/demo-token',
                    'message' => null,
                ],
                'replyTo' => 'disabled',
            ],
            'newsletter-webmaster' => [
                'subject' => '[DEMO] Nouvel inscrit à la newsletter',
                'template' => 'front/default/actions/newsletter/email/webmaster.html.twig',
                'arguments' => [
                    'stringEmail' => 'demo-subscriber@example.test',
                ],
                'replyTo' => 'demo-subscriber@example.test',
            ],
            'contact-confirmation' => [
                'subject' => '[DEMO] Confirmation de votre demande de contact',
                'template' => 'front/default/actions/form/email/contact-confirmation.html.twig',
                'arguments' => [
                    'message' => '<p>Merci pour votre message, nous reviendrons vers vous rapidement.</p>',
                ],
                'replyTo' => 'disabled',
            ],
            'contact-form' => [
                'subject' => '[DEMO] Nouveau message via le formulaire de contact',
                'template' => 'front/default/actions/form/email/contact.html.twig',
                'arguments' => [
                    'fields' => [
                        'lastName'  => (object) ['label' => 'Nom',     'value' => 'Doe',                       'valueIntl' => 'Doe'],
                        'firstName' => (object) ['label' => 'Prénom',   'value' => 'John',                      'valueIntl' => 'John'],
                        'email'     => (object) ['label' => 'Email',    'value' => 'john.doe@example.test',     'valueIntl' => 'john.doe@example.test'],
                        'phone'     => (object) ['label' => 'Téléphone','value' => '+33 1 23 45 67 89',         'valueIntl' => '+33 1 23 45 67 89'],
                        'company'   => (object) ['label' => 'Société',  'value' => 'Up Animations!',            'valueIntl' => 'Up Animations!'],
                        'subject'   => (object) ['label' => 'Sujet',    'value' => 'Demande de devis',          'valueIntl' => 'Demande de devis'],
                        'message'   => (object) ['label' => 'Message',  'value' => 'Bonjour, je souhaite un devis pour un événement le mois prochain.', 'valueIntl' => 'Bonjour, je souhaite un devis pour un événement le mois prochain.'],
                    ],
                    'attachments' => [],
                ],
                'replyTo' => 'john.doe@example.test',
            ],
            'registration' => [
                'subject' => '[DEMO] Finalisez votre inscription',
                'template' => 'front/default/actions/security/email/confirmation-registration.html.twig',
                'arguments' => [
                    'token' => 'demo-registration-token',
                    'user' => (object) [
                        'username' => 'demo-user',
                        'email' => 'demo-user@example.test',
                        'firstName' => 'John',
                        'lastName' => 'Doe',
                        'locale' => 'fr',
                    ],
                ],
            ],
            'reset-password' => [
                'subject' => '[DEMO] Réinitialisation de votre mot de passe',
                'template' => 'front/default/actions/security/email/password-request.html.twig',
                'arguments' => [
                    'token' => 'demo-reset-token',
                ],
            ],
            'password-expire' => [
                'subject' => '[DEMO] Votre mot de passe arrive à expiration',
                'template' => 'front/default/actions/security/email/password-expire.html.twig',
                'arguments' => [
                    'expire' => false,
                    'user' => (object) ['locale' => 'fr', 'email' => 'demo-user@example.test'],
                    'website' => $website,
                    'schemeAndHttpHost' => $base,
                ],
            ],
            '2fa-code' => [
                'subject' => '[DEMO] Votre code de vérification',
                'template' => 'front/default/actions/security/email/2fa-code.html.twig',
                'arguments' => [
                    'code' => '123456',
                    'user' => (object) ['email' => 'demo-user@example.test'],
                ],
            ],
            default => throw new \RuntimeException(sprintf('Unknown scenario "%s".', $id)),
        };
    }
}
