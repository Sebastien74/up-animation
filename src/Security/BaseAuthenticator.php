<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Core\Website;
use App\Entity\Security\User;
use App\Entity\Security\UserFront;
use App\Model\Core\WebsiteModel;
use App\Repository\Security\UserFrontRepository;
use App\Repository\Security\UserRepository;
use App\Service\Content\CryptService;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Security\Exception\IdentityProviderAuthenticationException;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception as SecurityException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * BaseAuthenticator.
 *
 * Manage recaptcha security authenticate post
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BaseAuthenticator
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;
    private string $loginRoute = '';
    private string $registerRoute = '';
    private string $loginType = '';
    private string $classname = '';
    private ?object $userRepository;
    private ?object $user = null;
    private array $credentials = [];

    /**
     * BaseAuthenticator constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly CryptService $cryptService,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly UserChecker $userChecker,
    ) {
        $this->translator = $this->coreLocator->translator();
        $this->entityManager = $this->coreLocator->em();
    }

    /**
     * Check if is valid POST.
     *
     * @throws InvalidArgumentException
     */
    public function supports(Request $request): ?bool
    {
        $currentRoute = $request->get('_route');

        if (($currentRoute === $this->loginRoute || $currentRoute === $this->registerRoute) && $request->isMethod('POST')) {
            $this->setCredentials($request);
            if ($currentRoute === $this->loginRoute && !$this->credentials['username']) {
                $message = $this->translator->trans('Authentication credentials could not be found.', [], 'security');
                throw new SecurityException\AuthenticationCredentialsNotFoundException($message);
            }
        }

        return $currentRoute === $this->loginRoute && $request->isMethod('POST');
    }

    /**
     * authenticate.
     *
     * @throws \Exception|InvalidArgumentException
     */
    public function authenticate(Request $request): Passport
    {
        $this->setCredentials($request);

        if ($this->credentials['username']) {
            $this->user = $this->entityManager->getRepository($this->classname)->loadUserByIdentifier($this->credentials['username']);
            if (!$this->user) {
                throw new SecurityException\UserNotFoundException();
            }
        }

        if ($request->get('_route') !== $this->registerRoute) {
            $website = $this->entityManager->getRepository(Website::class)->findOneByHost($request->getHost());
            $this->checkRecaptcha($website, $request);
            $this->checkPassword($website);
            $this->checkActive($website);
            $this->checkCsrfToken($request);
        }

        $passport = new Passport(
            new UserBadge($this->credentials['username'], [$this->userRepository, 'loadUserByIdentifier']),
            new PasswordCredentials($this->credentials['password'])
        );
        $passport->addBadge(new CsrfTokenBadge('authenticate', $this->credentials['csrf_token']));

        $rememberMe = new RememberMeBadge();
        $rememberMe->enable();
        $passport->addBadge($rememberMe);

        return $passport;
    }

    /**
     * onAuthenticationSuccess.
     *
     * @throws SessionNotFoundException
     */
    public function onAuthenticationSuccess(Request $request): void
    {
        $request->getSession()->set('onAuthenticationSuccess', true);
    }

    /**
     * onAuthenticationFailure.
     *
     * @throws SessionNotFoundException|RouteNotFoundException|MissingMandatoryParametersException|InvalidParameterException
     */
    public function onAuthenticationFailure(Request $request, SecurityException\AuthenticationException $exception, ?string $route = null): ?Response
    {
        if ($exception instanceof SecurityException\TooManyLoginAttemptsAuthenticationException) {
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        } elseif ($exception instanceof IdentityProviderAuthenticationException) {
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, new SecurityException\CustomUserMessageAccountStatusException($exception->getMessage()));
        } elseif (!$this->user) {
            $message = $this->translator->trans('Authentication credentials could not be found.', [], 'security');
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, new SecurityException\AuthenticationCredentialsNotFoundException($message));
        } else {
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        }

        $response = $route ? new RedirectResponse($this->coreLocator->router()->generate($route)) : null;

        if ($response && $request->get('_route') === $this->registerRoute) {
            $response->headers->setCookie(Cookie::create('SECURITY_ERROR', $this->translator->trans('La connexion automatique a échouée.', [], 'security_cms')));
        }

        if (str_contains($request->get('_route'), 'security_front_connect')) {
            foreach ($request->getSession()->all() as $key => $message) {
                if ('_security.last_error' === $key) {
                    $request->getSession()->remove($key);
                }
            }
            $session = new Session();
            $session->getFlashBag()->clear();
        }

        return $response;
    }

    /**
     * start.
     *
     * @throws RouteNotFoundException|MissingMandatoryParametersException|InvalidParameterException
     */
    public function start(Request $request, string $route, string $loginRoute, ?SecurityException\AuthenticationException $authException = null): RedirectResponse|JsonResponse
    {
        $isInvalid = $authException instanceof SecurityException\InvalidCsrfTokenException
            || $authException instanceof SecurityException\CustomUserMessageAccountStatusException
            || $authException instanceof SecurityException\AuthenticationCredentialsNotFoundException;

        if ($authException instanceof SecurityException\InsufficientAuthenticationException) {
            $indAmin = preg_match('/\/admin-'.$_ENV['SECURITY_TOKEN'].'/', $request->getUri());
            $routeName = $indAmin ? 'security_login' : 'security_front_login';

            return new RedirectResponse($this->coreLocator->router()->generate('app_logout', ['route_name' => $routeName]));
        }

        if ($isInvalid || is_object($authException) && !$request->getUser() && 403 === $authException->getPrevious()->getCode()) {
            if ($request->isMethod('POST') && $authException instanceof SecurityException\AuthenticationCredentialsNotFoundException) {
                $response = new RedirectResponse($this->coreLocator->router()->generate($loginRoute));
                $session = new Session();
                $session->getFlashBag()->add('error', $authException->getMessageKey());

                return $response;
            }

            $response = new RedirectResponse($this->coreLocator->router()->generate($route));
            if ('security_front_forms' === $route) {
                $response->headers->setCookie(Cookie::create('SECURITY_ERROR', $authException->getMessageKey()));
            }

            return $response;
        }

        $data = [
            'message' => 'Authentication Required',
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * To get credentials.
     *
     * @throws \Exception
     */
    public function getCredentials(): array
    {
        return $this->credentials;
    }

    /**
     * To set credentials.
     *
     * @throws InvalidArgumentException|SessionNotFoundException|BadRequestException
     */
    public function setCredentials(Request $request): void
    {
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $request->request->get($this->loginType));
        $post = !empty($request->request->all('registration')) ? $request->request->all('registration') : $request->request->all();

        $this->credentials['csrf_token'] = !empty($post['_csrf_token']) ? $post['_csrf_token'] : null;
        $this->credentials['username'] = !empty($post[$this->loginType]) ? $post[$this->loginType] : null;
        $this->credentials['password'] = !empty($post['plainPassword']['first']) ? $post['plainPassword']['first'] : (!empty($post['_password']) ? $post['_password'] : '.');

        if (empty($this->credentials['csrf_token']) && !empty($this->credentials['username'])) {
            $user = $this->entityManager->getRepository($this->classname)->loadUserByIdentifier($this->credentials['username']);
            if ($user instanceof User || $user instanceof UserFront) {
                $authenticatedToken = new UsernamePasswordToken($user, 'main', $user->getRoles());
                $this->credentials['csrf_token'] = $this->csrfTokenManager->getToken($authenticatedToken->getUserIdentifier())->getValue();
            }
        }
    }

    /**
     * To set login route.
     */
    public function setLoginRoute(string $route): void
    {
        $this->loginRoute = $route;
    }

    /**
     * To set register route.
     */
    public function setRegisterRoute(string $route): void
    {
        $this->registerRoute = $route;
    }

    /**
     * To set login type.
     */
    public function setLoginType(string $classname): void
    {
        $this->loginType = $classname;
    }

    /**
     * To set classname.
     */
    public function setClassname(string $classname): void
    {
        $this->classname = $classname;
    }

    /**
     * To set user Repository.
     */
    public function setUserRepository(UserFrontRepository|UserRepository $userRepository): void
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Check recaptcha.
     *
     * @throws \Exception
     */
    public function checkRecaptcha(WebsiteModel $website, Request $request, bool $asResponse = false)
    {
        $formSecurityKey = $website->entity->getSecurity()->getSecurityKey();
        $this->setSecurityKeys($website);

        $fieldHo = $request->request->get('field_ho');
        $fieldHoEntitled = $request->request->get('field_ho_entitled');
        if (!$fieldHo) {
            foreach ($request->request->all() as $key => $value) {
                if (!empty($request->request->all()[$key]['field_ho'])) {
                    $fieldHo = $request->request->all()[$key]['field_ho'];
                    $fieldHoEntitled = $request->request->all()[$key]['field_ho_entitled'];
                    break;
                }
            }
        }

        $message = $this->translator->trans('Erreur de sécurité !! Rechargez la page et réessayez.', [], 'security_cms');
        $session = $this->coreLocator->request()->getSession();

        if (!empty($fieldHo) && empty($fieldHoEntitled)) {
            $honeyPost = $this->cryptService->execute($website, $fieldHo, 'd');
            if ($honeyPost && urldecode($honeyPost) != $formSecurityKey) {
                $this->logger($request);
                if ($asResponse) {
                    $session->getFlashBag()->add('error', $message);

                    return false;
                } else {
                    throw new SecurityException\CustomUserMessageAccountStatusException($message);
                }
            }
        } else {
            $this->logger($request);
            if ($asResponse) {
                $session->getFlashBag()->add('error', $message);

                return false;
            } else {
                throw new SecurityException\CustomUserMessageAccountStatusException($message);
            }
        }

        if ($asResponse) {
            return true;
        }
    }

    /**
     * To check password.
     *
     * @throws \Exception
     */
    public function checkPassword(WebsiteModel $website): void
    {
        if ($this->user instanceof User || $this->user instanceof UserFront) {
            $passwordExpire = $this->userChecker->passwordExpired($website, $this->user);
            if ($passwordExpire) {
                $message = $this->translator->trans('Votre mot de passe a expiré, vous devez le réinitialiser.', [], 'security_cms');
                throw new SecurityException\CustomUserMessageAccountStatusException($message);
            }
        }
    }

    /**
     * To check if account is active.
     */
    public function checkActive(WebsiteModel $website): void
    {
        $isUser = $this->user instanceof User || $this->user instanceof UserFront;
        if ($isUser && !$this->user->isActive()) {
            $message = $this->getInactiveMessage($website);
            throw new SecurityException\CustomUserMessageAccountStatusException($message);
        }
    }

    /**
     * To get inactive message.
     */
    public function getInactiveMessage(WebsiteModel $website): string
    {
        $security = $website->entity->getSecurity();

        if ($security->isFrontRegistrationValidation()) {
            $message = $this->translator->trans("Votre compte n'est pas activé. Il doit être validé par l'administrateur.", [], 'security_cms');
        } elseif ($security->isFrontEmailConfirmation()) {
            $message = $this->translator->trans("L’activation de votre compte est en attente.<br>Un e-mail de confirmation vous a été adressé lors de votre inscription. Pensez à cliquer sur le lien qu’il contient pour activer votre compte.", [], 'security_cms');
        } else {
            $message = $this->translator->trans("Votre compte n'est pas activé.", [], 'security_cms');
        }

        return $message;
    }

    /**
     * To check csrf token.
     */
    public function checkCsrfToken(Request $request): void
    {
        $token = new CsrfToken('authenticate', $this->credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, new SecurityException\InvalidCsrfTokenException());
            throw new SecurityException\InvalidCsrfTokenException();
        }
    }

    /**
     * Set security keys if not generated.
     *
     * @throws \Exception
     */
    private function setSecurityKeys(WebsiteModel $website): void
    {
        $api = $website->entity->getApi();
        $securityKey = $api->getSecuritySecretKey();
        $securityIv = $api->getSecuritySecretIv();
        $flush = !$securityKey || !$securityIv;

        if (!$securityKey) {
            $key = base64_encode(uniqid().password_hash(uniqid(), PASSWORD_BCRYPT).random_bytes(10));
            $api->setSecuritySecretKey(substr(str_shuffle($key), 0, 45));
        }

        if (!$securityIv) {
            $key = base64_encode(uniqid().password_hash(uniqid(), PASSWORD_BCRYPT).random_bytes(10));
            $api->setSecuritySecretIv(substr(str_shuffle($key), 0, 45));
        }

        if ($flush) {
            $this->entityManager->persist($api);
            $this->entityManager->flush();
        }
    }

    /**
     * To log message.
     */
    private function logger(Request $request): void
    {
        $logger = new Logger('SECURITY_FORM');
        $logger->pushHandler(new RotatingFileHandler($this->coreLocator->logDir().'/security-cms.log', 10, Level::Critical));
        $logger->critical('Recaptcha security. IP register :'.$request->getClientIp());
    }
}
