<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Service\Core\MailerService;
use App\Service\Interface\CoreLocatorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

abstract class MailerKernelTestCase extends KernelTestCase
{
    use MailerAssertionsTrait;

    protected MailerInterface $mailer;
    protected Environment $twig;
    protected TranslatorInterface $translator;
    protected RequestStack $requestStack;
    protected CoreLocatorInterface&MockObject $coreLocator;

    protected function setUp(): void
    {
        $_ENV['APP_ENV'] = 'test';

        self::bootKernel();
        $container = static::getContainer();

        $this->mailer = $container->get(MailerInterface::class);
        $this->twig = $container->get(Environment::class);
        $this->translator = $container->get(TranslatorInterface::class) ?? new Translator('fr');

        $request = Request::create('https://up-animations.test/');
        $request->setLocale('fr');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $this->requestStack = $container->get('request_stack');
        $this->requestStack->push($request);

        $this->coreLocator = $this->createMock(CoreLocatorInterface::class);
        $this->coreLocator->method('translator')->willReturn($this->translator);
        $this->coreLocator->method('requestStack')->willReturn($this->requestStack);
        $this->coreLocator->method('request')->willReturn($request);
        $this->coreLocator->method('logDir')->willReturn(sys_get_temp_dir());
        $this->coreLocator->method('isDebug')->willReturn(false);
        $this->coreLocator->method('checkIP')->willReturn(false);
    }

    protected function createMailerService(): MailerService
    {
        return new MailerService($this->mailer, $this->twig, $this->coreLocator);
    }
}
