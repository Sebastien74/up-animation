<?php

declare(strict_types=1);

namespace App\Tests\Form\Manager\Front;

use App\Tests\Support\MailerKernelTestCase;
use App\Tests\Support\WebsiteModelFactory;

final class NewsletterEmailTest extends MailerKernelTestCase
{
    public function testItSendsNewsletterConfirmationEmail(): void
    {
        $service = $this->createMailerService();
        $service->setWebsite(WebsiteModelFactory::create());
        $service->setLocale('fr');
        $service->setSubject('Confirmez votre inscription a notre newsletter');
        $service->setTo(['subscriber@example.test']);
        $service->setFrom('newsletter@up-animations.test');
        $service->setReplyTo('disabled');
        $service->setTemplate('front/default/actions/newsletter/email/confirmation.html.twig');
        $service->setArguments([
            'stringEmail' => 'subscriber@example.test',
            'confirmationLink' => 'https://up-animations.test/newsletter/confirm/token-123',
            'message' => null,
        ]);

        $result = $service->send();

        self::assertTrue($result->success);
        self::assertEmailCount(1);

        $email = self::getMailerMessage(0);
        self::assertEmailSubjectContains($email, 'Confirmez votre inscription');
        self::assertEmailAddressContains($email, 'To', 'subscriber@example.test');
        self::assertEmailAddressContains($email, 'From', 'newsletter@up-animations.test');
        self::assertEmailHtmlBodyContains($email, 'token-123');
    }

    public function testItSendsNewsletterWebmasterEmail(): void
    {
        $service = $this->createMailerService();
        $service->setWebsite(WebsiteModelFactory::create());
        $service->setLocale('fr');
        $service->setSubject('Nouvel inscrit a la newsletter');
        $service->setTo(['webmaster@up-animations.test']);
        $service->setFrom('newsletter@up-animations.test');
        $service->setReplyTo('subscriber@example.test');
        $service->setTemplate('front/default/actions/newsletter/email/webmaster.html.twig');
        $service->setArguments(['stringEmail' => 'subscriber@example.test']);

        $service->send();

        self::assertEmailCount(1);

        $email = self::getMailerMessage(0);
        self::assertEmailSubjectContains($email, 'Nouvel inscrit');
        self::assertEmailAddressContains($email, 'To', 'webmaster@up-animations.test');
        self::assertEmailAddressContains($email, 'Reply-To', 'subscriber@example.test');
    }
}
