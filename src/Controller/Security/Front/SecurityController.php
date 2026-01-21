<?php

declare(strict_types=1);

namespace App\Controller\Security\Front;

use App\Controller\Front\FrontController;
use App\Entity\Core\Configuration;
use App\Entity\Core\Module;
use App\Entity\Security\UserFront;
use App\Entity\Security\UserRequest;
use App\Form\Manager\Security\Front\ProfileManager;
use App\Form\Manager\Security\Front\RegisterManager;
use App\Form\Type\Security\Front\LoginType;
use App\Form\Type\Security\Front\RegistrationType;
use App\Model\Core\WebsiteModel;
use App\Repository\Core\WebsiteRepository;
use App\Repository\Security\UserFrontRepository;
use App\Security\BaseAuthenticator;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * SecurityController.
 *
 * Front User security controller to manage auth User
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SecurityController extends FrontController
{
    private const bool SEPARATE_FORMS = false;

    /**
     * Login page.
     *
     * @throws Exception
     */
    #[Route([
        'fr' => '/espace-personnel/identification/{view}',
        'en' => '/personal-space/identification/{view}',
        'es' => '/espacio-personal/identificacion/{view}',
        'it' => '/spazio-personale/identificazione/{view}',
    ], name: 'security_front_forms', defaults: ['view' => null], methods: 'GET|POST', schemes: '%protocol%')]
    public function forms(Request $request, ?string $view = null): RedirectResponse|Response
    {
        if (self::SEPARATE_FORMS) {
            return $this->redirectToRoute('security_front_login');
        }

        $website = $this->getWebsite();
        $websiteTemplate = $website->configuration->template;
        $security = $website->security;

        if (!$this->secureModuleActive($request, $website)) {
            return $this->redirectToRoute('front_index');
        } elseif ($this->getUser() instanceof UserFront) {
            return $this->redirect($website->securityDashboardUrl);
        }

        return $this->render('front/'.$websiteTemplate.'/actions/security/front/forms.html.twig', array_merge([
            'view' => $view,
            'templateName' => 'security-front',
            'security' => $security,
        ], $this->defaultArgs($website)));
    }

    /**
     * Login page.
     *
     * @throws Exception
     */
    #[Route([
        'fr' => '/espace-personnel/connexion/{view}',
        'en' => '/personal-space/login/{view}',
        'es' => '/espacio-personal/accesso/{view}',
        'it' => '/spazio-personale/accesso/{view}',
    ], name: 'security_front_login', defaults: ['view' => null], methods: 'GET|POST', schemes: '%protocol%')]
    public function login(
        Request $request,
        AuthenticationUtils $authenticationUtils,
        ?string $view = null,
    ): RedirectResponse|Response {

        $website = $this->getWebsite();
        $websiteTemplate = $website->configuration->template;
        $template = $view ?: 'page';
        $template = 'front/'.$websiteTemplate.'/actions/security/front/login-'.$template.'.html.twig';
        $secureActive = $this->secureModuleActive($request, $website);
        $security = $website->security;

        if (!$secureActive) {
            return 'page' === $template ? $this->redirectToRoute('front_index') : new Response();
        }

        if ($this->getUser() instanceof UserFront) {
            return $this->redirect($website->securityDashboardUrl);
        }

        $form = $this->createForm(LoginType::class);
        $form->handleRequest($request);

        $arguments = array_merge([
            'templateName' => 'security-front',
            'security' => $security,
            'form' => $form->createView(),
            'secureActive' => $secureActive,
            'login_type' => $_ENV['SECURITY_FRONT_LOGIN_TYPE'],
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'separateForms' => self::SEPARATE_FORMS,
        ], $this->defaultArgs($website));

        return $this->render($template, $arguments);
    }

    /**
     * Register.
     *
     * @throws NonUniqueResultException|Exception|InvalidArgumentException
     */
    #[Route([
        'fr' => '/espace-personnel/inscription/{view}',
        'en' => '/personal-space/sign-up/{view}',
        'es' => '/espacio-personal/inscribirse/{view}',
        'it' => '/spazio-personale/registro/{view}',
    ], name: 'security_front_register', defaults: ['category' => null, 'view' => null], methods: 'GET|POST', schemes: '%protocol%')]
    public function register(
        Request $request,
        WebsiteRepository $websiteRepository,
        RegisterManager $manager,
        BaseAuthenticator $baseAuthenticator,
        ?string $view = null,
    ): RedirectResponse|JsonResponse|Response {

        $website = $websiteRepository->findOneByHost($request->getHost());
        $this->coreLocator->em()->refresh($website->entity);
        $websiteTemplate = $website->configuration->template;
        $template = $view ?: 'page';
        $template = 'front/'.$websiteTemplate.'/actions/security/front/register-'.$template.'.html.twig';
        $security = $website->security;

        if (!$security->isFrontRegistration()) {
            return 'page' === $template ? $this->redirectToRoute('front_index') : new Response();
        }

        $user = new UserFront();
        $manager->prePersist($user);
        $form = $this->createForm(RegistrationType::class, $user, [
            'website' => $website->entity,
            'disabled_account' => false,
        ]);
        $form->handleRequest($request);

        $arguments = array_merge([
            'templateName' => 'security-front',
            'security' => $security,
            'post' => $form->isSubmitted(),
            'form' => $form->createView(),
        ], $this->defaultArgs($website));

        if ($request->cookies->get('SECURITY_ERROR')) {
            $session = new Session();
            $session->getFlashBag()->add('error', $request->cookies->get('SECURITY_ERROR'));
        }

        if ($form->isSubmitted()) {
            $isValid = !$request->get('update_form') && $form->isValid() && $baseAuthenticator->checkRecaptcha($website, $request, true);
            $redirection = $isValid ? $manager->register($form, $security, $website) : null;
            return new JsonResponse([
                'success' => $isValid,
                'redirection' => $redirection,
                'html' => $this->renderView($template, $arguments),
            ]);
        }

        return $this->render($template, $arguments);
    }

    /**
     * Request password.
     */
    #[Route([
        'fr' => '/espace-personnel/confirmation-inscription',
        'en' => '/personal-space/confirmation-registration',
        'es' => '/espacio-personal/confirmacion-registro',
        'it' => '/spazio-personale/conferma-registrazione',
    ], name: 'security_front_success_registration', methods: 'GET|POST', schemes: '%protocol%')]
    public function registerSuccess(): JsonResponse|Response
    {
        $website = $this->getWebsite();
        $configuration = $website->configuration;
        $websiteTemplate = $configuration->template;

        return $this->render('front/'.$websiteTemplate.'/actions/security/front/register-success.html.twig', array_merge([], $this->defaultArgs($website)));
    }

    /**
     * Auto login.
     */
    #[Route([
        'fr' => '/espace-personnel/front/security/auto-login/{token}/{user}',
        'en' => '/personal-space/front/security/auto-login/{token}/{user}',
        'es' => '/espacio-personal/front/security/auto-login/{token}/{user}',
        'it' => '/spazio-personale/front/security/auto-login/{token}/{user}',
    ], name: 'security_front_auto_login', defaults: ['user' => null], methods: 'GET', schemes: '%protocol%')]
    public function autoLogin(
        Request $request,
        RegisterManager $manager,
        UserFrontRepository $userFrontRepository,
        BaseAuthenticator $baseAuthenticator,
        string $token,
        ?UserFront $user = null,
    ): RedirectResponse {

        $userByToken = $userFrontRepository->findOneBy(['token' => urldecode($token)]);
        $response = $manager->autoLogin($userByToken, $request);

        if (!$userByToken) {
            return $this->redirectToRoute('front_index');
        } elseif (!$userByToken->isActive()) {
            $website = $this->getWebsite();
            $message = $baseAuthenticator->getInactiveMessage($website);
            $session = new Session();
            $session->getFlashBag()->add('warning', $message);

            return $this->redirectToRoute('security_front_forms');
        } elseif (!$user || $user instanceof UserFront && !$this->getUser()) {
            return $this->redirectToRoute('security_front_auto_login', ['token' => $token, 'user' => $userByToken->getId()]);
        }

        return $this->redirect($response);
    }

    /**
     * Email confirmation page.
     *
     * @throws Exception
     */
    #[Route([
        'fr' => '/espace-personnel/renvoi-email/confirmation',
        'en' => '/personal-space/resend-email/confirmation',
        'es' => '/espacio-personal/reenviar-correo-electronico/confirmacion',
        'it' => '/spazio-personale/restituisci-email/conferma',
    ], name: 'security_front_resend_email', methods: 'GET', schemes: '%protocol%')]
    public function resendConfirmEmail(Request $request, RegisterManager $registerManager): RedirectResponse
    {
        /** @var UserFront $userFront */
        $userFront = $this->getUser();

        if (!$userFront instanceof UserFront || $userFront->getConfirmEmail()) {
            return $this->redirectToRoute('front_index');
        }

        $website = $this->getWebsite();

        if (!$userFront->getToken()) {
            $token = base64_encode(uniqid().password_hash($userFront->getEmail(), PASSWORD_BCRYPT).random_bytes(10));
            $token = substr(str_shuffle($token), 0, 30);
            $userFront->setToken($token);
            $this->coreLocator->em()->persist($userFront);
            $this->coreLocator->em()->flush();
        }

        $registerManager->sendConfirmEmail($userFront, $website, $userFront->getToken());

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Email confirmation page.
     *
     * @throws Exception
     */
    #[Route([
        'fr' => '/email/confirmation/mon-espace-personnel/{token}/{status}',
        'en' => '/email/confirmation/my-personal-space/{token}/{status}',
        'es' => '/email/confirmacion/mi-espacio-personal/{token}/{status}',
        'it' => '/email/conferma/mio-spazio-personale/{token}/{status}',
    ], name: 'security_front_confirmation', defaults: ['status' => null], methods: 'GET', schemes: '%protocol%', priority: 1)]
    public function confirmation(string $token, UserFrontRepository $userFrontRepository, RegisterManager $registerManager, ?string $status = null): RedirectResponse|Response
    {
        $website = $this->getWebsite();
        $websiteTemplate = $website->configuration->template;
        $security = $website->security;
        $token = urldecode($token);
        $user = $token ? $userFrontRepository->findOneBy(['token' => $token]) : null;

        if (!$security->isFrontRegistration() || !$user) {
            return $this->redirectToRoute('front_index');
        }

        $status = $registerManager->confirmation($user, $status);

        return $this->render('front/'.$websiteTemplate.'/actions/security/front/confirmation.html.twig', array_merge([
            'templateName' => 'security-front',
            'user' => $user,
            'status' => $status,
        ], $this->defaultArgs($website)));
    }

    /**
     * To clear user request.
     */
    #[Route([
        'fr' => '/espace-personnel/clear-user-request/{token}',
        'en' => '/personal-space/clear-user-request/{token}',
        'es' => '/espacio-personal/clear-user-request/{token}',
        'it' => '/spazio-personale/clear-user-request/{token}',
    ], name: 'security_front_clear_user_request', methods: 'GET', schemes: '%protocol%', priority: 1)]
    public function clearUserRequest(string $token): Response
    {
        $userRequest = $this->coreLocator->em()->getRepository(UserRequest::class)->findOneBy(['token' => urldecode($token)]);
        if ($userRequest) {
            $this->coreLocator->em()->remove($userRequest);
            $this->coreLocator->em()->flush();
        } else {
            return $this->redirectToRoute('security_front_login');
        }

        $website = $this->getWebsite();
        $websiteTemplate = $website->configuration->template;

        return $this->render('front/'.$websiteTemplate.'/actions/security/front/user-request.html.twig', array_merge([
            'type' => 'clear'
        ], $this->defaultArgs($website)));
    }

    /**
     * To clear user request.
     */
    #[Route([
        'fr' => '/espace-personnel/confirm-user-request/{token}',
        'en' => '/personal-space/confirm-user-request/{token}',
        'es' => '/espacio-personal/confirm-user-request/{token}',
        'it' => '/spazio-personale/confirm-user-request/{token}',
    ], name: 'security_front_confirm_user_request', methods: 'GET', schemes: '%protocol%', priority: 1)]
    public function confirmUserRequest(ProfileManager $manager, string $token): Response
    {
        $userRequest = $this->coreLocator->em()->getRepository(UserRequest::class)->findOneBy(['token' => urldecode($token)]);

        if ($userRequest && $userRequest->getUserFront()) {
            $manager->confirmUserRequest($userRequest);
        } else {
            return $this->redirectToRoute('security_front_login');
        }

        $website = $this->getWebsite();
        $websiteTemplate = $website->configuration->template;

        return $this->render('front/'.$websiteTemplate.'/actions/security/front/user-request.html.twig', array_merge([
            'type' => 'confirm'
        ], $this->defaultArgs($website)));
    }

    /**
     * To clear user request.
     */
    #[Route([
        'fr' => '/espace-personnel/clear-remove-user-request',
        'en' => '/personal-space/clear-remove-user-request',
        'es' => '/espacio-personal/clear-remove-user-request',
        'it' => '/spazio-personale/clear-remove-user-request',
    ], name: 'security_front_clear_user_remove_request', methods: 'GET', schemes: '%protocol%', priority: 1)]
    public function clearRemoveUserRequest(): Response
    {
        /** @var UserFront $user */
        $user = $this->getUser();
        if ($user) {
            $user->setTokenRemoveRequest(null);
            $user->setTokenRemoveRequestDate(null);
            $this->coreLocator->em()->flush($user);
            $this->coreLocator->em()->flush();
        } else {
            return $this->redirectToRoute('security_front_login');
        }

        $website = $this->getWebsite();
        $websiteTemplate = $website->configuration->template;

        return $this->render('front/'.$websiteTemplate.'/actions/security/front/user-request.html.twig', array_merge([
            'type' => 'clear-remove'
        ], $this->defaultArgs($website)));
    }

    /**
     * To clear user request.
     */
    #[Route([
        'fr' => '/espace-personnel/remove-user',
        'en' => '/personal-space/remove-user',
        'es' => '/espacio-personal/remove-user',
        'it' => '/spazio-personale/remove-user',
    ], name: 'security_front_user_remove', methods: 'GET', schemes: '%protocol%', priority: 1)]
    public function removeUser(): Response
    {
        $website = $this->getWebsite();
        $websiteTemplate = $website->configuration->template;

        return $this->render('front/'.$websiteTemplate.'/actions/security/front/user-request.html.twig', array_merge([
            'type' => 'remove-user'
        ], $this->defaultArgs($website)));
    }

    /**
     * To check if secure module activated.
     */
    private function secureModuleActive(Request $request, WebsiteModel $website)
    {
        $secureModuleRole = $request->get('tpl-form') ? 'ROLE_SECURE_MODULE' : 'ROLE_SECURE_PAGE';
        $secureModule = $this->coreLocator->em()->getRepository(Module::class)->findOneBy(['role' => $secureModuleRole]);

        return $secureModule instanceof Module ? $this->coreLocator->em()->getRepository(Configuration::class)->moduleExist($website->entity, $secureModule) : null;
    }
}
