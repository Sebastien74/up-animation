<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Security\UserFront;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * AdminToFrontSwitcher.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class AdminToFrontSwitcher
{
    private const string FRONT_FIREWALL = 'user_front';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function switchTo(UserFront $userFront): void
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request || !$request->hasSession()) {
            throw new RuntimeException('No HTTP session available.');
        }

        // regenerate the session id to drop the back-office identity, then install the front user token;
        // bypass scheb 2FA by setting the token directly instead of routing through the authenticator chain
        $request->getSession()->migrate(true);

        $token = new PostAuthenticationToken($userFront, self::FRONT_FIREWALL, $userFront->getRoles());
        $this->tokenStorage->setToken($token);

        $this->eventDispatcher->dispatch(new InteractiveLoginEvent($request, $token), SecurityEvents::INTERACTIVE_LOGIN);
    }
}
