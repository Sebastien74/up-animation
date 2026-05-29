<?php

declare(strict_types=1);

namespace App\Controller\Admin\Development;

use App\Controller\Admin\AdminController;
use App\Service\Development\MailScenarioCatalog;
use App\Service\Development\MailScenarioSender;
use App\Service\Development\MailTestRunner;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Nelmio\ApiDocBundle\Attribute\Security as ApiSecurity;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/development/mail-tests', schemes: '%protocol%')]
#[OA\Tag(name: 'Mail tests')]
final class MailTestRunnerController extends AdminController
{
    // Shared with CommandTestRunnerController so the Scalar UI's single token works on both.
    private const string CSRF_TOKEN_ID = 'admin_dev_tools';

    public function __construct(
        CoreLocatorInterface $coreLocator,
        AdminLocatorInterface $adminLocator,
    ) {
        parent::__construct($coreLocator, $adminLocator);
    }

    #[Route('', name: 'admin_mail_tests', methods: 'GET')]
    public function view(): RedirectResponse
    {
        return $this->redirectToRoute('app.swagger_ui');
    }

    #[Route('/run', name: 'admin_mail_tests_run', methods: 'POST')]
    #[OA\Post(
        description: <<<'TXT'
            Lance l'intégralité des tests unitaires liés aux envois transactionnels
            sur le **transport `null`** : aucun mail n'est réellement envoyé, seul le
            rendu des templates et la construction des messages sont vérifiés.

            **Usage typique** : régression rapide avant un déploiement, après une refonte
            de template ou un changement de palette dans les bases mail.

