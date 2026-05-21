<?php

declare(strict_types=1);

namespace App\Security\TwoFactor;

use App\Entity\Security\User;
use App\Security\TwoFactor\Exception\InvalidTwoFactorCodeException;
use App\Security\TwoFactor\Exception\MissingPendingSecretException;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Orchestrates the TOTP enrolment flow for admin users (Scheb 2FA bundle).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class TwoFactorSetupService
{
    private const string SESSION_SECRET_KEY = 'two_factor_setup_secret';

    public function __construct(
        private readonly GoogleAuthenticatorInterface $googleAuthenticator,
        private readonly BackupCodeGenerator $backupCodeGenerator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Returns the TOTP secret currently being enrolled (kept in session until confirmation).
     */
    public function prepareSecret(SessionInterface $session): string
    {
        $secret = $session->get(self::SESSION_SECRET_KEY);

        if (!is_string($secret) || '' === $secret) {
            $secret = $this->googleAuthenticator->generateSecret();
            $session->set(self::SESSION_SECRET_KEY, $secret);
        }

        return $secret;
    }

    /**
     * Returns the otpauth:// URI for the given pending secret, used by authenticator apps.
     */
    public function getQrContent(User $user, string $pendingSecret): string
    {
        $original = $user->getGoogleAuthenticatorSecret();
        $user->setGoogleAuthenticatorSecret($pendingSecret);

        try {
            return $this->googleAuthenticator->getQRContent($user);
        } finally {
            $user->setGoogleAuthenticatorSecret($original);
        }
    }

    /**
     * Validates the first OTP code, persists the secret + freshly generated backup codes.
     *
     * When $disableEmail is true, email authentication is turned off in the same flush -
     * used by the "switch from email to TOTP" flow.
     *
     * @return list<string> the backup codes to show once
     *
     * @throws RandomException
     */
    public function confirmSetup(SessionInterface $session, User $user, string $code, bool $disableEmail = false): array
    {
        $secret = $session->get(self::SESSION_SECRET_KEY);
        if (!is_string($secret) || '' === $secret) {
            throw new MissingPendingSecretException('No pending 2FA secret in session.');
        }

        $user->setGoogleAuthenticatorSecret($secret);

        if (!$this->googleAuthenticator->checkCode($user, $code)) {
            $user->setGoogleAuthenticatorSecret(null);

            throw new InvalidTwoFactorCodeException('Submitted TOTP code did not match the pending secret.');
        }

        $backupCodes = $this->backupCodeGenerator->generate();
        $user->setBackupCodes($backupCodes);

        if ($disableEmail) {
            $user->setEmailAuthEnabled(false);
        }

        $this->entityManager->flush();
        $session->remove(self::SESSION_SECRET_KEY);

        return $backupCodes;
    }

    public function disable(User $user): void
    {
        $user->setGoogleAuthenticatorSecret(null);
        $user->setBackupCodes([]);

        $this->entityManager->flush();
    }

    public function enableEmail(User $user): void
    {
        $user->setEmailAuthEnabled(true);
        $this->entityManager->flush();
    }

    public function disableEmail(User $user): void
    {
        $user->setEmailAuthEnabled(false);
        $this->entityManager->flush();
    }

    /**
     * Atomic swap: disable TOTP (and burn backup codes) + enable email auth.
     *
     * Used when the admin chooses to replace their TOTP method by the email channel.
     */
    public function switchToEmail(User $user): void
    {
        $user->setGoogleAuthenticatorSecret(null);
        $user->setBackupCodes([]);
        $user->setEmailAuthEnabled(true);
        $user->setDisabledAuth(false);

        $this->entityManager->flush();
    }

    /**
     * Atomic prep: clear TOTP + backup codes + disable email. The target user is
     * left with no active method, so the AdminTwoFactorRequiredSubscriber will
     * force them into the TOTP onboarding flow at their next login.
     *
     * Used by top-level admins to force-rotate a user onto TOTP.
     */
    public function switchToTotpEnrolment(User $user): void
    {
        $user->setGoogleAuthenticatorSecret(null);
        $user->setBackupCodes([]);
        $user->setEmailAuthEnabled(false);
        $user->setDisabledAuth(false);

        $this->entityManager->flush();
    }

    /**
     * Kill-switch: turn off 2FA entirely for the user. All methods are cleared
     * and the disabledAuth flag is set so AdminTwoFactorRequiredSubscriber stops
     * forcing enrolment. Login becomes classic (no challenge).
     */
    public function disableAuth(User $user): void
    {
        $user->setGoogleAuthenticatorSecret(null);
        $user->setBackupCodes([]);
        $user->setEmailAuthEnabled(false);
        $user->setDisabledAuth(true);

        $this->entityManager->flush();
    }

    /**
     * Re-enables 2FA enforcement: clears the kill switch but leaves no method
     * active, so the subscriber will force enrolment at the user's next login.
     */
    public function restoreAuth(User $user): void
    {
        $user->setDisabledAuth(false);

        $this->entityManager->flush();
    }

    /**
     * @throws RandomException
     *
     * @return list<string>
     */
    public function regenerateBackupCodes(User $user): array
    {
        $codes = $this->backupCodeGenerator->generate();
        $user->setBackupCodes($codes);
        $this->entityManager->flush();

        return $codes;
    }
}
