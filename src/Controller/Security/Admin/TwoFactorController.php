<?php

declare(strict_types=1);

namespace App\Controller\Security\Admin;

use App\Controller\Admin\AdminController;
use App\Entity\Security\User;
use App\Entity\Security\UserFront;
use App\Repository\Security\UserFrontRepository;
use App\Repository\Security\UserRepository;
use App\Security\TwoFactor\Exception\InvalidTwoFactorCodeException;
use App\Security\TwoFactor\FrontTwoFactorToggleService;
use App\Security\TwoFactor\QrCodeRenderer;
use App\Security\TwoFactor\TwoFactorSetupService;
use Random\RandomException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * TwoFactorController.
 *
 * refonte-admin TOTP enrolment & deactivation (Scheb 2FA bundle).
 *
 * Initial enrolment (pending / just_enabled) is rendered with the login layout
 * so the user goes through a clean onboarding step before reaching the back-office.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/{website}/security/2fa', schemes: '%protocol%')]
class TwoFactorController extends AdminController
{
    /**
     * @throws RandomException
     */
    #[Route('/setup', name: 'admin_2fa_setup', methods: 'GET|POST')]
    public function setup(
        Request $request,
        TwoFactorSetupService $setupService,
        QrCodeRenderer $qrCodeRenderer,
    ): Response|RedirectResponse {
        /** @var User $user */
        $user = $this->getUser();

        // Global kill-switch: 2FA disabled at the website level - send the user back to their profile.
        $websiteEntity = $this->coreLocator->website()?->entity;
        if (false === $websiteEntity?->getSecurity()?->isAdminTwoFactorAuth()) {
            return $this->redirectToRoute('admin_user_profile', ['website' => $request->attributes->get('website')]);
        }

        $this->breadcrumb($request, [
            'Profil' => 'admin_user_profile',
            'Authentification à deux facteurs' => 'admin_2fa_setup',
        ]);

        if ($user->isGoogleAuthenticatorEnabled() || $user->isEmailAuthEnabled()) {
            return $this->adminRender('admin/page/security/2fa-setup.html.twig', [
                'user' => $user,
                'state' => 'enabled',
                'breadcrumb' => $this->arguments['breadcrumb'],
            ]);
        }

        $session = $request->getSession();
        $secret = $setupService->prepareSecret($session);
        $qrContent = $setupService->getQrContent($user, $secret);
        $backupCodes = null;
        $error = null;

        if ($request->isMethod('POST')) {
            $submittedCode = trim((string) $request->request->get('_auth_code', ''));

            try {
                $backupCodes = $setupService->confirmSetup($session, $user, $submittedCode);
            } catch (InvalidTwoFactorCodeException) {
                $error = 'invalid_code';
            }
        }

        return $this->render('security/2fa-onboarding.html.twig', [
            'user' => $user,
            'state' => null !== $backupCodes ? 'just_enabled' : 'pending',
            'secret' => $secret,
            'qrContent' => $qrContent,
            'qrSvg' => $qrCodeRenderer->renderSvg($qrContent),
            'backupCodes' => $backupCodes,
            'error' => $error,
            'website' => $request->attributes->get('website'),
        ]);
    }

    /**
     * Switch flow: replace email auth by TOTP. Renders the onboarding template
     * with a "switch" notice; on valid code submission, enables TOTP and disables
     * email in the same flush.
     *
     * @throws RandomException
     */
    #[Route('/switch/totp', name: 'admin_2fa_switch_to_totp', methods: 'GET|POST')]
    public function switchToTotp(
        Request $request,
        TwoFactorSetupService $setupService,
        QrCodeRenderer $qrCodeRenderer,
    ): Response|RedirectResponse {
        /** @var User $user */
        $user = $this->getUser();

        // Only meaningful when email is the sole active method.
        if ($user->isGoogleAuthenticatorEnabled() || !$user->isEmailAuthEnabled()) {
            return $this->redirectToRoute('admin_2fa_setup', ['website' => $request->attributes->get('website')]);
        }

        $session = $request->getSession();
        $secret = $setupService->prepareSecret($session);
        $qrContent = $setupService->getQrContent($user, $secret);
        $backupCodes = null;
        $error = null;

        if ($request->isMethod('POST')) {
            $submittedCode = trim((string) $request->request->get('_auth_code', ''));

            try {
                $backupCodes = $setupService->confirmSetup($session, $user, $submittedCode, disableEmail: true);
            } catch (InvalidTwoFactorCodeException) {
                $error = 'invalid_code';
            }
        }

        return $this->render('security/2fa-onboarding.html.twig', [
            'user' => $user,
            'state' => null !== $backupCodes ? 'just_enabled' : 'pending',
            'switchMode' => 'from_email',
            'secret' => $secret,
            'qrContent' => $qrContent,
            'qrSvg' => $qrCodeRenderer->renderSvg($qrContent),
            'backupCodes' => $backupCodes,
            'error' => $error,
            'website' => $request->attributes->get('website'),
        ]);
    }

