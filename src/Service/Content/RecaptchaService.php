<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Entity\Core\Website;
use App\Service\Security\CaptchaService;
use App\Service\Security\WebsiteSecretProvider;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * RecaptchaService.
 *
 * Front form anti-bot gate. Delegates the actual challenge verification to the
 * self-hosted proof-of-work CaptchaService, adds IP rate limiting and logs spam.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class RecaptchaService
{
    public function __construct(
        private readonly CaptchaService $captcha,
        private readonly WebsiteSecretProvider $secretProvider,
        private readonly TranslatorInterface $translator,
        private readonly RequestStack $requestStack,
        private readonly RateLimiterFactoryInterface $formSubmissionLimiter,
        private readonly string $logDir,
    ) {
    }

    /**
     * Check if the submitted form clears the anti-bot challenge.
     */
    public function execute(Website $website, mixed $entity, FormInterface $form, ?string $email = null): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        /** Rate limit by IP: applies to all front form posts, even without captcha */
        if (!$this->withinRateLimit($request)) {
            return $this->reject($request, $email, 'rate limited', $this->translator->trans('Trop de tentatives. Veuillez patienter quelques instants et réessayer.', [], 'front_form'));
        }

        if (method_exists($entity, 'isRecaptcha') && !$entity->isRecaptcha()) {
            return true;
        }

        $fields = $request instanceof Request ? (array) $request->request->all($form->getName()) : [];
        $payload = is_string($fields['field_ho'] ?? null) ? $fields['field_ho'] : null;
        $honeypot = is_string($fields['field_ho_entitled'] ?? null) ? $fields['field_ho_entitled'] : null;

        if ($this->captcha->verify($this->secretProvider->hmacKey($website), $payload, $honeypot)) {
            return true;
        }

        return $this->reject($request, $email, 'challenge failed');
    }

    private function withinRateLimit(?Request $request): bool
    {
        $clientIp = $request?->getClientIp() ?: 'unknown';

        return $this->formSubmissionLimiter->create($clientIp)->consume()->isAccepted();
    }

    private function reject(?Request $request, ?string $email, string $reason, ?string $message = null): bool
    {
        if ($request?->hasSession()) {
            $request->getSession()->getFlashBag()->add(
                'error_form',
                $message ?? $this->translator->trans('Erreur de sécurité !! Rechargez la page et réessayez.', [], 'front_form')
            );
        }

        $logger = new Logger('SPAM');
        $logger->pushHandler(new RotatingFileHandler($this->logDir.'/spams.log', 10, Level::Info));
        $context = $email ?: ($request?->getClientIp() ?? 'unknown');
        $logger->alert(sprintf('Captcha security (%s). Rejected: %s', $reason, $context));

        return false;
    }
}
