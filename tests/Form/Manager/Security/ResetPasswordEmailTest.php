<?php

declare(strict_types=1);

namespace App\Tests\Form\Manager\Security;

use App\Tests\Support\MailerKernelTestCase;
use App\Tests\Support\WebsiteModelFactory;

final class ResetPasswordEmailTest extends MailerKernelTestCase
{
    public function testItSendsFrontResetPasswordEmail(): void
    {
        $service = $this->createMailerService();
        $service->setWebsite(WebsiteModelFactory::create());
        $service->setLocale('fr');
        $service->setSubject('Reinitialisation de votre mot de passe');
        $service->setTo(['frontuser@example.test']);
        $service->setFrom('no-reply@up-animations.test');
        $service->setTemplate('front/default/actions/security/email/password-request.html.twig');
        $service->setArguments([
            'token' => 'reset-token-front-123',
        ]);

        $result = $service->send();

        self::assertTrue($result->success);
        self::assertEmailCount(1);

        $email = self::getMailerMessage(0);
        self::assertEmailSubjectContains($email, 'Reinitialisation');
        self::assertEmailAddressContains($email, 'To', 'frontuser@example.test');
        self::assertEmailHtmlBodyContains($email, 'reset-token-front-123');
    }

    public function testItSendsAdminResetPasswordEmail(): void
    {
        $service = $this->createMailerService();
        $service->setWebsite(WebsiteModelFactory::create());
        $service->setLocale('fr');
        $service->setSubject('Reinitialisation de votre mot de passe administrateur');
        $service->setTo(['admin@up-animations.test']);
        $service->setFrom('no-reply@up-animations.test');
        $service->setTemplate('front/default/actions/security/email/password-request.html.twig');
        $service->setArguments([
            'token' => 'reset-token-admin-456',
        ]);

        $service->send();

        self::assertEmailCount(1);

        $email = self::getMailerMessage(0);
        self::assertEmailAddressContains($email, 'To', 'admin@up-animations.test');
        self::assertEmailHtmlBodyContains($email, 'reset-token-admin-456');
    }
}
