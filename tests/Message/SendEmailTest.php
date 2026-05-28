<?php

declare(strict_types=1);

namespace App\Tests\Message;

use App\Entity\Core\Website;
use App\Message\SendEmail;
use App\Model\Core\WebsiteModel;
use PHPUnit\Framework\TestCase;

final class SendEmailTest extends TestCase
{
    public function testItExposesAllConstructorValuesViaGetters(): void
    {
        $message = new SendEmail(
            locale: 'fr',
            subject: 'Mon sujet',
            to: ['a@example.test', 'b@example.test'],
            cc: ['cc@example.test'],
            name: 'Up Animations!',
            from: 'no-reply@up-animations.test',
            replyTo: 'contact@up-animations.test',
            template: 'front/default/email/template.html.twig',
            arguments: ['key' => 'value'],
            attachments: ['/tmp/file.pdf'],
            websiteId: 42,
        );

        self::assertSame('fr', $message->getLocale());
        self::assertSame('Mon sujet', $message->getSubject());
        self::assertSame(['a@example.test', 'b@example.test'], $message->getTo());
        self::assertSame(['cc@example.test'], $message->getCc());
        self::assertSame('Up Animations!', $message->getName());
        self::assertSame('no-reply@up-animations.test', $message->getFrom());
        self::assertSame('contact@up-animations.test', $message->getReplyTo());
        self::assertSame('front/default/email/template.html.twig', $message->getTemplate());
        self::assertSame(['key' => 'value'], $message->getArguments());
        self::assertSame(['/tmp/file.pdf'], $message->getAttachments());
        self::assertSame(42, $message->getWebsiteId());
    }

    public function testSettersStripTagsFromSubject(): void
    {
        $message = new SendEmail();
        $message->setSubject('<script>alert(1)</script>Hello');

        self::assertSame('alert(1)Hello', $message->getSubject());
    }

    public function testSetWebsiteAcceptsWebsiteEntity(): void
    {
        $website = $this->createMock(Website::class);
        $website->method('getId')->willReturn(7);

        $message = new SendEmail();
        $message->setWebsite($website);

        self::assertSame(7, $message->getWebsiteId());
    }

    public function testSetWebsiteAcceptsWebsiteModel(): void
    {
        $model = new WebsiteModel(id: 9);

        $message = new SendEmail();
        $message->setWebsite($model);

        self::assertSame(9, $message->getWebsiteId());
    }
}
