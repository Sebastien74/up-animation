<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Security\User;
use App\Repository\Security\UserRepository;
use App\Service\Interface\CoreLocatorInterface;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * LoginFormAuthenticator.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LoginFormAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;

    private mixed $LOGIN_TYPE;
    private const string LOGIN_ROUTE = 'security_login';
    private const string REGISTER_ROUTE = 'security_register';

    /**
     * LoginFormAuthenticator constructor.
     */
    public function __construct(
        private readonly BaseAuthenticator $baseAuthenticator,
        private readonly CoreLocatorInterface $coreLocator,
        private readonly UserRepository $userRepository,
    ) {
        $this->LOGIN_TYPE = $_ENV['SECURITY_ADMIN_LOGIN_TYPE'];
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
        $this->baseAuthenticator->setClassname(User::class);
        $this->baseAuthenticator->setUserRepository($this->userRepository);

        return $this->baseAuthenticator->supports($request);
    }

    /**
     * authenticate.
     *
     * @throws Exception
     * @throws InvalidArgumentException
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
        $this->baseAuthenticator->onAuthenticationSuccess($request);

        /** @var User $user */
        $user = $token->getUser();

        $this->clearAdminSession();

        if (self::REGISTER_ROUTE === $request->get('_route')) {
            return new RedirectResponse($this->coreLocator->router()->generate(self::REGISTER_ROUTE));
        }

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        $website = $this->coreLocator->website();
        $groupRedirection = $user->getGroup()->getLoginRedirection();
        $routeRedirection = $groupRedirection ?: 'admin_dashboard';

        return new RedirectResponse($this->coreLocator->router()->generate($routeRedirection, [
            'website' => $website->id,
        ]));
    }

    /**
     * onAuthenticationFailure.
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return $this->baseAuthenticator->onAuthenticationFailure($request, $exception, 'security_login');
    }

    /**
     * start.
     */
    public function start(Request $request, ?AuthenticationException $authException = null): RedirectResponse|JsonResponse
    {
        return $this->baseAuthenticator->start($request, 'front_index', 'security_login', $authException);
    }

    /**
     * Clear admin session.
     */
    private function clearAdminSession(): void
    {
        $sessionNames = ['social_networks', 'configuration_'];
        $sessionRequest = new Session();
        foreach ($sessionRequest->all() as $name => $value) {
            foreach ($sessionNames as $sessionName) {
                if (preg_match('/'.$sessionName.'/', $name)) {
                    $sessionRequest->remove($name);
                }
            }
        }
    }
}
