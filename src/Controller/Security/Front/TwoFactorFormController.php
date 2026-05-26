<?php

declare(strict_types=1);

namespace App\Controller\Security\Front;

use App\Controller\Front\FrontController;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\TwoFactorFirewallContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * Renders the 2FA form within the front base layout (defaultArgs are required by base.html.twig).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TwoFactorFormController extends FrontController
{
    public function form(
        Request $request,
        TokenStorageInterface $tokenStorage,
        TwoFactorFirewallContext $firewallContext,
    ): Response {
        $token = $tokenStorage->getToken();
        if (!$token instanceof TwoFactorTokenInterface) {
            throw new AccessDeniedException('User is not in a two-factor authentication process.');
        }

        $providerName = $token->getCurrentTwoFactorProvider();
        if (null === $providerName) {
            throw new AccessDeniedException('User is not in a two-factor authentication process.');
        }

        $config = $firewallContext->getFirewallConfig($token->getFirewallName());
        $authException = $this->extractAuthenticationException($request);
        $website = $this->getWebsite();

        $twoFactorArgs = [
            'twoFactorProvider' => $providerName,
            'availableTwoFactorProviders' => $token->getTwoFactorProviders(),
            'authenticationError' => $authException?->getMessageKey(),
            'authenticationErrorData' => $authException?->getMessageData() ?? [],
            'authCodeParameterName' => $config->getAuthCodeParameterName(),
            'isCsrfProtectionEnabled' => $config->isCsrfProtectionEnabled(),
            'csrfParameterName' => $config->getCsrfParameterName(),
            'csrfTokenId' => $config->getCsrfTokenId(),
            'templateName' => 'security',
        ];

        return $this->render(
            'front/'.$website->configuration->template.'/actions/security/front/2fa-form.html.twig',
            array_merge($this->defaultArgs($website), $twoFactorArgs)
        );
    }

    private function extractAuthenticationException(Request $request): ?AuthenticationException
    {
        $session = $request->getSession();
        $exception = $session->get(SecurityRequestAttributes::AUTHENTICATION_ERROR);
        if ($exception instanceof AuthenticationException) {
            $session->remove(SecurityRequestAttributes::AUTHENTICATION_ERROR);

            return $exception;
        }

        return null;
    }
}
