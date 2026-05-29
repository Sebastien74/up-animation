# API Mail Tests (Nelmio Swagger UI)

Outil interne du back-office permettant de :

- lancer la suite PHPUnit des tests d'envoi de mail (transport null, aucun mail réel)
- déclencher l'envoi réel d'un ou plusieurs mails de démonstration vers le `MAILER_DSN` configuré (utile en local avec mailpit)

L'interface est générée automatiquement par `nelmio/api-doc-bundle` à partir des attributs OpenAPI sur le contrôleur.

---

## URLs

Le segment `{token}` correspond à la valeur de la variable d'environnement `SECURITY_TOKEN`.

| Type | URL |
|---|---|
| Point d'entrée (redirige vers Swagger) | `https://<host>/admin-{token}/development/mail-tests` |
| Swagger UI | `https://<host>/admin-{token}/development/mail-tests/doc` |
| OpenAPI JSON brut | `https://<host>/admin-{token}/development/mail-tests/doc.json` |

Accès raccourci : Back-office -> Boîte à outils -> carte "Tests" -> bouton "Suite de tests mail".

---

## Authentification

- Firewall admin (`^/admin-{token}`) : session admin requise
- Rôle minimum : `ROLE_INTERNAL`
- Bundle Nelmio désactivé en `prod` (`config/bundles.php`)
- Endpoints renvoient 403 si `coreLocator->isProd()`
- Chaque endpoint POST nécessite un **CSRF token** valide (id `admin_mail_tests_run`), passé au choix :
    - Header `X-CSRF-Token: <token>`
    - Champ JSON `csrf_token` dans le body

Le token se récupère via `GET /csrf-token` (voir endpoints ci-dessous). Il reste valide pour toute la session admin courante.

---

## Endpoints

Tous les endpoints sont préfixés par `/admin-{token}/development/mail-tests`.

### `GET /csrf-token`

Renvoie le CSRF token courant. À appeler une fois en début de session pour récupérer la valeur à coller dans les POST suivants.

Réponse :

```json
{ "csrf_token": "abc123..." }
```

### `GET /scenarios`

Liste le catalogue des scénarios de mail envoyables.

Réponse :

```json
[
  {
    "id": "newsletter-confirmation",
    "label": "Newsletter - confirmation double opt-in",
    "description": "Mail envoye au visiteur apres inscription a la newsletter..."
  },
  ...
]
```

### `POST /run`

Lance la suite PHPUnit `mail` via `bin/phpunit --testsuite=mail`. **N'envoie aucun mail réel** (transport `null://null` forcé dans `phpunit.xml.dist`). Sert à vérifier que le code, les templates Twig et les en-têtes sont valides.

Body :

```json
{ "csrf_token": "..." }
```

Réponse (extrait) :

```json
{
  "success": true,
  "exitCode": 0,
  "duration": 4.12,
  "totals": { "total": 21, "passed": 21, "failed": 0, "errored": 0, "skipped": 0 },
  "cases": [ { "class": "...", "name": "...", "status": "passed", "time": 0.05, "message": null } ],
  "stdout": "...",
  "stderr": ""
}
```

Timeout dur côté serveur : 120 secondes.

### `POST /send`

Envoie **un seul** scénario via le `MailerService` réel + `MAILER_DSN` du `.env.local` (donc visible dans mailpit en local).

Body :

```json
{
  "scenario": "newsletter-confirmation",
  "recipient": "demo@example.test",
  "csrf_token": "..."
}
```

Réponse :

```json
{ "success": true, "error": null }
```

ou en cas d'erreur :

```json
{ "success": false, "error": "Invalid recipient email." }
```

### `POST /send-all`

Envoie **tous** les scénarios du catalogue à la même adresse.

Body :

```json
{ "recipient": "demo@example.test", "csrf_token": "..." }
```

Réponse :

```json
{
  "success": true,
  "results": [
    { "id": "newsletter-confirmation", "label": "...", "success": true, "error": null },
    { "id": "newsletter-webmaster",    "label": "...", "success": true, "error": null },
    ...
  ]
}
```

`success` global est `true` uniquement si chaque scénario passe.

---

## Catalogue des scénarios

| id | Template Twig | Description |
|---|---|---|
| `newsletter-confirmation` | `front/default/actions/newsletter/email/confirmation.html.twig` | Confirmation double opt-in newsletter |
| `newsletter-webmaster` | `front/default/actions/newsletter/email/webmaster.html.twig` | Alerte interne nouvel inscrit |
| `contact-confirmation` | `front/default/actions/form/email/contact-confirmation.html.twig` | Accusé de réception formulaire contact |
| `registration` | `front/default/actions/security/email/confirmation-registration.html.twig` | Lien d'activation après création de compte |
| `reset-password` | `front/default/actions/security/email/password-request.html.twig` | Lien de reset mot de passe (front + back) |
| `password-expire` | `front/default/actions/security/email/password-expire.html.twig` | Alerte cron mot de passe expiré ou expirant |
| `2fa-code` | `front/default/actions/security/email/2fa-code.html.twig` | Code à usage unique pour 2FA email |

