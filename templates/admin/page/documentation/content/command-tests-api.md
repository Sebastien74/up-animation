# Tests des commandes (API)

Outil interne pour lancer la suite de tests des commandes console depuis le
back-office, via la documentation interactive Swagger/Scalar. Réservé à l'équipe
(`ROLE_INTERNAL`) et **désactivé en production**.

## Accès

- Vue Scalar : `/admin-{token}/development/mail-tests/doc` (même page que les tests
  mails ; les endpoints des commandes sont regroupés sous le tag **« Tests commandes »**).
- `{token}` correspond à la variable d'environnement `SECURITY_TOKEN`.

## Authentification

- Firewall admin + rôle `ROLE_INTERNAL`.
- Les appels mutants (`POST /run`) exigent un token CSRF. L'UI Scalar l'injecte
  automatiquement (header `X-CSRF-Token`) ; le token est partagé avec les tests mails.

## Endpoints

| Méthode | Chemin | Rôle |
| --- | --- | --- |
| `GET` | `/development/command-tests/csrf-token` | Renvoie un token CSRF de session. |
| `GET` | `/development/command-tests/commands` | Liste les commandes planifiées du catalogue (libellé, expression cron, statut actif). Lecture seule. |
| `POST` | `/development/command-tests/run` | Exécute la testsuite PHPUnit `command` et renvoie le résultat agrégé. |

## Contenu de la testsuite `command`

La suite (`tests/Command`) **n'exécute aucune commande réelle** ; elle vérifie :

- **`CommandRegistryTest`** : toutes les commandes de `src/Command` sont enregistrées,
  nommées et exposent une définition valide.
- **`SchedulerArgumentsTest`** : garde-fou de régression. Le moteur du scheduler
  n'injecte `cronLogger`/`commandLogger` que si la commande les déclare ; ce test
  reconstruit cette entrée pour chaque commande du catalogue et vérifie qu'elle est
  acceptée (et qu'une commande sans ces arguments les rejette bien).
- **`SecurityTokenCommandTest`** : la commande `security:reset:token` annule un token
  dont la date dépasse 24 h, conserve un token récent (dépendances mockées).

## Garde-fous

- **Aucune exécution réelle des commandes destructives** depuis l'UI
  (`gdpr:remove`, `app:analytics:purge`, `contacts:remove`, rotation de tokens
  Instagram/TikTok). Le runner se limite à la suite PHPUnit.
- Bundle Nelmio (et donc cette doc) chargé uniquement hors production.
- Exécution dans un processus enfant (`bin/phpunit`), jamais via un shell.
- Timeout de 120 s.

## Configuration

- Testsuite : `phpunit.xml.dist` (`<testsuite name="command">` → `tests/Command`).
- Runner : `src/Service/Development/PhpunitSuiteRunner.php` (+ `PhpunitRunResult`).
- Controller : `src/Controller/Admin/Development/CommandTestRunnerController.php`.
- Périmètre Scalar : `config/packages/nelmio_api_doc.yaml` (`areas.default.path_patterns`).

## Dépannage

| Symptôme | Cause probable | Action |
| --- | --- | --- |
| `403` sur `/run` | CSRF manquant/expiré, ou environnement de production | Recharger la page Scalar (token réinjecté) ; vérifier l'environnement. |
| Binaire PHP introuvable | `PHP_BINARY` pointe sur `httpd.exe` (WAMP) | Définir `PHP_CLI_PATH` dans `.env.local` vers `php.exe`. |
| `totals.total = 0` | Testsuite vide ou JUnit non généré | Vérifier que `tests/Command` contient des tests et que `var/log` est inscriptible. |
