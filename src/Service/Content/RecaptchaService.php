<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Entity\Core\Website;
use App\Model\Core\WebsiteModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * RecaptchaService.
 *
 * Manage recaptcha security post
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class RecaptchaService
{
    private ?Request $request;
    private Session $session;

    /**
     * RecaptchaService constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly CryptService $cryptService,
        private readonly TranslatorInterface $translator,
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $logDir,
    ) {
        $this->request = $this->requestStack->getCurrentRequest();
        $this->session = new Session();
    }

    /**
     * Check if is valid POST.
     *
     * @throws Exception|InvalidArgumentException
     */
    public function execute(Website $website, mixed $entity, FormInterface $form, ?string $email = null): bool
    {
        $post = filter_input_array(INPUT_POST)[$form->getName()];
        $formSecurityKey = method_exists($entity, 'getSecurityKey') ? $entity->getSecurityKey() : $website->getSecurity()->getSecurityKey();
        $this->securityKeys($website);

        if (method_exists($entity, 'isRecaptcha') && !$entity->isRecaptcha()) {
            return true;
        }

        if (!empty($post['field_ho']) && empty($post['field_ho_entitled'])) {
            $honeyPost = $this->cryptService->execute(WebsiteModel::fromEntity($website, $this->coreLocator), $post['field_ho'], 'd');
            if ($honeyPost && urldecode($honeyPost) == $formSecurityKey) {
                return true;
            }
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request?->hasSession()) {
            $request->getSession()->getFlashBag()->add(
                'error_form',
                $this->translator->trans('Erreur de sécurité !! Rechargez la page et réessayez.', [], 'front_form')
            );
        }

        $logger = new Logger('SPAM');
        $logger->pushHandler(new RotatingFileHandler($this->logDir.'/spams.log', 10, Level::Info));

        if ($email) {
            $logger->alert('Recaptcha security. This email seems to be spam :'.$email);
        } else {
            $logger->alert('Recaptcha security. IP spam :'.$this->request->getClientIp());
        }

        return false;
    }

    /**
     * Set security keys if not generated.
     *
     * @throws Exception
     */
    private function securityKeys(Website $website): void
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
