<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Security\User;
use App\Entity\Security\UserFront;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * LogoutListener.
 *
 * To manage events on user logout
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LogoutListener
{
    /**
     * LogoutListener constructor.
     */
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly RouterInterface $router,
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * On logout success.
     */
    public function onSymfonyComponentSecurityHttpEventLogoutEvent(LogoutEvent $event): void
    {
        $request = $event->getRequest();
        $routeName = !empty($request->get('route_name')) ? $request->get('route_name') : 'front_index';
        $response = new RedirectResponse($this->router->generate($routeName));
        $token = $this->tokenStorage->getToken();

        if (!empty($request->get('route_name'))) {
            $session = new Session();
            $session->getFlashBag()->add('error', $this->translator->trans('Une erreur de sécurité est survenue, veuillez réessayer de vous connecter.', [], 'security_cms'));
        }

        if (is_object($token) && method_exists($token, 'getUser')) {
            /** @var User|UserFront $user */
            $user = $token->getUser();
            $user->setIsonline(false);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        $this->invalidate($request, $response);
    }

    /**
     * Invalidate User Session.
     */
    private function invalidate(Request $request, RedirectResponse $response): void
    {
        $request->getSession()->invalidate();
        $this->tokenStorage->setToken();
        /* To clear cookies */
        foreach ($request->cookies->all() as $cookieName => $value) {
            if (!str_contains($cookieName, 'felixCookies')) {
                $response->headers->clearCookie($cookieName);
            }
        }
        $response->send();
    }
}
