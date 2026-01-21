<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Core\Website;
use App\Service\Content\CryptService;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * RecaptchaAuthenticator.
 *
 * Manage recaptcha security authenticate post
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class RecaptchaAuthenticator
{
    private Session $session;

    /**
     * RecaptchaAuthenticator constructor.
     */
    public function __construct(
        private readonly CryptService $cryptService,
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $logDir,
    ) {
        $this->session = new Session();
    }

    /**
     * Check if is valid POST.
     *
     * @throws \Exception
     */
    public function execute(Request $request): bool
    {
        $website = $this->entityManager->getRepository(Website::class)->findOneByHost($request->getHost());
        $formSecurityKey = $website->getSecurity()->getSecurityKey();
        $this->setSecurityKeys($website);
        $fieldHo = $request->request->get('field_ho');
        $fieldHoEntitled = $request->request->get('field_ho_entitled');

        if (!empty($fieldHo) && empty($fieldHoEntitled)) {
            $honeyPost = $this->cryptService->execute($website, $fieldHo, 'd');
            if (urldecode($honeyPost) == $formSecurityKey) {
                return true;
            }
        }

        $this->session->getFlashBag()->add('error_form', $this->translator->trans('Erreur de sécurité !! Rechargez la page et réessayez.', [], 'front_form'));

        $logger = new Logger('SECURITY_FORM');
        $logger->pushHandler(new RotatingFileHandler($this->logDir.'/security-cms.log', 10, Level::Critical));
        $logger->critical('Recaptcha security. IP register :'.$request->getClientIp());

        return false;
    }

    /**
     * Set security keys if not generated.
     *
     * @throws \Exception
     */
    private function setSecurityKeys(Website $website): void
    {
        $flush = false;
        $api = $website->getApi();
        $securityKey = $api->getSecuritySecretKey();
        $securityIv = $api->getSecuritySecretIv();

        if (!$securityKey) {
            $key = base64_encode(uniqid().password_hash(uniqid(), PASSWORD_BCRYPT).random_bytes(10));
            $api->setSecuritySecretKey(substr(str_shuffle($key), 0, 45));
            $flush = true;
        }

        if (!$securityIv) {
            $key = base64_encode(uniqid().password_hash(uniqid(), PASSWORD_BCRYPT).random_bytes(10));
            $api->setSecuritySecretIv(substr(str_shuffle($key), 0, 45));
            $flush = true;
        }

        if ($flush) {
            $this->entityManager->persist($api);
            $this->entityManager->flush();
        }
    }
}
