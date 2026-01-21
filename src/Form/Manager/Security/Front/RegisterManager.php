<?php

declare(strict_types=1);

namespace App\Form\Manager\Security\Front;

use App\Entity\Core as CoreEntity;
use App\Entity\Information as InformationEntity;
use App\Entity\Security as UserEntity;
use App\Model\Core\WebsiteModel;
use App\Security\LoginFrontFormAuthenticator;
use App\Service\Core\MailerService;
use App\Service\Interface\CoreLocatorInterface;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * RegisterFrontManager.
 *
 * Manage UserFront security registration
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => RegisterManager::class, 'key' => 'security_front_register_form_manager'],
])]
class RegisterManager
{
    private const string DEFAULT_PASSWORD = 'VxR%\Y!wtsr!5((';

    /**
     * RegisterManager constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly UserPasswordHasherInterface $passwordEncoder,
        private readonly UserAuthenticatorInterface $authenticator,
        private readonly LoginFrontFormAuthenticator $formAuthenticator,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly MailerService $mailer
    ) {

    }

    /**
     * To set user before registration.
     *
     * @throws Exception
     */
    public function prePersist(UserEntity\UserFront $user): void
    {
        $address = new InformationEntity\Address();
        $phone = new InformationEntity\Phone();
        $profile = new UserEntity\Profile();
        $profile->setUserFront($user);
        $profile->addPhone($phone);
        $profile->addAddress($address);
        $user->setProfile($profile);

        $securityKey = str_replace(['/', '.'], '', crypt(random_bytes(30), 'rl'));
        $user->setSecretKey($securityKey);
    }

    /**
     * Registration.
     *
     * @throws Exception
     */
    public function register(
        FormInterface $form,
        CoreEntity\Security $security,
        WebsiteModel $website,
        ?UserEntity\UserCategory $userCategory = null,
        bool $disabledConfirmation = false): ?string
    {
        $disabledAccount = $form->getConfig()->getOption('disabled_account');
        $user = $this->setUser($form, $disabledAccount, $website);
        $user->setCategory($userCategory);
        $session = new Session();

        if ('email' === $_ENV['SECURITY_FRONT_LOGIN_TYPE'] && $user->getEmail()) {
            $user->setLogin($user->getEmail());
        }

        if ($security->isFrontRegistrationValidation() && !$disabledConfirmation && !$disabledAccount) {
            $session->getFlashBag()->add('success', $this->coreLocator->translator()->trans("Merci pour votre inscription. Votre compte doit être validé par l'administrateur.", [], 'security_cms'));
            $user->setActive(false);
        } elseif ($security->isFrontEmailConfirmation() && !$disabledConfirmation && !$disabledAccount) {
            $session->getFlashBag()->add('success', $this->coreLocator->translator()->trans('Merci pour votre inscription. Un e-mail de confirmation vous a été envoyé. Pour activer votre compte vous devrez cliquer sur le lien de cet e-mail.', [], 'security_cms'));
            $user->setActive(false);
        } else {
            $session->getFlashBag()->add('success', $this->coreLocator->translator()->trans('Merci pour votre inscription.', [], 'security_cms'));
        }

        if ($security->isFrontEmailConfirmation() || $disabledConfirmation || $disabledAccount) {
            $token = $user->getEmail() ? bin2hex(random_bytes(45).md5($user->getEmail())) : null;
            $user->setToken(str_replace(['%', '/'], '', $token));
        }

        if ($security->isFrontEmailConfirmation() && !$disabledConfirmation && !$disabledAccount) {
            $user->setConfirmEmail(false);
            $this->sendConfirmEmail($user, $website, $user->getToken());
        }

        if ($security->isFrontEmailWebmaster()) {
            $this->sendWebmasterEmail($user, $website);
        }

        $this->coreLocator->em()->persist($user);
        $this->coreLocator->em()->flush();

        if (!$disabledAccount && $user->getToken() && !$disabledConfirmation && !$security->isFrontEmailConfirmation() && !$security->isFrontRegistrationValidation()) {
            return $this->coreLocator->router()->generate('security_front_auto_login', ['token' => urlencode($user->getToken())]);
        } elseif (!$disabledAccount && $user->getToken()) {
            return $this->coreLocator->router()->generate('security_front_success_registration', ['token' => urlencode($user->getToken())]);
        }

        return null;
    }

    /**
     * Auto login.
     */
    public function autoLogin(UserEntity\UserFront $user, Request $request): ?string
    {
        if ($user->isActive()) {
            $this->coreLocator->request()->request->set('_remember_me', true);
            $response = $this->authenticator->authenticateUser($user, $this->formAuthenticator, $request, [new RememberMeBadge()]);
            $loginEvent = new InteractiveLoginEvent($request, $this->coreLocator->tokenStorage()->getToken());
            $this->eventDispatcher->dispatch($loginEvent, SecurityEvents::INTERACTIVE_LOGIN);

            return $response instanceof RedirectResponse ? $response->getTargetUrl() : $this->coreLocator->request()->getSchemeAndHttpHost();
        }

        return null;
    }