Catalogue défini dans `src/Service/Development/MailScenarioCatalog.php`. Les arguments Twig de démonstration sont construits dans `src/Service/Development/MailScenarioSender::buildConfig()`.

---

## Workflow type dans Swagger UI

1. Ouvrir `https://<host>/admin-{token}/development/mail-tests/doc`
2. Déplier `GET /csrf-token` -> **Try it out** -> **Execute** -> copier la valeur de `csrf_token`
3. (optionnel) `GET /scenarios` pour voir les ids disponibles
4. `POST /send` -> **Try it out** -> coller le token et un destinataire -> **Execute**
5. Vérifier l'arrivée du mail dans mailpit (`http://localhost:8025` par défaut)
6. Pour tout envoyer d'un coup : `POST /send-all`
7. Pour valider que le code mail compile sans rien envoyer : `POST /run`

---

## Sécurité

- Bundle Nelmio chargé uniquement sur `dev`, `local`, `preprod`, `test` (jamais `prod`)
- Tous les endpoints sont sous le firewall admin + `ROLE_INTERNAL`
- CSRF token obligatoire sur tout POST
- Aucun paramètre utilisateur n'est concaténé dans une commande shell : le runner PHPUnit reçoit un array figé (`PHP_BINARY`, `bin/phpunit`, `--testsuite=mail`, ...)
- Scénarios figés en code : pas de filtre `--filter` ouvert, pas de template arbitraire
- Validation `FILTER_VALIDATE_EMAIL` du destinataire avant tout envoi
- Timeout dur de 120 s sur le subprocess PHPUnit

---

## Configuration

| Fichier | Rôle |
|---|---|
| `config/bundles.php` | Charge `NelmioApiDocBundle` sur dev/local/preprod/test |
| `config/packages/nelmio_api_doc.yaml` | Filtre l'aire de doc sur `^/admin-[^/]+/development/mail-tests` |
| `config/routes/nelmio_api_doc.yaml` | Monte Swagger UI sous le préfixe admin (firewall) |
| `phpunit.xml.dist` | Testsuite `mail` couvrant `tests/Form/Manager`, `tests/Service/Core`, `tests/Security`, `tests/Message` |
| `src/Service/Development/MailTestRunner.php` | Exécute PHPUnit via `Symfony\Component\Process` et parse le JUnit XML |
| `src/Service/Development/MailScenarioCatalog.php` | Catalogue des scénarios |
| `src/Service/Development/MailScenarioSender.php` | Configure et appelle `MailerService` selon le scénario |
| `src/Controller/Admin/Development/MailTestRunnerController.php` | 5 endpoints annotés OpenAPI |

---

## Dépannage

| Symptôme | Cause probable | Action |
|---|---|---|
| 404 sur `/mail-tests/doc` | Cache pas vidé après install Nelmio | `php bin/console cache:clear --env=local` |
| 403 sur tous les endpoints | Pas connecté en admin ou rôle insuffisant | Vérifier `ROLE_INTERNAL` sur le user courant |
| 403 "Invalid CSRF token" | Token expiré ou non transmis | Refaire `GET /csrf-token` et coller la nouvelle valeur |
| 403 "Mail dev tools disabled in production" | `APP_ENV=prod` | Normal, l'outil est interdit en prod |
| Mailpit ne reçoit rien après `POST /send` | `MAILER_DSN` ne pointe pas vers mailpit dans `.env.local` | Vérifier `MAILER_DSN=smtp://localhost:1025` (port par défaut mailpit) |
| `POST /run` renvoie `success: false` mais 21/21 passés | Exit code PHPUnit non zero à cause de deprecations | C'est désormais ignoré : `success` se base sur le total de failed/errored, pas sur l'exit code |
| Erreur "Bundle Nelmio not found" en `prod` | `bundles.php` configuré pour exclure prod | Comportement attendu, ne pas activer en prod |

---

## Voir aussi

- Tests unitaires : `tests/` (suite `mail` dans `phpunit.xml.dist`)
- Service mail central : `src/Service/Core/MailerService.php`
- Cartographie complète des envois de mail dans le projet : voir le commit qui a introduit les tests, ou interroger le code avec `grep "->mailer->send"` dans `src/`.
