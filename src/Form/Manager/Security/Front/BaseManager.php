<?php

declare(strict_types=1);

namespace App\Form\Manager\Security\Front;

use App\Entity\Security\Message;
use App\Entity\Security\UserFront;
use App\Model\EntityModel;
use App\Service\Core\MailerService;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Random\RandomException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * BaseManager.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => BaseManager::class, 'key' => 'security_front_base_form_manager'],
])]
class BaseManager
{
    /**
     * BaseManager constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly MailerService $mailer,
    )
    {
    }

    /**
     * To set token.
     *
     * @throws RandomException
     */
    protected function token(string $key): string
    {
        $token = $key ? bin2hex(random_bytes(45).md5($key)) : bin2hex(random_bytes(45).md5(uniqid()));
        return str_replace(['%', '/'], '', $token);
    }

    /**
     * To get message.
     *
     * @throws NonUniqueResultException|MappingException
     */
    protected function message(string $slug, string $subject): object
    {
        $website = $this->coreLocator->website();
        $message = $this->coreLocator->em()->getRepository(Message::class)->findOneBy(['website' => $website->entity, 'slug' => $slug]);
        $message = $message ? EntityModel::fromEntity($message, $this->coreLocator)->response : false;
        $message = $message && $message->intl->body ? $message->intl->body : false;

        return (object) [
            'subject' => $message && $message->intl->title ? $message->intl->body : $subject,
            'content' => $message && $message->intl->body ? $message->intl->body : false,
        ];
    }

    /**
     * To send Email.
     */
    protected function sendMail(UserInterface $user, object $message, ?string $template = null, ?string $token = null): void
    {
        /** @var UserFront $user */
        $template = $template ?: 'default';
        $website = $this->coreLocator->website();
        $this->mailer->setSubject($message->subject);
        $this->mailer->setTo([$user->getEmail()]);
        $this->mailer->setTemplate('front/'.$website->configuration->template.'/actions/security/back/email-'.$template.'.html.twig');
        $this->mailer->setArguments([
            'user' => $user,
            'userRequest' => $user->getUserRequest(),
            'subject' => $message->subject,
            'message' => $message->content,
            'companyName' => $website->companyName,
            'token' => $token
        ]);
        $this->mailer->send();
    }
}