<?php

declare(strict_types=1);

namespace App\Form\Manager\Core;

use App\Entity\Core\Website;
use App\Service\Core\MailerService;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * SupportManager.
 *
 * To send email at support in admin
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => SupportManager::class, 'key' => 'core_support_form_manager'],
])]
class SupportManager
{
    /**
     * SupportManager constructor.
     */
    public function __construct(
        private readonly MailerService $mailer,
        private readonly CoreLocatorInterface $coreLocator,
    ) {
    }

    /**
     * Send request.
     */
    public function send(array $data, Website $website): void
    {
        $configuration = $website->getConfiguration();
        $emails = array_merge($configuration->getEmailsDev(), $configuration->getEmailsSupport());

        $this->mailer->setSubject($this->coreLocator->translator()->trans('Agence Félix Support', [], 'security_cms'));
        $this->mailer->setName($data['name']);
        $this->mailer->setTo(array_unique($emails));
        $this->mailer->setReplyTo($data['email']);
        $this->mailer->setBaseTemplate('base-felix');
        $this->mailer->setTemplate('admin/page/core/support-email.html.twig');
        $this->mailer->setArguments([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'message' => $data['message'],
        ]);
        $this->mailer->send();

        $session = new Session();
        $session->getFlashBag()->add('success', $this->coreLocator->translator()->trans('Votre message a été envoyé avec succès. Nous y répondrons dès que possible', [], 'admin'));
    }
}
