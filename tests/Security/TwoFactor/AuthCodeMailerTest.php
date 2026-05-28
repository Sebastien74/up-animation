<?php

declare(strict_types=1);

namespace App\Tests\Security\TwoFactor;

use App\Security\TwoFactor\AuthCodeMailer;
use PHPUnit\Framework\TestCase;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

final class AuthCodeMailerTest extends TestCase
{
    public function testItSendsAuthCodeToUserEmail(): void
    {
        $user = $this->createMock(TwoFactorInterface::class);
        $user->method('getEmailAuthCode')->willReturn('123456');
        $user->method('getEmailAuthRecipient')->willReturn('user@example.test');

        $captured = null;
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->willReturnCallback(static function (object $email) use (&$captured): void {
                $captured = $email;
            });

        $sut = new AuthCodeMailer($mailer, 'no-reply@up-animations.test', 'Up Animations!');
        $sut->sendAuthCode($user);

        self::assertInstanceOf(TemplatedEmail::class, $captured);
        self::assertSame('front/default/actions/security/email/2fa-code.html.twig', $captured->getHtmlTemplate());
        self::assertSame('Votre code de vérification', $captured->getSubject());

        $context = $captured->getContext();
        self::assertSame('123456', $context['code']);
        self::assertSame($user, $context['user']);

        $to = $captured->getTo();
        self::assertCount(1, $to);
        self::assertSame('user@example.test', $to[0]->getAddress());

        $from = $captured->getFrom();
        self::assertCount(1, $from);
        self::assertSame('no-reply@up-animations.test', $from[0]->getAddress());
        self::assertSame('Up Animations!', $from[0]->getName());
    }

    public function testItDoesNothingWhenCodeIsNull(): void
    {
        $user = $this->createMock(TwoFactorInterface::class);
        $user->method('getEmailAuthCode')->willReturn(null);
        $user->method('getEmailAuthRecipient')->willReturn('user@example.test');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $sut = new AuthCodeMailer($mailer, 'no-reply@up-animations.test', 'Up Animations!');
        $sut->sendAuthCode($user);
    }

    public function testItDoesNothingWhenRecipientIsEmpty(): void
    {
        $user = $this->createMock(TwoFactorInterface::class);
        $user->method('getEmailAuthCode')->willReturn('123456');
        $user->method('getEmailAuthRecipient')->willReturn('');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $sut = new AuthCodeMailer($mailer, 'no-reply@up-animations.test', 'Up Animations!');
        $sut->sendAuthCode($user);
    }
}
