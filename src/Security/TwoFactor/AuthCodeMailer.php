<?php

declare(strict_types=1);

namespace App\Security\TwoFactor;

use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Sends the one-time email 2FA code for any user implementing EmailTwoFactorInterface
 * (UserFront opt-in and admin User if email factor enabled).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class AuthCodeMailer implements AuthCodeMailerInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $senderEmail,
        private readonly string $senderName,
    ) {
    }

    public function sendAuthCode(TwoFactorInterface $user): void
    {
        $code = $user->getEmailAuthCode();
        $recipient = $user->getEmailAuthRecipient();

        if (null === $code || '' === $recipient) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to($recipient)
            ->subject('Votre code de vérification')
            ->htmlTemplate('front/default/actions/security/email/2fa-code.html.twig')
            ->context([
                'code' => $code,
                'user' => $user,
            ]);

        $this->mailer->send($email);
    }
}