            **Garde-fous** :
            - Bloqué en production (`403`).
            - Exige un CSRF token frais (`GET /csrf-token`).
            TXT,
        summary: 'Execute la suite PHPUnit des tests mail.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['csrf_token'],
                properties: [
                    new OA\Property(
                        property: 'csrf_token',
                        description: 'Token CSRF (alternative : header `X-CSRF-Token`).',
                        type: 'string',
                        example: '4f8a...truncated',
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sortie agrégée de la suite PHPUnit (statut global, durée, tests joués, échecs détaillés).',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'tests', description: 'Nombre de tests exécutés.', type: 'integer', example: 24),
                        new OA\Property(property: 'failures', type: 'integer', example: 0),
                        new OA\Property(property: 'duration', description: 'Durée en secondes.', type: 'number', format: 'float', example: 1.42),
                        new OA\Property(property: 'output', description: 'Sortie brute PHPUnit pour debug.', type: 'string'),
                    ],
                ),
            ),
            new OA\Response(response: 403, description: 'CSRF invalide ou environnement de production.'),
        ],
    )]
    #[ApiSecurity(name: 'Session')]
    public function run(Request $request, MailTestRunner $runner): JsonResponse
    {
        if (($denied = $this->guard($request)) !== null) {
            return $denied;
        }

        return new JsonResponse($runner->run()->toArray());
    }

    #[Route('/send', name: 'admin_mail_tests_send', methods: 'POST')]
    #[OA\Post(
        description: <<<'TXT'
            Déclenche l'envoi **réel** d'un scénario unique (newsletter, confirmation
            inscription, reset password, etc.) via le transport configuré dans
            `MAILER_DSN`. Le destinataire reçoit le mail tel qu'il serait reçu en
            production : rendu HTML, headers, pièces jointes éventuelles.

            **Quand l'utiliser** :
            - Vérifier visuellement le rendu chez un fournisseur précis (Gmail, Outlook, Apple Mail).
            - Tester la délivrabilité après ajustement DKIM/SPF.
            - Valider une modification de palette ou de copy avant déploiement.

            **Précautions** :
            - Utilisez toujours une adresse contrôlée (boîte interne ou Mailtrap).
            - Aucune donnée personnelle réelle ne doit être injectée.
            - Bloqué en production (`403`).
            TXT,
        summary: 'Envoie un seul scénario de mail vers un destinataire de test.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['scenario', 'recipient'],
                    properties: [
                        new OA\Property(
                            property: 'scenario',
                            description: 'Identifiant du scénario. Voir `GET /scenarios` pour les libellés humains et les descriptions.',
                            type: 'string',
                            enum: [
                                'newsletter-confirmation',
                                'newsletter-webmaster',
                                'contact-form',
                                'contact-confirmation',
                                'registration',
                                'reset-password',
                                'password-expire',
                                '2fa-code',
                            ],
                        ),
                        new OA\Property(
                            property: 'recipient',
                            description: 'Adresse de test. Préférer un domaine interne ou un piège SMTP.',
                            type: 'string',
                            format: 'email',
                            example: 'demo@example.test',
                        ),
                        new OA\Property(
                            property: 'csrf_token',
                            description: 'Token CSRF — auto-injecté par l\'UI Scalar, ne pas remplir manuellement.',
                            type: 'string',
                        ),
                    ],
                ),
                examples: [
                    new OA\Examples(example: 'newsletter-confirmation', summary: 'Newsletter — confirmation double opt-in', value: ['scenario' => 'newsletter-confirmation', 'recipient' => 'demo@example.test']),
                    new OA\Examples(example: 'newsletter-webmaster', summary: 'Newsletter — alerte webmaster', value: ['scenario' => 'newsletter-webmaster', 'recipient' => 'demo@example.test']),
                    new OA\Examples(example: 'contact-form', summary: 'Contact — notification webmaster', value: ['scenario' => 'contact-form', 'recipient' => 'demo@example.test']),
                    new OA\Examples(example: 'contact-confirmation', summary: 'Contact — accusé de réception', value: ['scenario' => 'contact-confirmation', 'recipient' => 'demo@example.test']),
                    new OA\Examples(example: 'registration', summary: 'Inscription — confirmation utilisateur', value: ['scenario' => 'registration', 'recipient' => 'demo@example.test']),
                    new OA\Examples(example: 'reset-password', summary: 'Réinitialisation mot de passe', value: ['scenario' => 'reset-password', 'recipient' => 'demo@example.test']),
                    new OA\Examples(example: 'password-expire', summary: 'Mot de passe expiré', value: ['scenario' => 'password-expire', 'recipient' => 'demo@example.test']),
                    new OA\Examples(example: '2fa-code', summary: '2FA — code de vérification', value: ['scenario' => '2fa-code', 'recipient' => 'demo@example.test']),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Résultat de l\'envoi (succès, ou détail de l\'erreur transport).',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'error', description: 'Message d\'erreur lisible si `success = false`.', type: 'string', nullable: true),
                    ],
                ),
            ),
            new OA\Response(response: 403, description: 'CSRF invalide, scénario inconnu, ou environnement de production.'),
        ],
    )]
    #[ApiSecurity(name: 'Session')]
    public function sendOne(Request $request, MailScenarioSender $sender): JsonResponse
    {
        if (($denied = $this->guard($request)) !== null) {
            return $denied;
        }

        $payload = $this->payload($request);
        $scenarioId = (string) ($payload['scenario'] ?? '');
        $recipient = (string) ($payload['recipient'] ?? '');

        return new JsonResponse($sender->send($scenarioId, $recipient));
    }

    #[Route('/send-all', name: 'admin_mail_tests_send_all', methods: 'POST')]
    #[OA\Post(
        description: <<<'TXT'
            Itère sur tous les scénarios du catalogue et envoie chacun au destinataire
            fourni. Pratique pour **inspecter en une fois l'ensemble du parc de mails
            transactionnels** après un changement de design system ou une migration de
            fournisseur d'envoi.

            **Réponse** : un agrégat avec le statut global et le détail de chaque scénario.
            Le statut global est `true` uniquement si **tous** les scénarios passent.

            **Garde-fous** :
            - Bloqué en production (`403`).
            - Envoi séquentiel — peut prendre plusieurs secondes selon le nombre de scénarios.
            - Une erreur sur un scénario ne stoppe pas la boucle (collect-all).
            TXT,
        summary: 'Envoie tous les scénarios de mail vers un même destinataire.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['recipient', 'csrf_token'],
                properties: [
                    new OA\Property(
                        property: 'recipient',
                        description: 'Adresse de test recevant l\'intégralité des mails (un par scénario).',
                        type: 'string',
                        format: 'email',
                        example: 'demo@example.test',
                    ),
                    new OA\Property(
                        property: 'csrf_token',
                        description: 'Token CSRF (alternative : header `X-CSRF-Token`).',
                        type: 'string',
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Statut agrégé + ligne par scénario avec son succès individuel.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', description: '`true` si tous les scénarios sont passés.', example: false),
                        new OA\Property(
                            property: 'results',
                            description: 'Détail par scénario, dans l\'ordre du catalogue.',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'scenario', type: 'string', example: 'newsletter-confirmation'),
                                    new OA\Property(property: 'success', type: 'boolean', example: true),
                                    new OA\Property(property: 'error', type: 'string', nullable: true),
                                ],
                            ),
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 403, description: 'CSRF invalide ou environnement de production.'),
        ],
    )]
    #[ApiSecurity(name: 'Session')]
    public function sendAll(Request $request, MailScenarioSender $sender): JsonResponse
    {
        if (($denied = $this->guard($request)) !== null) {
            return $denied;
        }

        $payload = $this->payload($request);
        $recipient = (string) ($payload['recipient'] ?? '');

        $results = $sender->sendAll($recipient);
        $hasFailure = false;
        foreach ($results as $row) {
            if (!$row['success']) {
                $hasFailure = true;
                break;
            }
        }

        return new JsonResponse([
            'success' => !$hasFailure,
            'results' => $results,
        ]);
    }

    #[Route('/scenarios', name: 'admin_mail_tests_scenarios', methods: 'GET')]
    #[OA\Get(
        description: <<<'TXT'
            Retourne la liste complète des identifiants de scénarios reconnus par
            les endpoints `POST /send` et `POST /send-all`.

            **Cas d'usage** :
            - Construire dynamiquement un select dans une interface admin.
            - Vérifier qu'un nouveau scénario est bien découvert après ajout dans
              `MailScenarioCatalog`.

            Endpoint en lecture seule, ne nécessite pas de CSRF token.
            TXT,
        summary: 'Catalogue des scénarios de mail disponibles.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des scénarios avec libellé humain et description courte.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', description: 'Identifiant technique à passer dans `POST /send`.', type: 'string', example: 'newsletter-confirmation'),
                            new OA\Property(property: 'label', description: 'Libellé affichable.', type: 'string', example: 'Confirmation newsletter'),
                            new OA\Property(property: 'description', description: 'Phrase de contexte décrivant le déclencheur métier.', type: 'string'),
                        ],
                    ),
                ),
            ),
        ],
    )]
    public function scenarios(MailScenarioCatalog $catalog): JsonResponse
    {
        return new JsonResponse($catalog->all());
    }

    #[Route('/csrf-token', name: 'admin_mail_tests_csrf', methods: 'GET')]
    #[OA\Get(
        description: <<<'TXT'
            Renvoie un token CSRF rattaché à la session courante. À appeler **avant**
            chaque série d'appels mutants (`POST /run`, `POST /send`, `POST /send-all`).

            Le token peut être transmis :
            - dans le header `X-CSRF-Token` (recommandé pour les clients automatisés) ;
            - ou dans le body JSON sous la clé `csrf_token`.

            Le token reste valide tant que la session admin n'est pas invalidée.
            Endpoint en lecture seule, accessible avec la session admin standard.
            TXT,
        summary: 'Obtient un CSRF token pour les appels POST.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token CSRF lié à la session courante.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'csrf_token',
                            description: 'Chaîne opaque à inclure dans les requêtes POST suivantes.',
                            type: 'string',
                            example: '4f8a3b9c2d1e7f6a5b4c3d2e1f0a9b8c',
                        ),
                    ],
                ),
            ),
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
            $payload = $this->payload($request);
            $token = (string) ($payload['csrf_token'] ?? '');
        }
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        if ($this->coreLocator->isProd()) {
            return new JsonResponse(['error' => 'Mail dev tools disabled in production.'], Response::HTTP_FORBIDDEN);
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
