<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Service\Content\BrowserDetection;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * BrowserRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BrowserRuntime implements RuntimeExtensionInterface
{
    private ?string $userAgent = null;

    /**
     * BrowserRuntime constructor.
     */
    public function __construct(
        private readonly BrowserDetection $browserDetection,
        private readonly RequestStack $requestStack,
        private readonly string $logDir,
    ) {
    }

    /**
     * Get current Browser.
     */
    public function browser(): ?string
    {
        return $this->browserDetection->getBrowser();
    }

    /**
     * Get current screen.
     */
    public function screen(): ?string
    {
        $request = $this->requestStack->getMainRequest();
        if ($request instanceof Request && $request->get('thumbScreen')) {
            return $request->get('thumbScreen');
        }

        if ($this->isMobile()) {
            return 'mobile';
        } elseif ($this->isTablet()) {
            return 'tablet';
        } elseif ($this->isDesktop()) {
            return 'desktop';
        }

        return null;
    }

    /**
     * Check if is desktop.
     */
    public function isDesktop(): bool
    {
        return !$this->browserDetection->isTablet() && !$this->browserDetection->isMobile();
    }

    /**
     * Check if is tablet.
     */
    public function isTablet(): bool
    {
        return $this->browserDetection->isTablet();
    }

    /**
     * Check if is mobile.
     */
    public function isMobile(): bool
    {
        return !$this->browserDetection->isTablet() && $this->browserDetection->isMobile();
    }

    /**
     * Check if is Firefox Browser.
     */
    public function isFirefox(?string $userAgent = null): bool
    {
        $this->userAgent($userAgent);

        return $this->browserDetection->is('Firefox', $this->userAgent);
    }

    /**
     * Check if is Chrome Browser.
     */
    public function isChrome(?string $userAgent = null): bool
    {
        $this->userAgent($userAgent);

        return $this->browserDetection->is('Chrome', $this->userAgent);
    }

    /**
     * Check if is Safari Browser.
     */
    public function isSafari(?string $userAgent = null): bool
    {
        $this->userAgent($userAgent);
        if ($this->isChrome($userAgent)) {
            return false;
        }

        return $this->browserDetection->is('Safari', $this->userAgent);
    }

    /**
     * Check if is Edge Browser.
     */
    public function isEdge(?string $userAgent = null): bool
    {
        $this->userAgent($userAgent);

        return $this->browserDetection->is('Edge', $this->userAgent);
    }

    /**
     * Check if is IE Browser.
     */
    public function isIE(?string $userAgent = null): bool
    {
        $this->userAgent($userAgent);

        return $this->browserDetection->is('IE', $this->userAgent);
    }

    /**
     * Check if is Opera Browser.
     */
    public function isOpera(?string $userAgent = null): bool
    {
        $this->userAgent($userAgent);

        return $this->browserDetection->is('Opera', $this->userAgent);
    }

    /**
     * To log User Agent.
     */
    public function logServerInfo(): void
    {
        $logger = new Logger('user-agent');
        $logger->pushHandler(new RotatingFileHandler($this->logDir.'/user-agent.log', 20, Level::Info));
        foreach ($_SERVER as $key => $value) {
            $logger->info($key.' : '.$value);
        }
    }

    /**
     * Set User Agent.
     */
    private function userAgent(?string $userAgent = null): void
    {
        $this->userAgent = $userAgent ?: (!empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null);
    }
}
