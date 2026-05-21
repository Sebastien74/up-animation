<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Security\User;
use App\Service\Interface\CoreLocatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

/**
 * Forces admin users to enrol TOTP before reaching any admin page.
 *
 * Runs after the firewall (priority below 8) so the authenticated user is available.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class AdminTwoFactorRequiredSubscriber implements EventSubscriberInterface
{
    private const string ADMIN_PATH_PATTERN = '#^/(admin-|secure/user)#';

    private const array ALLOWED_ROUTES = [
        'admin_2fa_setup',
        'admin_2fa_disable',
        'admin_2fa_email_enable',
        'admin_2fa_email_disable',
        'admin_2fa_switch_to_totp',
        'admin_2fa_switch_to_email',
        'admin_2fa_regenerate_backup_codes',
        'admin_2fa_users_index',
        'admin_2fa_users_disable_totp',
        'admin_2fa_users_disable_email',
        'admin_2fa_users_enable_email',
        'admin_2fa_users_enable_totp',
        'admin_2fa_users_disable_auth',
        'admin_2fa_users_restore_auth',
        '2fa_admin_login',
        '2fa_admin_login_check',
        'app_logout',
        'security_login',
    ];

    public function __construct(
        private Security $security,
        private RouterInterface $router,
        private CoreLocatorInterface $coreLocator,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 4],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if (!preg_match(self::ADMIN_PATH_PATTERN, $path)) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        // Global kill-switch: when admin 2FA is disabled at the website level,
        // no enrolment is forced - login stays classic password-only.
        $websiteEntity = $this->coreLocator->website()?->entity;
        if (false === $websiteEntity?->getSecurity()?->isAdminTwoFactorAuth()) {
            return;
        }

        if ($user->isDisabledAuth() || $user->isGoogleAuthenticatorEnabled() || $user->isEmailAuthEnabled()) {
            return;
        }

        $route = (string) $request->attributes->get('_route');
        if (in_array($route, self::ALLOWED_ROUTES, true) || str_starts_with($route, '_')) {
            return;
        }

        $website = $request->attributes->get('website');
        if (null === $website) {
            $website = $this->coreLocator->website()?->entity?->id;
        }

        if (null === $website) {
            // Fail-closed: an admin requires 2FA enrolment but the website context cannot be resolved.
            // Forcing logout is safer than silently letting the request through.
            $this->logger->critical('2FA enrolment guard could not resolve website context - forcing logout.', [
                'user' => $user->getUserIdentifier(),
                'path' => $path,
            ]);

            $event->setResponse(new RedirectResponse($this->router->generate('app_logout')));

            return;
        }

        $event->setResponse(new RedirectResponse(
            $this->router->generate('admin_2fa_setup', ['website' => $website])
        ));
    }
}
