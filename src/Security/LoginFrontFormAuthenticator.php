<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Security\UserFront;
use App\Repository\Security\UserFrontRepository;
use App\Service\Interface\CoreLocatorInterface;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * LoginFrontFormAuthenticator.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LoginFrontFormAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;

    private mixed $LOGIN_TYPE;
    private const string LOGIN_ROUTE = 'security_front_login';
    private const string REGISTER_ROUTE = 'security_front_auto_login';

    /**
     * LoginFrontFormAuthenticator constructor.
     */
    public function __construct(
        private readonly BaseAuthenticator $baseAuthenticator,
        private readonly CoreLocatorInterface $coreLocator,
        private readonly UserFrontRepository $userRepository,
    ) {
        $this->LOGIN_TYPE = $_ENV['SECURITY_FRONT_LOGIN_TYPE'];
    }

    /**
     * supports.
     *
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning `false` will cause this authenticator
     * to be skipped.
     *
     * @throws Exception|InvalidArgumentException
     */
    public function supports(Request $request): ?bool
    {
        $this->baseAuthenticator->setLoginRoute(self::LOGIN_ROUTE);
        $this->baseAuthenticator->setRegisterRoute(self::REGISTER_ROUTE);
        $this->baseAuthenticator->setLoginType($this->LOGIN_TYPE);
        $this->baseAuthenticator->setClassname(UserFront::class);
        $this->baseAuthenticator->setUserRepository($this->userRepository);

        return $this->baseAuthenticator->supports($request);
    }

    /**
     * authenticate.
     *
     * @throws Exception|InvalidArgumentException
     */
    public function authenticate(Request $request): Passport
    {
        return $this->baseAuthenticator->authenticate($request);
    }

    /**
     * onAuthenticationSuccess.
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->getSuccessRedirectionUrl($request));
    }

    /**
     * onAuthenticationFailure.
     *
     * @throws Exception
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return $this->baseAuthenticator->onAuthenticationFailure($request, $exception, 'security_front_forms');
    }

    /**
     * start.
     */
    public function start(Request $request, ?AuthenticationException $authException = null): RedirectResponse|JsonResponse
    {
        return $this->baseAuthenticator->start($request, 'security_front_forms', 'security_front_forms', $authException);
    }

    /**
     * To get redirect url after success login.
     */
    public function getSuccessRedirectionUrl(Request $request): string
    {
        $previousUrl = $request->getSession()->get('previous_secure_url');
        $this->baseAuthenticator->onAuthenticationSuccess($request);

        if ($previousUrl) {
            return $previousUrl;
        }

        return $this->coreLocator->website()->securityDashboardUrl;
    }
}
