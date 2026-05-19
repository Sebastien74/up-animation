<?php

declare(strict_types=1);

namespace App\Controller\Front\Module;

use App\Entity\Core\Website;
use App\Model\Core\WebsiteModel;
use App\Service\Content\CryptService;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * CryptController.
 *
 * Manage string encryption used by the form honeypot mechanism.
 *
 * Note: a public decryption endpoint used to exist here. It has been removed
 * because it acted as a generic decryption oracle for any payload encrypted
 * with the website key.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Route('/cms/front/crypt', schemes: '%protocol%')]
class CryptController extends AbstractController
{
    /**
     * Encrypt a short opaque payload (used only for the form honeypot).
     *
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException|\ReflectionException
     */
    #[Route('/encrypt/{website}/{string}', name: 'front_encrypt', options: ['isMainRequest' => false], defaults: ['website' => null, 'string' => null], methods: 'GET')]
    public function encrypt(CoreLocatorInterface $coreLocator, CryptService $cryptService, ?Website $website = null, ?string $string = null): JsonResponse
    {
        if (!$website instanceof Website || null === $string) {
            throw new NotFoundHttpException();
        }

        if (strlen($string) > 256) {
            throw new NotFoundHttpException();
        }

        return new JsonResponse([
            'result' => $cryptService->execute(WebsiteModel::fromEntity($website, $coreLocator), $string, 'e'),
        ]);
    }

    /**
     * Legacy decrypt route - intentionally disabled.
     *
     * Kept to preserve `path('front_decrypt')` calls in templates, but always
     * answers 404 so it cannot be used as a decryption oracle.
     */
    #[Route('/decrypt/{website}/{string}',
        name: 'front_decrypt',
        options: ['isMainRequest' => false],
        defaults: ['website' => null, 'string' => null],
        methods: 'GET')]
    public function decrypt(): JsonResponse
    {
        throw new NotFoundHttpException();
    }
}
