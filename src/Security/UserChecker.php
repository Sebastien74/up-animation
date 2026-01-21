<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Core\Website;
use App\Entity\Security;
use App\Model\Core\WebsiteModel;
use App\Security\Interface\UserCheckerInterface;
use App\Service\Interface\CoreLocatorInterface;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * UserChecker.
 *
 * To listen user
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UserChecker implements UserCheckerInterface
{
    private const bool ENABLE_LAST_ACTIVITY = false;

    private ?UserInterface $user;

    /**
     * UserChecker constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->user = !empty($this->coreLocator->tokenStorage()->getToken()) ? $this->coreLocator->tokenStorage()->getToken()->getUser() : null;
    }

    /**
     * To execute service.
     *
     * @throws Exception
     */
    public function execute(RequestEvent $event, ?WebsiteModel $website = null): void
    {
        $request = $event->getRequest();
        $disableDRoutes = ['app_logout'];
        $loginRoutes = ['security_front_login', 'security_login', 'security_front_forms'];
        $allowedSwitchRoutes = ['security_front_confirmation'];
        $routeName = $request->get('_route');
        $roles = $this->user ? $this->user->getRoles() : [];

        /* To redirect user back to switcher if in front secure page */
        $isAdmin = $this->user && in_array('ROLE_ADMIN', $roles);
        if ($isAdmin && str_contains($routeName, 'front') && str_contains($routeName, 'security') && !in_array($routeName, $allowedSwitchRoutes)) {
            $response = new RedirectResponse($this->coreLocator->router()->generate('security_switcher'));
            $event->setResponse($response);
        }

        $isImpersonator = $this->user && in_array('IS_IMPERSONATOR', $roles);
        if (!in_array($routeName, $disableDRoutes) && $isAdmin && !$isImpersonator) {
            $isLoginRoute = in_array($routeName, $loginRoutes);
            if ($this->user instanceof Security\User || $this->user instanceof Security\UserFront) {
                //                if (!$user->isOnline() && !$request->getSession()->get('onAuthenticationSuccess')) {
                //                    $request->getSession()->invalidate();
                //                    $this->coreLocator->tokenStorage()->setToken();
                //                    if ($isLoginRoute && !$request->isMethod('POST')) {
                //                        $arguments = $routeName === 'security_login' ? ['_locale' => $request->getLocale()] : [];
                //                        $response = new RedirectResponse($this->router->generate($routeName, $arguments));
                //                        $event->setResponse($response);
                //                    }
                //                } else {
                $this->checkAccount($event, $request, $this->user);
                $this->checkPassword($event, $this->user, $isLoginRoute, $website);
                $this->setLastActivity($this->user);
                //                }
                //                $request->getSession()->remove('onAuthenticationSuccess');
            }
        }
    }

    /**
     * To redirect inactive User.
     */
    private function checkAccount(RequestEvent $event, Request $request, Security\User|Security\UserFront $user): void
    {
        if (!$user->isActive() && !$request->get('inactive')) {
            $response = new RedirectResponse($this->coreLocator->router()->generate('app_logout'));
            $event->setResponse($response);
        }
    }

    /**
     * Check if User must reset his password.
     *
     * @throws Exception
     */
    private function checkPassword(RequestEvent $event, Security\User|Security\UserFront $user, bool $isLoginRoute, ?WebsiteModel $website = null): void
    {
        $repository = $this->coreLocator->em()->getRepository(Website::class);
        $website = $website instanceof WebsiteModel ? $website : $repository->findOneByHost($event->getRequest()->getHost(), true);
        $website = $website ?: $repository->findAll()[0];
        $passwordExpire = $this->passwordExpired($website, $user);
        if ($passwordExpire) {
            if ($isLoginRoute) {
                $event->getRequest()->getSession()->invalidate();
                $this->coreLocator->tokenStorage()->setToken(null);
            } else {
                $response = new RedirectResponse($this->coreLocator->router()->generate('app_logout'));
                $event->setResponse($response);
            }
        }
    }

    /**
     * To check if password expired.
     *
     * @throws Exception
     */
    public function passwordExpired(WebsiteModel $website, Security\User|Security\UserFront $user): bool
    {
        $security = $website->entity->getSecurity();
        $securityStatus = $user instanceof Security\User ? $security->isAdminPasswordSecurity() : $security->isFrontPasswordSecurity();
        $delay = $user instanceof Security\User ? $security->getAdminPasswordDelay() : $security->getFrontPasswordDelay();

        if ($delay) {
            if (!$user->getResetPasswordDate()) {
                $user->setResetPasswordDate(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                $this->coreLocator->em()->persist($user);
                $this->coreLocator->em()->flush();
            }

            $today = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
            $interval = new \DateInterval('P'.$delay.'D');

            /** @var \DateTime $resetDate */
            $resetDate = $user->getResetPasswordDate();
            $resetDate->add($interval);

            $expired = $securityStatus && $today > $resetDate;

            if ($expired) {
                $user->setResetPassword(true);
                $this->coreLocator->em()->persist($user);
                $this->coreLocator->em()->flush();
            }

            return $expired;
        }

        return false;
    }

    /**
     * To set last activity.
     *
     * @throws Exception
     */
    private function setLastActivity(Security\User|Security\UserFront $user): void
    {
        if (self::ENABLE_LAST_ACTIVITY) {
            $delay = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
            $delay->setTimestamp(strtotime('2 minutes ago'));
            if ($user->getLastActivity() < $delay) {
                $user->setLastActivity(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                $this->coreLocator->em()->persist($user);
                $this->coreLocator->em()->flush();
            }
        }
    }
}
