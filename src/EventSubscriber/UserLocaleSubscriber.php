<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Security\User;
use App\Entity\Security\UserFront;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * UserLocaleSubscriber.
 *
 * Stores the locale of the user in the session after the
 * login. This can be used by the LocaleSubscriber afterwards.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UserLocaleSubscriber implements EventSubscriberInterface
{
    /**
     * UserLocaleSubscriber constructor.
     */
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        /** @var User|UserFront $user */
        $user = $event->getAuthenticationToken()->getUser();
        if (null !== $user->getLocale()) {
            $this->requestStack->getSession()->set('_locale', $user->getLocale());
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
        ];
    }
}
