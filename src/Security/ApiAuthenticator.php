<?php

declare(strict_types=1);

namespace App\Security;

class ApiAuthenticator
{
}

// use App\Entity\Core\WebsiteModel;
// use App\Entity\Security\UserFront;
// use App\Form\Manager\Security\Front\RegisterManager;
// use App\Repository\Security\UserFrontRepository;
// use Doctrine\ORM\EntityManagerInterface;
// use Exception;
// use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
// use KnpU\OAuth2ClientBundle\Exception\InvalidStateException;
// use KnpU\OAuth2ClientBundle\Exception\MissingAuthorizationCodeException;
// use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
// use KnpU\OAuth2ClientBundle\Security\Exception\IdentityProviderAuthenticationException;
// use KnpU\OAuth2ClientBundle\Security\Exception\InvalidStateAuthenticationException;
// use KnpU\OAuth2ClientBundle\Security\Exception\NoAuthCodeAuthenticationException;
// use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
// use League\OAuth2\Client\Token\AccessToken;
// use Symfony\Component\HttpFoundation\RedirectResponse;
// use Symfony\Component\HttpFoundation\Request;
// use Symfony\Component\HttpFoundation\Response;
// use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
// use Symfony\Component\Security\Core\Exception\AuthenticationException;
// use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
// use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
// use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
// use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
// use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
// use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
// use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
//
// /**
// * ApiAuthenticator
// *
// * https://github.com/knpuniversity/oauth2-client-bundle
// *
// * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
// */
// class ApiAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
// {
//    private const array APIS = ['google', 'facebook'];
//    private const string DEFAULT_PASSWORD = 'VxR%\Y!wtsr!5((';
//
//    private BaseAuthenticator $baseAuthenticator;
//    private LoginFrontFormAuthenticator $frontAuthenticator;
//    private ClientRegistry $clientRegistry;
//    private RegisterManager $registerManager;
//    private UserFrontRepository $userRepository;
//    private EntityManagerInterface $entityManager;
//    private string $apiName = '';
//
//    /**
//     * ApiAuthenticator constructor.
//     */
//    public function __construct(
//        BaseAuthenticator           $baseAuthenticator,
//        LoginFrontFormAuthenticator $frontAuthenticator,
//        ClientRegistry              $clientRegistry,
//        RegisterManager             $registerManager,
//        UserFrontRepository         $userRepository,
//        EntityManagerInterface      $entityManager)
//    {
//        $this->baseAuthenticator = $baseAuthenticator;
//        $this->frontAuthenticator = $frontAuthenticator;
//        $this->clientRegistry = $clientRegistry;
//        $this->registerManager = $registerManager;
//        $this->userRepository = $userRepository;
//        $this->entityManager = $entityManager;
//    }
//
//    /**
//     * supports
//     */
//    public function supports(Request $request): bool
//    {
//        foreach (self::APIS as $apiName) {
//            if ($request->attributes->get('_route') === "security_front_connect_" . $apiName . "_check") {
//                $this->apiName = $apiName;
//                return true;
//            }
//        }
//
//        return false;
//    }
//
//    /**
//     * authenticate

//     * @throws Exception
//     */
//    public function authenticate(Request $request): PassportInterface
//    {
//        try {
//            $accessToken = $this->getClient()->getAccessToken([]);
//            $website = $this->entityManager->getRepository(WebsiteModel::class)->findDefault();
//            $user = $this->getPassportByAuthToken($accessToken, $request, $website);
//            $passport = new Passport(
//                new UserBadge($user->getEmail(), [$this->userRepository, 'loadUserByIdentifier']),
//                new PasswordCredentials(self::DEFAULT_PASSWORD)
//            );
//            $passport->addBadge(new RememberMeBadge());
//            return $passport;
//        } catch (MissingAuthorizationCodeException $e) {
//            throw new NoAuthCodeAuthenticationException();
//        } catch (IdentityProviderException $e) {
//            throw new IdentityProviderAuthenticationException($e);
//        } catch (InvalidStateException $e) {
//            throw new InvalidStateAuthenticationException($e);
//        }
//    }
//
//    /**
//     * Get user
//     *
//     * @throws Exception
//     */
//    public function getPassportByAuthToken(AccessToken $accessToken, Request $request, WebsiteModel $website): ?UserFront
//    {
//        $authUser = $this->getClient()->fetchUserFromToken($accessToken);
//
//        if (is_object($authUser)) {
//            $user = $this->entityManager->getRepository(UserFront::class)->findOneBy([$this->apiName . 'Id' => $authUser->getId()]);
//            if (!$user instanceof UserFront) {
//                $email = $authUser->getEmail();
//                $user = $this->entityManager->getRepository(UserFront::class)->findOneBy(['email' => $email]);
//                if (!$user instanceof UserFront) {
//                    $user = new UserFront();
//                    $this->registerManager->prePersist($user);
//                    $user->setPlainPassword(self::DEFAULT_PASSWORD);
//                    $user->setLocale($request->getLocale());
//                    $user->setLogin($email);
//                    $user->setFacebookId($authUser->getId());
//                    $user->setEmail($email);
//                    $user->setFirstName($authUser->getFirstName());
//                    $user->setLastName($authUser->getLastName());
//                    $this->registerManager->register($user, $website->getSecurity(), $website, NULL, true);
//                }
//            }
//        }
//
//        return !empty($user) ? $user : NULL;
//    }
//
//    /**
//     * To get Client
//     */
//    private function getClient(): OAuth2ClientInterface
//    {
//        return $this->clientRegistry->getClient($this->apiName);
//    }
//
//    /**
//     * onAuthenticationSuccess
//     */
//    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
//    {
//        return $this->frontAuthenticator->onAuthenticationSuccess($request, $token, $firewallName);
//    }
//
//    /**
//     * onAuthenticationFailure
//     */
//    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
//    {
//        return $this->baseAuthenticator->onAuthenticationFailure($request, $exception, 'security_front_login');
//    }
//
//    /**
//     * start
//     *
//     * Called when authentication is needed, but it's not sent.
//     * This redirects to the 'login'.
//     */
//    public function start(Request $request, AuthenticationException $authException = null): RedirectResponse
//    {
//        return new RedirectResponse(
//            '/connect/', /** might be the site, where users choose their oauth provider */
//            Response::HTTP_TEMPORARY_REDIRECT
//        );
//    }
// }
