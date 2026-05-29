<?php

declare(strict_types=1);

namespace App\Controller\Admin\Development;

use App\Controller\Admin\AdminController;
use App\Service\Development\PhpunitSuiteRunner;
use App\Service\Development\ScheduledCommandCatalog;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Nelmio\ApiDocBundle\Attribute\Security as ApiSecurity;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/development/command-tests', schemes: '%protocol%')]
#[OA\Tag(name: 'Tests commandes')]
final class CommandTestRunnerController extends AdminController
{
    private const string TESTSUITE = 'command';
    // Shared with MailTestRunnerController so the Scalar UI's single token works on both.
    private const string CSRF_TOKEN_ID = 'admin_dev_tools';

    public function __construct(
        CoreLocatorInterface $coreLocator,
        AdminLocatorInterface $adminLocator,
    ) {
        parent::__construct($coreLocator, $adminLocator);
    }

    #[Route('/run', name: 'admin_command_tests_run', methods: 'POST')]
    #[OA\Post(
        description: <<<'TXT'
            Lance la testsuite PHPUnit `command` : tests structurels de **toutes** les
            commandes de `src/Command` (enregistrement, définition valide), garde-fou de
            régression sur l'injection d'arguments du scheduler, et tests comportementaux
            ciblés (reset des tokens). **Aucune commande n'est réellement exécutée** : pas
            de risque pour les commandes destructives (`gdpr:remove`, `analytics:purge`,
            rotation de tokens).

            **Garde-fous** :
            - Bloqué en production (`403`).
            - Exige un CSRF token frais (`GET /csrf-token`).
            TXT,
        summary: 'Exécute la suite PHPUnit des commandes console.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Résultat agrégé de la suite (statut, durée, tests joués, échecs détaillés).',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'testsuite', type: 'string', example: 'command'),
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'duration', type: 'number', format: 'float', example: 70.2),
                        new OA\Property(property: 'totals', type: 'object'),
                        new OA\Property(property: 'cases', type: 'array', items: new OA\Items(type: 'object')),
                    ],
                ),
            ),
            new OA\Response(response: 403, description: 'CSRF invalide ou environnement de production.'),
        ],
    )]
    #[ApiSecurity(name: 'Session')]
    public function run(Request $request, PhpunitSuiteRunner $runner): JsonResponse
    {
        if (($denied = $this->guard($request)) !== null) {
            return $denied;
        }

        return new JsonResponse($runner->run(self::TESTSUITE)->toArray());
    }

    #[Route('/commands', name: 'admin_command_tests_commands', methods: 'GET')]
    #[OA\Get(
        description: <<<'TXT'
            Liste les commandes planifiées du catalogue (`ScheduledCommandCatalog`) avec
            leur expression cron et leur statut actif par défaut. Lecture seule, sans CSRF.
            TXT,
        summary: 'Catalogue des commandes planifiées.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des commandes avec libellé, expression cron et drapeau actif.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'command', type: 'string', example: 'security:reset:token'),
                            new OA\Property(property: 'name', type: 'string', example: 'Suppression des tokens utilisateurs'),
                            new OA\Property(property: 'cronExpression', type: 'string', example: '0 3 * * *'),
                            new OA\Property(property: 'active', type: 'boolean', example: true),
                        ],
                    ),
                ),
            ),
        ],
    )]
    public function commands(ScheduledCommandCatalog $catalog): JsonResponse
    {
        $rows = [];
        foreach ($catalog->all() as $definition) {
            $rows[] = [
                'command' => $definition->command,
                'name' => $definition->name,
                'cronExpression' => $definition->cronExpression,
                'active' => $definition->active,
            ];
        }

        return new JsonResponse($rows);
    }

    #[Route('/csrf-token', name: 'admin_command_tests_csrf', methods: 'GET')]
    #[OA\Get(
        description: 'Renvoie un token CSRF rattaché à la session courante pour les appels POST.',
        summary: 'Obtient un CSRF token pour les appels POST.',
        responses: [
            new OA\Response(response: 200, description: 'Token CSRF lié à la session courante.'),
        ],
    )]
    public function csrfToken(CsrfTokenManagerInterface $csrfTokenManager): JsonResponse
    {
        return new JsonResponse([
            'csrf_token' => $csrfTokenManager->getToken(self::CSRF_TOKEN_ID)->getValue(),
        ]);
    }

    private function guard(Request $request): ?JsonResponse
    {
        $token = (string) $request->headers->get('X-CSRF-Token', $request->request->get('_csrf_token', ''));
        if ('' === $token) {
            $token = (string) ($this->payload($request)['csrf_token'] ?? '');
        }
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        if ($this->coreLocator->isProd()) {
            return new JsonResponse(['error' => 'Command dev tools disabled in production.'], Response::HTTP_FORBIDDEN);
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request): array
    {
        $content = $request->getContent();
        if ('' !== $content && str_starts_with(trim($content), '{')) {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return $request->request->all();
    }
}
