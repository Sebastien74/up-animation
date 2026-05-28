<?php

declare(strict_types=1);

namespace App\Tests\Service\Core;

use App\Tests\Support\MailerKernelTestCase;
use App\Tests\Support\WebsiteModelFactory;

final class MailerServiceTest extends MailerKernelTestCase
{
    public function testItSendsEmailWithSubjectFromAndTo(): void
    {
        $service = $this->createMailerService();
        $service->setWebsite(WebsiteModelFactory::create());
        $service->setLocale('fr');
        $service->setSubject('Sujet de test');
        $service->setTo(['user@example.test']);
        $service->setTemplate('front/default/actions/newsletter/email/confirmation.html.twig');
        $service->setArguments([
            'stringEmail' => 'user@example.test',
            'confirmationLink' => 'https://up-animations.test/newsletter/confirm/abc',
            'message' => null,
        ]);

        $result = $service->send();

        if (!$result->success) {
            self::fail('MailerService failed: '.($result->exception?->getMessage() ?? 'unknown'));
        }

        self::assertTrue($result->success);
        self::assertEmailCount(1);

        $email = self::getMailerMessage(0);
        self::assertEmailSubjectContains($email, 'Sujet de test');
        self::assertEmailAddressContains($email, 'To', 'user@example.test');
        self::assertEmailAddressContains($email, 'From', 'no-reply@up-animations.test');
    }

    public function testItPrefixesSubjectWithEnvironmentNameWhenNotProd(): void
    {
        $service = $this->createMailerService();
        $service->setWebsite(WebsiteModelFactory::create());
        $service->setLocale('fr');
        $service->setSubject('Newsletter');
        $service->setTo(['user@example.test']);
        $service->setTemplate('front/default/actions/newsletter/email/confirmation.html.twig');
        $service->setArguments([
            'stringEmail' => 'user@example.test',
            'confirmationLink' => 'https://up-animations.test/newsletter/confirm/abc',
            'message' => null,
        ]);

        $service->send();

        $email = self::getMailerMessage(0);
        self::assertEmailSubjectContains($email, '[TEST]');
    }

    public function testItExplodesCommaSeparatedRecipients(): void
    {
        $service = $this->createMailerService();
        $service->setWebsite(WebsiteModelFactory::create());
        $service->setLocale('fr');
        $service->setSubject('Multi-recipients');
        $service->setTo(['a@example.test,b@example.test', 'c@example.test']);
        $service->setTemplate('front/default/actions/newsletter/email/confirmation.html.twig');
        $service->setArguments([
            'stringEmail' => 'user@example.test',
            'confirmationLink' => 'https://up-animations.test/newsletter/confirm/abc',
            'message' => null,
        ]);

        $service->send();

        self::assertEmailCount(3);
    }

    public function testItSetsReplyToWhenDifferentFromSender(): void
    {
        $service = $this->createMailerService();
        $service->setWebsite(WebsiteModelFactory::create());
        $service->setLocale('fr');
        $service->setSubject('Reply-To test');
        $service->setTo(['user@example.test']);
        $service->setReplyTo('contact@up-animations.test');
        $service->setTemplate('front/default/actions/newsletter/email/confirmation.html.twig');
        $service->setArguments([
            'stringEmail' => 'user@example.test',
            'confirmationLink' => 'https://up-animations.test/newsletter/confirm/abc',
            'message' => null,
        ]);

        $service->send();

        $email = self::getMailerMessage(0);
        self::assertEmailAddressContains($email, 'Reply-To', 'contact@up-animations.test');
    }

    public function testItDoesNotSetReplyToWhenDisabledKeywordPassed(): void
    {
        $service = $this->createMailerService();
        $service->setWebsite(WebsiteModelFactory::create());
        $service->setLocale('fr');
        $service->setSubject('Disabled Reply-To');
        $service->setTo(['user@example.test']);
        $service->setReplyTo('disabled');
        $service->setTemplate('front/default/actions/newsletter/email/confirmation.html.twig');
        $service->setArguments([
            'stringEmail' => 'user@example.test',
            'confirmationLink' => 'https://up-animations.test/newsletter/confirm/abc',
            'message' => null,
        ]);

        $service->send();

        $email = self::getMailerMessage(0);
        self::assertNull($email->getHeaders()->get('Reply-To'));
    }
}
