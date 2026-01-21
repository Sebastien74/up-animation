<?php

declare(strict_types=1);

namespace App\Controller\Security\Admin;

use App\Controller\Admin\AdminController;
use App\Repository\Security\UserRepository;
use App\Service\Core\KeyGeneratorService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * UtilityController.
 *
 * Security utilities management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UtilityController extends AdminController
{
    /**
     * User ApiModel.
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin-%security_token%/security/utility', name: 'admin_security_utility', methods: 'GET|POST', schemes: '%protocol%')]
    public function getUsersApi(UserRepository $userRepository, Request $request): JsonResponse
    {
        return $this->json(
            $userRepository->findAllMatching($request->query->get('query')),
            200, [],
            ['groups' => ['main']]
        );
    }

    /**
     * Password generator.
     */
    #[Route('/admin-%security_token%/security/utility/password-generator', name: 'security_password_generator', options: ['expose' => true], methods: 'GET', schemes: '%protocol%')]
    public function passwordGenerator(KeyGeneratorService $keyGeneratorService): JsonResponse
    {
        return new JsonResponse(['password' => $keyGeneratorService->generate(4, 4, 4, 2)]);
    }
}
