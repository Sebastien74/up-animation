<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Tests\Support\MailerKernelTestCase;
use App\Tests\Support\WebsiteModelFactory;

final class PasswordExpireEmailTest extends MailerKernelTestCase
{
    public function testItSendsPasswordExpiringSoonAlert(): void
    {
        $website = WebsiteModelFactory::create();
        $service = $this->createMailerService();
        $service->setWebsite($website);
        $service->setLocale('fr');
        $service->setSubject('Votre mot de passe arrive a expiration');
        $service->setTo(['user@example.test']);
        $service->setFrom('support@up-animations.test');
        $service->setReplyTo('no-reply@up-animations.test');
        $service->setTemplate('front/default/actions/security/email/password-expire.html.twig');
        $service->setArguments([
            'expire' => false,
            'user' => (object) ['locale' => 'fr'],
            'website' => $website,
            'schemeAndHttpHost' => 'https://up-animations.test',
        ]);

        $result = $service->send();

        self::assertTrue($result->success);
        self::assertEmailCount(1);

        $email = self::getMailerMessage(0);
        self::assertEmailSubjectContains($email, 'arrive a expiration');
        self::assertEmailAddressContains($email, 'To', 'user@example.test');
    }

    public function testItSendsPasswordExpiredAlert(): void
    {
        $website = WebsiteModelFactory::create();
        $service = $this->createMailerService();
        $service->setWebsite($website);
        $service->setLocale('fr');
        $service->setSubject('Votre mot de passe a expire');
        $service->setTo(['user@example.test']);
        $service->setFrom('support@up-animations.test');
        $service->setTemplate('front/default/actions/security/email/password-expire.html.twig');
        $service->setArguments([
            'expire' => true,
            'user' => (object) ['locale' => 'fr'],
            'website' => $website,
            'schemeAndHttpHost' => 'https://up-animations.test',
        ]);

        $service->send();

        self::assertEmailCount(1);
    }
}
