<?php

declare(strict_types=1);

namespace App\Tests\Form\Manager\Front;

use App\Tests\Support\MailerKernelTestCase;
use App\Tests\Support\WebsiteModelFactory;

final class ContactEmailTest extends MailerKernelTestCase
{
    public function testItSendsContactFormConfirmationToSender(): void
    {
        $service = $this->createMailerService();
        $service->setWebsite(WebsiteModelFactory::create());
        $service->setLocale('fr');
        $service->setSubject('Confirmation de votre demande de contact');
        $service->setTo(['john.doe@example.test']);
        $service->setFrom('contact@up-animations.test');
        $service->setReplyTo('disabled');
        $service->setTemplate('front/default/actions/form/email/contact-confirmation.html.twig');
        $service->setArguments([
            'message' => '<p>Merci pour votre message, nous reviendrons vers vous rapidement.</p>',
        ]);

        $result = $service->send();

        self::assertTrue($result->success);
        self::assertEmailCount(1);

        $email = self::getMailerMessage(0);
        self::assertEmailSubjectContains($email, 'Confirmation de votre demande');
        self::assertEmailAddressContains($email, 'To', 'john.doe@example.test');
        self::assertEmailAddressContains($email, 'From', 'contact@up-animations.test');
        self::assertEmailHtmlBodyContains($email, 'Merci pour votre message');
    }

    public function testItSendsContactFormDefaultConfirmation(): void
    {
        $service = $this->createMailerService();
        $service->setWebsite(WebsiteModelFactory::create());
        $service->setLocale('fr');
        $service->setSubject('Confirmation');
        $service->setTo(['john.doe@example.test']);
        $service->setTemplate('front/default/actions/form/email/default-confirmation.html.twig');
        $service->setArguments([
            'message' => '<p>Votre demande a bien ete enregistree.</p>',
        ]);

        $service->send();

        self::assertEmailCount(1);

        $email = self::getMailerMessage(0);
        self::assertEmailHtmlBodyContains($email, 'enregistree');
    }
}
