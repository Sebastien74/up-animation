<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Core\Website;
use App\Service\Security\CaptchaService;
use App\Service\Security\WebsiteSecretProvider;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * RecaptchaAuthenticator.
 *
 * Anti-bot gate for registration/security posts. Delegates verification to the
 * self-hosted proof-of-work CaptchaService.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class RecaptchaAuthenticator
{
    public function __construct(
        private readonly CaptchaService $captcha,
        private readonly WebsiteSecretProvider $secretProvider,
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $logDir,
    ) {
    }

    /**
     * Check if is valid POST.
     */
    public function execute(Request $request): bool
    {
        $website = $this->entityManager->getRepository(Website::class)->findOneByHost($request->getHost());
        if (!$website instanceof Website) {
            return false;
        }

        [$payload, $honeypot] = $this->readChallengeFields($request);

        if ($this->captcha->verify($this->secretProvider->hmacKey($website), $payload, $honeypot)) {
            return true;
        }

        $request->getSession()->getFlashBag()->add(
            'error_form',
            $this->translator->trans('Erreur de sécurité !! Rechargez la page et réessayez.', [], 'front_form')
        );

        $logger = new Logger('SECURITY_FORM');
        $logger->pushHandler(new RotatingFileHandler($this->logDir.'/security-cms.log', 10, Level::Critical));
        $logger->critical('Captcha security. IP register :'.$request->getClientIp());

        return false;
    }

    /**
     * Read the challenge fields whether posted top-level or nested under a form name.
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function readChallengeFields(Request $request): array
    {
        $payload = $request->request->get('field_ho');
        $honeypot = $request->request->get('field_ho_entitled');

        if (null === $payload) {
            foreach ($request->request->all() as $value) {
                if (is_array($value) && !empty($value['field_ho'])) {
                    $payload = $value['field_ho'];
                    $honeypot = $value['field_ho_entitled'] ?? null;
                    break;
                }
            }
        }

        return [
            is_string($payload) ? $payload : null,
            is_string($honeypot) ? $honeypot : null,
        ];
    }
}
