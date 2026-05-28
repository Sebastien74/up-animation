<?php

declare(strict_types=1);

namespace App\Tests\Form\Manager\Security\Front;

use App\Tests\Support\MailerKernelTestCase;
use App\Tests\Support\WebsiteModelFactory;

final class RegistrationEmailTest extends MailerKernelTestCase
{
    public function testItSendsRegistrationConfirmationToUser(): void
    {
        $service = $this->createMailerService();
        $service->setWebsite(WebsiteModelFactory::create());
        $service->setLocale('fr');
        $service->setSubject('Finalisez votre inscription');
        $service->setTo(['newuser@example.test']);
        $service->setFrom('no-reply@up-animations.test');
        $service->setTemplate('front/default/actions/security/email/confirmation-registration.html.twig');
        $service->setArguments([
            'token' => 'registration-token-abc',
            'user' => (object) [
                'username' => 'newuser',
                'email' => 'newuser@example.test',
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
        ]);

        $result = $service->send();

        self::assertTrue($result->success);
        self::assertEmailCount(1);

        $email = self::getMailerMessage(0);
        self::assertEmailSubjectContains($email, 'Finalisez votre inscription');
        self::assertEmailAddressContains($email, 'To', 'newuser@example.test');
        self::assertEmailHtmlBodyContains($email, 'registration-token-abc');
    }
}
