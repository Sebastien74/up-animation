# Différences `src/` — uninstantmagique vs up-animation

Comparaison des dossiers source des deux projets (base CMS Symfony commune) :

- **UIM** = `C:\wamp64\www\uninstantmagique\src`
- **UP** = `C:\wamp64\www\up-animation\src`

Généré le 2026-07-19. Les deux projets partent du même socle ; ils divergent sur
(1) le contenu de marque (fixtures, imports, scénarios mail) et (2) quelques
fonctionnalités arrivées à des dates différentes de chaque côté.

---

## 1. Fichiers présents uniquement dans **UIM** (absents de UP)

Essentiellement des commandes de mise en page / restructuration propres au site
uninstantmagique, plus deux services de fixtures.

| Fichier | Nature |
|---|---|
| `Command/HomeHeroPosterCommand.php` (`app:home:hero-poster`) | Setup home UIM |
| `Command/HomeHeroVideoCommand.php` (`app:home:hero-video`) | Setup home UIM |
| `Command/HomeLayoutSetupCommand.php` (`app:home:setup`) | Setup home UIM |
| `Command/HomeUspCardsCommand.php` (`app:home:usp-cards`) | Setup home UIM |
| `Command/HomeRealisationsTeaserCommand.php` (`app:home:realisations-teaser`) | Setup home UIM |
| `Command/HomeRemoveLinkZoneCommand.php` (`app:home:remove-link-zone`) | Setup home UIM |
| `Command/CatalogRestructureCommand.php` (`app:catalog:restructure`) | Restructuration catalogue |
| `Command/MigrateRealisationsToCatalogCommand.php` (`app:migrate:realisations-to-catalog`) | Migration réalisations → catalogue |
| `Command/PageAgenciesTeaserCommand.php` (`app:page:agencies-teaser`) | Page agences |
| `Command/ImportJsonContentCommand.php` (`app:import:json-content`) | Import contenu JSON |
| `Service/DataFixtures/HtmlLayoutBuilder.php` | Construction de layouts HTML (fixtures) |
| `Service/DataFixtures/JsonContentFixtures.php` | Fixtures contenu réel (blog, réalisations) |
| `Form/Widget/FiligraneType.php` | Widget de formulaire filigrane |

> UIM est plus avancé sur l'industrialisation de sa home et de son catalogue
> (commandes de setup dédiées + import JSON de contenu réel).

## 2. Fichiers présents uniquement dans **UP** (absents de UIM)

Aucun. Tous les fichiers de UP existent aussi dans UIM (avec ou sans différences
de contenu).

---

## 3. Fichiers présents des deux côtés mais **différents**

### a) Fonctionnalités où **UP est en avance**

| Fichier | Écart | Détail |
|---|---|---|
| `Entity/Module/Catalog/Product.php` | +17 lignes | Relation `ManyToOne` vers `Faq` (champ « FAQ associée » du produit) + getter/setter. Absent de UIM. |
| `Form/Type/Module/Catalog/ProductType.php` | +24 lignes | Champ `faq` (EntityType) dans l'onglet Configuration, filtré par website. Absent de UIM. |
| `Model/Module/FaqModel.php` | +7 lignes | Émission du JSON-LD `FAQPage` pour les FAQ embarquées sans Block (FAQ de fiche produit). Absent de UIM. |
| `Twig/Content/LayoutRuntime.php` | +8 lignes | Classes de fond auto : `video-bg → bg-primary`, fallback `bg-beige`/`bg-white` selon position. Absent de UIM. |

> Chantier **FAQ produit / AI Overviews** : présent dans UP, pas encore dans UIM.

### b) Fonctionnalités où **UIM est en avance**

| Fichier | Écart | Détail |
|---|---|---|
| `Twig/Content/CoreRuntime.php` | UIM +34 lignes | Méthode `titleHighlight()` (span coloré après `:`) + `truncate()` avec fallback longueur nulle (`$length ??= 30`). UP n'a ni la méthode, ni le fallback (signature non-nullable). |
| `Service/Core/Uploader.php` | UIM +15 lignes | `Uploader` UIM ajoute un helper `addFlash()` gardé par `hasSession()` (no-op en CLI/fixtures pour éviter `SessionNotFoundException`). UP écrit encore directement dans `getSession()->getFlashBag()` (risque hors contexte HTTP). |
| `Service/DataFixtures/NewscastFixtures.php` | UP +48 lignes | UP génère 15 newscasts de démo (faker + média + URL). UIM a retiré la démo (catégorie « main » vide, contenu réel via `JsonContentFixtures`). |

### c) Différences de **contenu de marque** (remplacement 1:1, structure identique)

Fichiers entièrement remplacés ligne pour ligne : ce sont des données propres à
chaque marque (produits, couleurs, scénarios mail, PDF), pas des divergences de code.

| Fichier | Volume | Nature |
|---|---|---|
| `Command/ImportLiveProductsCommand.php` | 279↔279 | Import des fiches produits (source live différente) |
| `Command/ImportRepairMediaCommand.php` | 146↔146 | Réparation médias liée à l'import |
| `Service/DataFixtures/ColorFixtures.php` | 238↔238 | Palette de couleurs de marque |
| `Service/Development/MailScenarioSender.php` | 191↔191 | Scénarios d'emails |
| `Service/Pdf/ProductPdfRenderer.php` | 295↔295 | Rendu PDF fiche produit |

### d) Différences **mineures** (1 à 6 lignes, ajustements ponctuels)

| Fichier | Écart |
|---|---|
| `Controller/Front/Action/SliderController.php` | +1 |
| `Entity/BaseMediaRelation.php` | ~2 |
| `Form/Manager/Front/FormManager.php` | ~5 |
| `Model/Module/ProductModel.php` | ~3 |
| `Security/PasswordExpire.php` | ~2 |
| `Service/Content/ImageThumbnail.php` | ~22 |
| `Service/Content/LocaleService.php` | ~2 |
| `Service/Content/SeoService.php` | ~2 |
| `Service/Core/MailerService.php` | ~6 |
| `Service/DataFixtures/MapFixtures.php` | ~2 |
| `Twig/Content/ThumbnailRuntime.php` | ~14 |
| `Twig/CoreExtension.php` | ~2 |

---

## 4. Synthèse

- **Structure identique**, socle CMS commun. UP n'a aucun fichier exclusif ;
  UIM en a 13 (surtout commandes de setup home/catalogue + fixtures de contenu).
- **UP devance UIM** sur la **FAQ produit** (entité, form, JSON-LD) et les classes
  de fond auto (`LayoutRuntime`).
- **UIM devance UP** sur : `titleHighlight()`, le `truncate()` tolérant à une
  longueur nulle, et surtout le **garde-session de l'`Uploader`** (à porter dans UP :
  cf. règle « toute session en runtime front doit être gardée `hasSession()` »).
- Le gros du volume de diff (`ImportLiveProducts`, `ColorFixtures`, `MailScenario`,
  `ProductPdfRenderer`) n'est **pas du code divergent** mais du **contenu de marque**.

### Portages suggérés vers UP

1. `Service/Core/Uploader.php` — reprendre le helper `addFlash()` gardé de UIM
   (évite `SessionNotFoundException` en CLI/fixtures).
2. `Twig/Content/CoreRuntime.php` — `truncate()` avec `$length ??= 30` (robustesse
   gabarit) et éventuellement `titleHighlight()` si le besoin existe côté UP.
