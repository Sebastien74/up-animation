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
use Symfony\Component\Routing\Attribute\Route;

/**
 * CryptController.
 *
 * Manage string encryption
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Route('/cms/front/crypt', schemes: '%protocol%')]
class CryptController extends AbstractController
{
    /**
     * Encrypt.
     *
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException|\ReflectionException
     */
    #[Route('/encrypt/{website}/{string}', name: 'front_encrypt', options: ['isMainRequest' => false], defaults: ['website' => null, 'string' => null], methods: 'GET')]
    public function encrypt(CoreLocatorInterface $coreLocator, CryptService $cryptService, ?Website $website = null, ?string $string = null): JsonResponse
    {
        $response = new JsonResponse(['result' => $cryptService->execute(WebsiteModel::fromEntity($website, $coreLocator), $string, 'e')]);
        header('Cache-Control: max-age=31536000');

        return $response;
    }

    /**
     * Decrypt.
     *
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException|\ReflectionException
     */
    #[Route('/decrypt/{website}/{string}',
        name: 'front_decrypt',
        options: ['isMainRequest' => false],
        defaults: ['website' => null, 'string' => null],
        methods: 'GET')]
    public function decrypt(CoreLocatorInterface $coreLocator, CryptService $codeService, ?Website $website = null, ?string $string = null): JsonResponse
    {
        $response = new JsonResponse(['result' => $codeService->execute(WebsiteModel::fromEntity($website, $coreLocator), $string, 'd')]);
        header('Cache-Control: max-age=31536000');

        return $response;
    }
}