    /**
     * Switch flow: replace TOTP by email auth. Atomic swap + force re-login so
     * Scheb's email challenge fires on the next authentication.
     */
    #[Route('/switch/email', name: 'admin_2fa_switch_to_email', methods: 'POST')]
    public function switchToEmail(Request $request, TwoFactorSetupService $setupService): RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('2fa_switch_to_email', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if (!$user->isGoogleAuthenticatorEnabled() || $user->isEmailAuthEnabled()) {
            return $this->redirectToRoute('admin_2fa_setup', ['website' => $request->attributes->get('website')]);
        }

        $setupService->switchToEmail($user);

        $this->addFlash('success', $this->coreLocator->translator()->trans(
            "Mode d'authentification modifié : code par e-mail. Reconnectez-vous pour recevoir votre code.",
            [],
            'security_cms'
        ));

        return $this->redirectToRoute('app_logout');
    }

    #[Route('/disable', name: 'admin_2fa_disable', methods: 'POST')]
    public function disable(Request $request, TwoFactorSetupService $setupService): RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('2fa_disable', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $setupService->disable($user);

        return $this->redirectToRoute('admin_2fa_setup', ['website' => $request->attributes->get('website')]);
    }

    #[Route('/email/enable', name: 'admin_2fa_email_enable', methods: 'POST')]
    public function enableEmail(Request $request, TwoFactorSetupService $setupService): RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('2fa_email_enable', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $setupService->enableEmail($user);

        // Force a fresh login so Scheb's email challenge actually fires for this user.
        $this->addFlash('success', $this->coreLocator->translator()->trans(
            'Double authentification par e-mail activée. Reconnectez-vous pour recevoir votre code.',
            [],
            'security_cms'
        ));

        return $this->redirectToRoute('app_logout');
    }

    #[Route('/email/disable', name: 'admin_2fa_email_disable', methods: 'POST')]
    public function disableEmail(Request $request, TwoFactorSetupService $setupService): RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('2fa_email_disable', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $setupService->disableEmail($user);

        return $this->redirectToRoute('admin_2fa_setup', ['website' => $request->attributes->get('website')]);
    }

