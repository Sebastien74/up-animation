# Détection des dépréciations

Dispositif de suivi des dépréciations PHP (Symfony et librairies tierces), destiné à
préparer une montée de version majeure (ex. Symfony 8.x). Deux approches complémentaires :
le **scan statique complet** (PHPStan) et l'**observation à l'exécution** (journal).

## 1. Scan complet (PHPStan)

Analyse statique de **tout le code PHP du projet**, sans l'exécuter : couverture
exhaustive, en une action. C'est le scan de référence pour préparer une montée de version.

- Page : **Rapport de dépréciations** (`admin_deprecation_report`,
  `/admin-{token}/development/deprecations`), tuile « Dépléciations » du dashboard
  (`ROLE_INTERNAL`).
- Bouton **« Lancer un scan complet »** : le scan tourne par **lots pilotés par le
  navigateur** (barre de progression réelle = fichiers traités / total). Pas de
  processus détaché : compatible Windows/WAMP comme mutualisé.
- Racines analysées : `src`, `migrations`, `config`, `public`. Chaque résultat est
  **étiqueté par zone** (src / migration / config / public) et par **paquet d'origine**
  (motif `Since <paquet> <version>:`), avec l'emplacement `fichier:ligne`.
- **Limite** : PHPStan analyse du **PHP**. Les **templates Twig** et le code **vendor**
  ne sont pas analysables statiquement → voir le journal d'exécution (section 2).

### Outils

- Paquets (require-dev) : `phpstan/phpstan`, `phpstan/phpstan-deprecation-rules`
  (auto-enregistré par `phpstan/extension-installer`).
- Config : `phpstan.dist.neon` (level 0, règles de dépréciation, `tmpDir: var/cache/phpstan`).
- En CLI : `php vendor/phpstan/phpstan/phpstan analyse`.
- Service : `src/Service/Development/DeprecationScanService.php` (lots, Process,
  filtrage des messages de dépréciation). Le binaire PHP CLI est résolu par
  `PhpCliBinaryResolver` (override `PHP_CLI_PATH` en `.env.local` si besoin sous WAMP).
- Endpoint : `admin_deprecation_scan` (`POST .../deprecations/scan`, CSRF, coupé en prod).

## 1bis. Crawl des pages (runtime, à la demande)

Second bouton de la page : visite **chaque URL** (URLs entités front en ligne +
routes admin GET satisfaisables) via des **sous-requêtes in-process**
(`HttpKernelInterface::SUB_REQUEST`), URL par URL, avec l'URL courante affichée sous
la barre de progression. Chaque page rendue déclenche ses dépréciations **runtime**
(Twig, vendor, chemins dynamiques) que l'analyse statique ne voit pas.

- Service : `src/Service/Development/DeprecationCrawlService.php`. Après chaque URL, lit
  le **delta du journal** pour attribuer les dépréciations à la page (`location`).
- Endpoint : `admin_deprecation_crawl` (`POST .../deprecations/crawl`, CSRF, coupé en prod).
- Effet de bord neutralisé : header `X-Internal-Crawler` → `ExceptionListener` n'enregistre
  pas de `NotFoundUrl` pendant un crawl.
- Trace persistée dans `var/cache/deprecation-crawl.json` (rechargée à l'affichage).

**Performance / prod** : tout le module (scan + crawl) est **à la demande**, jamais
branché sur le cycle de requête normal, et les endpoints sont **coupés en production**.
La liste différencie chaque dépréciation par **Source** : `scan` / `crawl` / `journal`.

## 2. Journal runtime (navigation + tests)

Les dépréciations déclenchées pendant l'exécution sont routées par le gestionnaire
d'erreurs Symfony vers le **canal Monolog `deprecation`**, puis écrites dans un fichier
dédié. Couvre ce que le scan statique ne voit pas (Twig, vendor, chemins dynamiques),
mais seulement pour le code réellement exécuté.

- Fichier : `var/log/<env>.deprecations.log` (ex. `local.deprecations.log`).
- Config : `config/packages/monolog.yaml`, handler `deprecation`
  (`channels: ["deprecation"]`) en `when@dev` et `when@local` uniquement. Le canal est
  exclu du log `main` (dev + prod) ; **aucun** journal de dépréciation en production.
- Activation : `framework.php_errors.log: true`.
- Affichage : section « Observé à l'exécution » de la page (agrégée par message + paquet).
- Service : `src/Service/Development/DeprecationLogReportService.php` (lecture par la
  fin, bornée).

## 3. Détection en tests (CI)

Le bridge `symfony/phpunit-bridge` compte les dépréciations rencontrées pendant la
suite de tests via `SYMFONY_DEPRECATIONS_HELPER` (dans `phpunit.xml.dist`).

- Mode actuel : `weak` (rapporte sans faire échouer le build).
- Étape suivante : geler l'existant dans une baseline puis n'échouer que sur les
  nouvelles dépréciations :
  1. `SYMFONY_DEPRECATIONS_HELPER="generateBaseline=true&baselineFile=tests/deprecations.baseline" vendor/bin/phpunit`
  2. Basculer `phpunit.xml.dist` sur `baselineFile=tests/deprecations.baseline`.

## 4. Corriger une dépréciation vendor (composer-patches)

Quand la dépréciation vient d'un **paquet tiers** (code `vendor/`, non éditable : tout
serait écrasé au prochain `composer install`), on la corrige avec
**`cweagans/composer-patches`** (`require-dev`). Un diff versionné dans `patches/` est
ré-appliqué automatiquement à chaque (ré)installation du paquet ciblé.

- Déclaration dans `composer.json` → `extra.patches`, indexée par nom de paquet :
  ```json
  "extra": {
      "patches": {
          "<vendor>/<paquet>": {
              "Description courte du correctif": "patches/<paquet>-<sujet>.patch"
          }
      }
  }
  ```
- Le diff (`patches/*.patch`) est un **diff unifié** dont les chemins sont relatifs à la
  **racine du paquet** (préfixes `a/` `b/`, niveau `-p1`).
- Le patch ne s'applique qu'à la **(ré)installation du paquet**, pas sur un `composer
  install` quand le paquet est déjà présent. Pour forcer : supprimer le dossier du paquet
  dans `vendor/<vendor>/<paquet>/` puis `php composer.phar install`. (`composer reinstall`
  échoue sous WAMP : bug Windows « Uninstall failed / Package is not installed ».)
- **Gotcha Windows** : composer-patches n'utilise `git apply` que si le paquet est lui-même
  un dépôt git (jamais le cas en vendor) et retombe sur la commande système `patch`,
  absente de WAMP. La fournir via Git for Windows :
  `$env:Path = "C:\Program Files\Git\usr\bin;$env:Path"` avant `php composer.phar install`.
  Sur Linux / OVH, `patch` est natif : rien à faire au déploiement.

Patch en place : **`eckinox/tinymce-bundle`** (`TinymceExtension` étendait l'`Extension`
de `HttpKernel`, interne depuis 7.1) → import basculé vers
`Symfony\Component\DependencyInjection\Extension\Extension`. À retirer dès qu'une version
du bundle compatible Symfony 8.x est publiée.

## Où la détection tourne

Affaire de **développement et de tests**, jamais de production : journal en `dev`/`local`,
bridge en `test`, scan PHPStan à la demande (require-dev, coupé en prod).