    /**
     * To manage confirmation.
     *
     * @throws Exception
     */
    public function confirmation(UserEntity\UserFront $user, ?string $status = null): ?string
    {
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $tokenDate = $user->getTokenDate();
        $interval = $now->diff($tokenDate);
        $isExpired = ($now > $tokenDate) && ($interval->days >= 1 || $interval->h >= 24);
        $status = $isExpired ? 'expired' : $status;

        if ('expired' === $status || 'decline' === $status) {
            $this->coreLocator->em()->remove($user);
            $this->coreLocator->em()->flush();
        } elseif ('accept' === $status) {
            $user->setActive(true);
            $user->setConfirmEmail(true);
            $user->setTokenDate(null);
            $user->setToken(null);
            $this->coreLocator->em()->persist($user);
            $this->coreLocator->em()->flush();
        }

        $this->removeExpiredToken();

        return $status;
    }

    /**
     * To removed Email[] with expired token.
     */
    public function removeExpiredToken(): void
    {
        $expiredUsers = $this->coreLocator->em()->getRepository(UserEntity\UserFront::class)->findWithExpiredToken();
        if ($expiredUsers) {
            foreach ($expiredUsers as $user) {
                $this->coreLocator->em()->remove($user);
            }
            $this->coreLocator->em()->flush();
        }
    }

    /**
     * Generate User.
     *
     * @throws Exception
     */
    private function setUser(FormInterface $form, bool $disabledAccount, WebsiteModel $website): UserEntity\UserFront
    {
        $user = $form->getData();
        $plainPassword = $disabledAccount ? self::DEFAULT_PASSWORD : $user->getPlainPassword();

        if ($plainPassword) {
            $user->setPassword(
                $this->passwordEncoder->hashPassword($user, $plainPassword)
            );
        }

        $currentDate = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $user->setWebsite($website->entity);
        $user->setAgreeTerms(true);
        $user->setAgreesTermsAt($currentDate);
        $user->setCreatedAt($currentDate);
        $this->setGroup($user);

        return $user;
    }

    /**
     * Set User group.
     */
    private function setGroup(UserEntity\UserFront $user): void
    {
        $groupRepository = $this->coreLocator->em()->getRepository(UserEntity\Group::class);
        $group = $groupRepository->findOneBy(['slug' => 'front']);

        if (!$group) {
            $position = count($groupRepository->findAll()) + 1;
            $group = new UserEntity\Group();
            $group->setPosition($position);
            $group->setAdminName('Utilisateurs Front');
            $group->setSlug('front');
            $rolesNames = ['ROLE_USER', 'ROLE_SECURE_PAGE', 'ROLE_USER_FRONT'];
            foreach ($rolesNames as $roleName) {
                $role = $this->coreLocator->em()->getRepository(UserEntity\Role::class)->findOneBy(['name' => $roleName]);
                $group->addRole($role);
            }
            $this->coreLocator->em()->persist($group);
            $this->coreLocator->em()->flush();
        }

        $user->setGroup($group);
    }

    /**
     * Send email to UserFront email account confirmation.
     */
    public function sendConfirmEmail(UserEntity\UserFront $user, WebsiteModel $website, string $token): void
    {
        if ($user->getEmail()) {
            $frontTemplate = $website->configuration->template;
            $companyName = $website->companyName;
            $this->mailer->setSubject($companyName.' - '.$this->coreLocator->translator()->trans('Confirmation de votre e-mail', [], 'security_cms'));
            $this->mailer->setTo([$user->getEmail()]);
            $this->mailer->setTemplate('front/'.$frontTemplate.'/actions/security/email/confirmation-registration.html.twig');
            $this->mailer->setArguments(['user' => $user, 'token' => $token]);
            $this->mailer->setWebsite($website);
            $this->mailer->send();
        }
    }

    /**
     * Send email to Webmaster.
     */
    public function sendWebmasterEmail(UserEntity\UserFront $user, WebsiteModel $website): void
    {
        $adminGroup = $this->coreLocator->em()->getRepository(UserEntity\Group::class)->findOneBy(['slug' => 'administrator']);
        $webmasters = $this->coreLocator->em()->getRepository(UserEntity\User::class)->findOneBy(['group' => $adminGroup]);

        $emails = [];
        foreach ($webmasters as $webmaster) {
            $emails[] = $webmaster->getEmail();
        }

        $frontTemplate = $website->configuration->template;
        $this->mailer->setSubject($this->coreLocator->translator()->trans('Nouvel inscrit', [], 'security_cms'));
        $this->mailer->setTo($emails);
        $this->mailer->setTemplate('front/'.$frontTemplate.'/actions/security/email/webmaster-registration.html.twig');
        $this->mailer->setArguments(['user' => $user]);
        $this->mailer->setWebsite($website);
        $this->mailer->send();
    }
}