    /**
     * @throws RandomException
     */
    #[Route('/backup-codes/regenerate', name: 'admin_2fa_regenerate_backup_codes', methods: 'POST')]
    public function regenerateBackupCodes(Request $request, TwoFactorSetupService $setupService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('2fa_regenerate', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if (!$user->isGoogleAuthenticatorEnabled()) {
            return $this->redirectToRoute('admin_2fa_setup', ['website' => $request->attributes->get('website')]);
        }

        $codes = $setupService->regenerateBackupCodes($user);

        $this->breadcrumb($request, [
            'Profil' => 'admin_user_profile',
            'Authentification à deux facteurs' => 'admin_2fa_setup',
        ]);

        return $this->adminRender('admin/page/security/2fa-setup.html.twig', [
            'user' => $user,
            'state' => 'codes_regenerated',
            'backupCodes' => $codes,
            'breadcrumb' => $this->arguments['breadcrumb'],
        ]);
    }

    /**
     * Lists every admin user with their current 2FA state, for back-office reset
     * by a top-level administrator (typically when a user loses their TOTP device).
     *
     * Non-internal administrators do not see internal accounts in the list.
     */
    #[Route('/users', name: 'admin_2fa_users_index', methods: 'GET')]
    public function usersIndex(Request $request, UserRepository $userRepository): Response
    {
        $this->breadcrumb($request, ['Double authentification' => 'admin_2fa_users_index']);

        $excludeInternal = !$this->isGranted('ROLE_INTERNAL');

        return $this->adminRender('admin/page/security/2fa-users-index.html.twig', [
            'users' => $userRepository->findAllEmailAlphabetical($excludeInternal),
            'currentUserId' => $this->getUser()?->getId(),
            'breadcrumb' => $this->arguments['breadcrumb'],
        ]);
    }

    /**
     * Defensive guard: prevents a non-internal administrator from toggling 2FA on
     * an internal account through a crafted POST, even if access is opened up.
     */
    private function denyIfTargetIsInternalAndViewerIsNot(User $user): void
    {
        if (!$this->isGranted('ROLE_INTERNAL') && in_array('ROLE_INTERNAL', $user->getRoles(), true)) {
            throw $this->createAccessDeniedException();
        }
    }

    /**
     * Force-disables a target user's TOTP (and burns their backup codes).
     */
    #[Route('/users/{user}/disable-totp', name: 'admin_2fa_users_disable_totp', methods: 'POST')]
    public function adminDisableTotp(
        Request $request,
        #[MapEntity(id: 'user')] User $user,
        TwoFactorSetupService $setupService,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('2fa_users_disable_totp_'.$user->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $this->denyIfTargetIsInternalAndViewerIsNot($user);

        $setupService->disable($user);

        $this->addFlash('success', $this->coreLocator->translator()->trans(
            'Application d\'authentification désactivée pour %email%.',
            ['%email%' => $user->getEmail()],
            'security_cms'
        ));

        return $this->redirectToRoute('admin_2fa_users_index', ['website' => $request->attributes->get('website')]);
    }

    /**
     * Force-disables a target user's email-based 2FA.
     */
    #[Route('/users/{user}/disable-email', name: 'admin_2fa_users_disable_email', methods: 'POST')]
    public function adminDisableEmail(
        Request $request,
        #[MapEntity(id: 'user')] User $user,
        TwoFactorSetupService $setupService,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('2fa_users_disable_email_'.$user->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $this->denyIfTargetIsInternalAndViewerIsNot($user);

        $setupService->disableEmail($user);

        $this->addFlash('success', $this->coreLocator->translator()->trans(
            'Authentification par e-mail désactivée pour %email%.',
            ['%email%' => $user->getEmail()],
            'security_cms'
        ));

        return $this->redirectToRoute('admin_2fa_users_index', ['website' => $request->attributes->get('website')]);
    }

    /**
     * Force-enables a target user's email-based 2FA. The user receives the email
     * challenge at the next login (Scheb generates the code on demand).
     */
    #[Route('/users/{user}/enable-email', name: 'admin_2fa_users_enable_email', methods: 'POST')]
    public function adminEnableEmail(
        Request $request,
        #[MapEntity(id: 'user')] User $user,
        TwoFactorSetupService $setupService,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('2fa_users_enable_email_'.$user->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $this->denyIfTargetIsInternalAndViewerIsNot($user);

        // Mutually exclusive: enabling email also disables any TOTP secret + clears backup codes.
        $setupService->switchToEmail($user);

        $this->addFlash('success', $this->coreLocator->translator()->trans(
            'Authentification par e-mail activée pour %email%.',
            ['%email%' => $user->getEmail()],
            'security_cms'
        ));

        return $this->redirectToRoute('admin_2fa_users_index', ['website' => $request->attributes->get('website')]);
    }

    /**
     * Force-activates TOTP enrolment for a target user: clears any existing TOTP
     * secret, burns backup codes, disables email auth. The target user will be
     * redirected by AdminTwoFactorRequiredSubscriber to the onboarding flow at
     * their next login to scan a QR code and validate their first TOTP code.
     */
    #[Route('/users/{user}/enable-totp', name: 'admin_2fa_users_enable_totp', methods: 'POST')]
    public function adminEnableTotp(
        Request $request,
        #[MapEntity(id: 'user')] User $user,
        TwoFactorSetupService $setupService,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('2fa_users_enable_totp_'.$user->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $this->denyIfTargetIsInternalAndViewerIsNot($user);

        $setupService->switchToTotpEnrolment($user);

        $this->addFlash('success', $this->coreLocator->translator()->trans(
            "Enrôlement de l'application d'authentification demandé pour %email%. L'utilisateur scannera un QR code à sa prochaine connexion.",
            ['%email%' => $user->getEmail()],
            'security_cms'
        ));

        return $this->redirectToRoute('admin_2fa_users_index', ['website' => $request->attributes->get('website')]);
    }

    /**
     * Kill-switch: turns 2FA off entirely for the target user. Login becomes
     * classic (no challenge). Reversible via adminRestoreAuth.
     */
    #[Route('/users/{user}/disable-auth', name: 'admin_2fa_users_disable_auth', methods: 'POST')]
    public function adminDisableAuth(
        Request $request,
        #[MapEntity(id: 'user')] User $user,
        TwoFactorSetupService $setupService,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('2fa_users_disable_auth_'.$user->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $this->denyIfTargetIsInternalAndViewerIsNot($user);

        $setupService->disableAuth($user);

        $this->addFlash('success', $this->coreLocator->translator()->trans(
            'Double authentification désactivée pour %email%. La connexion se fera désormais sans vérification de code.',
            ['%email%' => $user->getEmail()],
            'security_cms'
        ));

        return $this->redirectToRoute('admin_2fa_users_index', ['website' => $request->attributes->get('website')]);
    }

    /**
     * Lifts the kill switch - user is back under the standard 2FA enforcement
     * (will be forced to enrol a method at next login).
     */
    #[Route('/users/{user}/restore-auth', name: 'admin_2fa_users_restore_auth', methods: 'POST')]
    public function adminRestoreAuth(
        Request $request,
        #[MapEntity(id: 'user')] User $user,
        TwoFactorSetupService $setupService,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('2fa_users_restore_auth_'.$user->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $this->denyIfTargetIsInternalAndViewerIsNot($user);

        $setupService->restoreAuth($user);

        $this->addFlash('success', $this->coreLocator->translator()->trans(
            "Double authentification réactivée pour %email%. L'utilisateur devra enrôler une méthode à sa prochaine connexion.",
            ['%email%' => $user->getEmail()],
            'security_cms'
        ));

        return $this->redirectToRoute('admin_2fa_users_index', ['website' => $request->attributes->get('website')]);
    }

    /**
     * Lists every front user with their current email 2FA state. Front users only
     * support email-based 2FA (no TOTP), so this index is simpler than the admin one.
     */
    #[IsGranted('ROLE_INTERNAL')]
    #[Route('/users-front', name: 'admin_2fa_users_front_index', methods: 'GET')]
    public function usersFrontIndex(Request $request, UserFrontRepository $userFrontRepository): Response
    {
        $this->breadcrumb($request, ['Double authentification front' => 'admin_2fa_users_front_index']);

        return $this->adminRender('admin/page/security/2fa-users-front-index.html.twig', [
            'users' => $userFrontRepository->findAllEmailAlphabetical(),
            'breadcrumb' => $this->arguments['breadcrumb'],
        ]);
    }

    /**
     * Toggles a front user's email-based 2FA. Reuses the front toggle service to
     * keep the activation logic centralized.
     */
    #[IsGranted('ROLE_INTERNAL')]
    #[Route('/users-front/{user}/toggle-email', name: 'admin_2fa_users_front_toggle_email', methods: 'POST')]
    public function adminToggleFrontEmail(
        Request $request,
        #[MapEntity(id: 'user')] UserFront $user,
        FrontTwoFactorToggleService $toggleService,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('2fa_users_front_toggle_email_'.$user->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $newState = $toggleService->toggle($user);

        $message = $newState
            ? 'Authentification par e-mail activée pour %email%.'
            : 'Authentification par e-mail désactivée pour %email%.';

        $this->addFlash('success', $this->coreLocator->translator()->trans(
            $message,
            ['%email%' => $user->getEmail()],
            'security_cms'
        ));

        return $this->redirectToRoute('admin_2fa_users_front_index', ['website' => $request->attributes->get('website')]);
    }
}
