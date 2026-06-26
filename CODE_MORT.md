# Code mort - inventaire

> Analyse heuristique du 2026-06-19 (script de détection sur `src/`, `templates/`,
> `config/`, hors `vendor`, `var`, `node_modules`, `public/build`).
> La détection de code mort en Symfony est piégeuse (autowiring par interface,
> conventions de nommage, rendu dynamique de templates). Chaque catégorie est donc
> assortie d'un **niveau de confiance**. A confirmer manuellement avant suppression.

## Méthode

- Une classe est candidate « morte » si **ni son nom court, ni les interfaces qu'elle
  implémente, ni sa classe parente** n'apparaissent dans aucun autre fichier
  (`.php`, `.twig`, `.yaml`, `.xml`).
- Les catégories câblées par convention (contrôleurs via route, commandes via
  `#[AsCommand]`, subscribers/listeners, message handlers, migrations, voters,
  fixtures, thèmes de formulaire, tests) sont **exclues** : l'absence de référence par
  nom de classe y est normale et ne signifie pas mort.

---

## 1. Classes PHP mortes - confiance HAUTE

Aucune référence (classe + interface + parent) dans tout le code, la config et les templates.

| Fichier | Note |
|---|---|
| `src/Service/Media/Compressor.php` | Aucune interface, aucun injecteur |
| `src/Service/Core/DoctrineService.php` | Aucun injecteur |
| `src/Service/Development/CopyFileService.php` | Aucun injecteur |
| `src/Service/Development/SecurityService.php` | Aucun injecteur |
| `src/Security/RecaptchaAuthenticator.php` | Reliquat reCAPTCHA ; le projet utilise le captcha proof-of-work maison (`CaptchaService`). Vérifier `security.yaml` (absent des `custom_authenticators`) |
| `src/Naming/OrUniqueNamer.php` | `NamerInterface` (VichUploader) non référencé dans la config vich |
| `src/Naming/WebsiteDirectoryNamer.php` | `DirectoryNamerInterface` non référencé dans la config vich |
| `src/Form/Model/Security/Front/ProfileRegistrationModel.php` | DTO de formulaire jamais instancié/typé |

---

## 2. Templates Twig - confiance MOYENNE

Jamais référencés par chemin complet ni par nom de fichier. **A vérifier** : certains
peuvent être chargés dynamiquement (chemin construit) ou par un fixture/loader.

### 2a. Probablement morts (aucun mécanisme dynamique trouvé)

- `admin/page/development/imports-data.html.twig`
- `admin/page/translation/front-translation.html.twig`
- `core/email/track-email.html.twig`
- `core/preload-javascript.html.twig`
- `core/form/bootstrap_5_horizontal_layout.html.twig` *(absent des `form_themes` de `twig.yaml`, seul `bootstrap_5_layout` y est)*
- `core/form/form_table_layout.html.twig` *(idem, non déclaré comme thème)*
- `front/default/actions/catalog/include/add-cart-btn.html.twig`
- `front/default/actions/vendor/include/layout-author.html.twig` *(les `layout-*` sont inclus dynamiquement par nom explicite ; celui-ci n'est dans aucun `include`)*
- `front/default/actions/vendor/include/layout-markdown.html.twig` *(idem)*
- `front/default/actions/security/back/email-default.html.twig`
- `front/default/actions/security/back/email-profile-request.html.twig`
- `front/default/actions/security/back/email-remove-request.html.twig`
- `front/default/actions/security/front/api-messages.html.twig`
- `front/default/actions/security/front/login-page.html.twig`
- `front/default/actions/security/front/register-page.html.twig`
- `bundles/TwigBundle/Exception/_error-500.html.twig` *(le pattern dynamique de `ExceptionController` produit `error-500.html.twig` sans underscore ; cette variante est un reliquat)*

### 2b. A vérifier - probable chargement dynamique multilingue (NE PAS supprimer sans contrôle)

Contenus par locale, probablement injectés par fixture/contenu de page :

- `front/default/template/include/cookies-de.html.twig`
- `front/default/template/include/cookies-fr.html.twig`
- `front/default/template/include/cookies-nl.html.twig`
- `front/default/template/include/legale-nl.html.twig`
- `front/default/template/include/legals-de.html.twig`
- `front/default/template/include/legals-en.html.twig`
- `front/default/template/include/legals-fr.html.twig`

### 2c. Faux positifs confirmés (NE PAS supprimer)

- `bundles/TwigBundle/Exception/error-403.html.twig` et `error-404.html.twig`
  - utilisés dynamiquement par `src/Controller/ExceptionController.php:159`
    (`'@Twig/Exception/error-'.$statusCode.'.html.twig'`).
- Tous les thèmes de formulaire (`admin_fields`, `security_fields`, `datalist_field`,
  `product_values_field`, `videos_field`, `front_calendar_slot_date`,
  `front_sub_categories_catalog_filter`) sont déclarés dans `config/packages/twig.yaml`.

---

## 3. Catégories NON listées (faux positifs structurels)

Le scan brut par nom de classe remontait 250 classes « sans référence ». La grande
majorité sont câblées par convention et **ne sont pas mortes** :

- ~111 contrôleurs (référencés par nom de route, pas par classe)
- ~30 commandes (`#[AsCommand]`, appelées par nom)
- 19 migrations (exécutées par Doctrine)
- subscribers / listeners / message handlers (attributs / interfaces)
- voters, fixtures, validateurs de formulaire, tests
- services injectés **par interface** (`CacheService`, `MenuService`,
  `MarkdownService`, `LayoutService`, `CatalogSearchService`, etc. - bien vivants)

Ces classes nécessitent une analyse de graphe d'appel (PHPStan + règles dead-code) ou
un crawl applicatif pour conclure ; l'heuristique statique ne suffit pas.

---

## 4. Fichiers non suivis par Git

- **Aucun fichier non suivi** hors `.gitignore`
  (`git ls-files --others --exclude-standard` = 0).
- Seuls changements non commités : 5 fichiers **déjà suivis** modifiés
  (`vendor/composer/autoload_*.php`), régénérés par Composer.

---

## Recommandations

1. Supprimer en priorité la **section 1** (8 classes PHP, confiance haute) apres un
   `grep` de contrôle ciblé.
2. Pour la **section 2a**, ouvrir chaque template et confirmer l'absence d'usage
   (aucun `include`/`render`/`setTemplate` correspondant) avant suppression.
3. Ne pas toucher aux sections 2b/2c.
4. Pour aller plus loin (méthodes/propriétés mortes, paramètres inutilisés), envisager
   `phpstan` + `shipmonk/dead-code-detector` en `require-dev` (non installé à ce jour).
