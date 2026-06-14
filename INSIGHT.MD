# INSIGHT — Module d'analyse de performance & rendu des pages

> État du module au 2026-06-14. Document de reprise : ce qui est fait, ce qui
> manque pour une analyse complète, et la feuille de route.

## 1. Objectif

Analyser le rendu front des pages (performance, rendu, SEO, accessibilité,
bonnes pratiques), **historiser les résultats en base**, et à terme permettre
une **boucle d'analyse → correction** : interpréter les données remontées pour
proposer les corrections nécessaires (en code, via PR validée humainement).

> ⚠️ L'indice produit est un **indice indicatif** (analyse statique du HTML
> rendu), **ce n'est PAS une mesure Lighthouse / Core Web Vitals**.

## 2. Périmètre & sécurité

- **Admin / preview uniquement** : déclenché par `ROLE_ADMIN`, derrière
  `%security_token%`, rendu en `preview=true`. **Aucun impact sur la navigation
  front publique.**
- Le **cron** analyse en HTTP les pages **publiées** (live), sans contexte admin.
- Interfaces couvertes : **Page**, **Newscast**, **Product** (toute entité
  possédant une `seo_url` en ligne).

## 3. Ce qui est FAIT

### 3.1 Analyse (service)
- `src/Service/Admin/PageAnalyzer.php` (+ `PageAnalyzerInterface.php`)
  - Parse le HTML rendu via `DOMDocument`/`DOMXPath`.
  - ~40 contrôles répartis en 7 catégories : **Performance & rendu**,
    **Ressources**, **Images & médias**, **Structure & DOM**, **SEO**,
    **Accessibilité**, **Bonnes pratiques & sécurité**.
  - Retourne : `meta` (poids, DOM, images, requêtes, domaines tiers, renderMs),
    `score` (heuristique 0-100), `summary` (compteurs high/medium/low),
    `groups` (constats détaillés : sévérité, valeur, recommandation).

### 3.2 Rendu preview par interface (mutualisé)
- `src/Controller/Admin/PageAnalysisTrait.php`
  - `analyzePreview()` : forward vers le bon contrôleur preview selon l'interface
    (`page` → `IndexController::preview`, `newscast` → `NewscastController::preview`,
    `catalogproduct` → `CatalogController::preview`), analyse, historise.
  - `previewUrlFor()` : génère l'URL de preview par interface.
- `src/Service/Admin/PageAnalysisRecorder.php`
  - Persistance d'un snapshot + purge (best-effort), partagé admin + cron.

### 3.3 Outil ponctuel (modale layout)
- Bouton « Analyser la page » dans `templates/admin/core/layout.html.twig`
  (passe `interface.name`).
- Action `admin_layout_analyze` dans `src/Controller/Admin/Layout/LayoutController.php`.
- Modale `templates/admin/core/layout/page-analysis.html.twig` :
  métriques clés, résumé global, constats groupés, **historique** (sparkline +
  deltas), actions **Ouvrir la preview / Relancer / Copier le rapport**.
- JS `assets/js/admin/plugins/page-analysis.js` (chargé via `edit-element.js`).

### 3.4 Dashboard « Analyse des pages »
- `src/Controller/Admin/Core/PageAnalysisController.php`
  - `admin_page_analysis_dashboard` : liste toutes les pages en ligne
    (Page/Newscast/Product) avec leur dernier indice.
  - `admin_page_analysis_run` : analyse une page (AJAX) → JSON métriques.
- Template `templates/admin/page/core/page-analysis-dashboard.html.twig` :
  tableau, « Analyser » par ligne, « Tout analyser » (séquentiel + barre de
  progression), filtre client.
- JS `assets/js/admin/pages/page-analysis-dashboard.js` (entrée Encore
  `admin-page-analysis` dans `webpack.config.js`).
- Lien sidebar : `templates/admin/include/sidebar/layout.html.twig`
  (« Analyse des pages », `ROLE_ADMIN`).

### 3.5 Persistance & historique
- Entité `src/Entity/Seo/PageAnalysis.php` (table `upa_seo_page_analysis`).
- Repository `src/Repository/Seo/PageAnalysisRepository.php` :
  `findLatestSnapshots()`, `findLatestPerPage()`, `pruneOldSnapshots()`.
- Migrations :
  - `migrations/Version20260613153000.php` (création table)
  - `migrations/Version20260613160600.php` (ajout colonne JSON `report`)
- Conservation : **20 derniers relevés par page** (purge auto).

### 3.6 Cron périodique
- `src/Command/PageAnalysisRunCommand.php` (`app:page-analysis:run`)
  - Fetch HTTP des pages publiées, **mapping domaine ↔ langue**
    (`Domain.locale`, repli domaine par défaut).
  - Options : `--website`, `--max-urls` (500), `--max-seconds` (120),
    `--timeout` (30), `--user-agent`.
- Planifié dans `src/Service/Development/ScheduledCommandCatalog.php`
  (`0 6 * * 1` — lundi 6H, actif). Propagation aux sites existants :
  `php bin/console app:scheduler:install`.

### 3.7 Données stockées (schéma `upa_seo_page_analysis`)
| Champ | Description |
|---|---|
| `website_id`, `urlCode`, `locale` | clé logique de la page |
| `score` | indice indicatif 0-100 (nullable) |
| `htmlKb`, `domCount`, `imagesCount`, `requests`, `renderMs`, `externalDomains` | agrégats |
| `severityHigh/Medium/Low` | compteurs de constats par sévérité |
| `report` (JSON) | **rapport détaillé complet** (meta, score, summary, groups) |
| `createdAt` | horodatage |

## 4. Ce qui MANQUE pour une analyse complète & la boucle de correction

### 4.1 Accès aux données pour exploitation par Claude
> **Mode retenu** : l'analyse/correction se fera dans l'**environnement local**
> de l'équipe, **BDD connectée**. Claude Code, exécuté en local, peut alors lire
> directement les snapshots (Doctrine / SQL sur `upa_seo_page_analysis`,
> y compris la colonne JSON `report`). Le pont d'export ci-dessous devient donc
> **optionnel** (partage / CI / hors-ligne), **pas un prérequis** de la boucle locale.

- [ ] (Optionnel) **Commande d'export** `app:page-analysis:export` (JSON/CSV,
  filtrable par site / indice / sévérité / interface) pour partager les données
  hors environnement local ou alimenter un pipeline.
- [ ] (Optionnel) Helper de lecture (ex. `app:page-analysis:report` affichant en
  console les pires pages / constats fréquents) pour accélérer l'exploitation.

> Rappel : depuis une session distante éphémère (Claude Code on the web), il n'y
> a **pas** d'accès à la BDD de prod — d'où l'usage en local décrit ci-dessus.

### 4.2 Pour rendre les corrections actionnables
- [ ] **Mapping symptôme → code** : le rapport décrit des symptômes
  (« scripts bloquants », « images sans dimensions ») sur une URL, **pas
  l'emplacement source** (template Twig, configuration de bloc, asset).
  Rattacher, quand c'est déterministe, un constat à son origine.
- [ ] **Synthèse priorisée** : vue « pires pages » + « problèmes les plus
  fréquents » (tri par indice, agrégation des constats) pour décider quoi
  corriger d'abord.
- [ ] **Vue détail par page** dans le dashboard (historique + constats complets),
  aujourd'hui le détail n'est visible que dans la modale layout.

### 4.3 Qualité de l'analyse
- [ ] **Mesures réelles** (LCP/TBT/CLS) : intégrer Lighthouse / PageSpeed /
  Web Vitals pour compléter l'analyse statique par des métriques mesurées.
- [ ] **Cohérence preview vs live** : l'admin analyse le rendu *preview*
  (y compris non publié), le cron le rendu *live publié*. À documenter /
  uniformiser si besoin de comparabilité stricte.
- [ ] **Multi-domaine par langue** : géré (repli défaut), mais à valider sur un
  site réel multi-domaines.

### 4.4 Industrialisation
- [ ] **Tests** (unitaires `PageAnalyzer`, fonctionnels endpoints/commande).
- [ ] **i18n** : nombreuses chaînes FR en dur (labels/reco) → clés de traduction.
- [ ] **Volumétrie** : « Tout analyser » et le cron sont séquentiels ;
  pour gros catalogues, prévoir lots / file d'attente (Messenger).
- [ ] **Seuils configurables** : les seuils de sévérité sont en dur dans
  `PageAnalyzer` → rendre configurables.

## 5. Notes de déploiement (rappel)
1. `php bin/console doctrine:migrations:migrate` (2 migrations).
2. `php bin/console app:scheduler:install` (cron sur sites existants).
3. Recompiler les assets (Encore) — entrée `admin-page-analysis` + JS modale.
4. Vérifier la **politique réseau** : le serveur doit pouvoir requêter ses
   propres domaines publics (pour le cron).
5. Test manuel : `php bin/console app:page-analysis:run --website=1 --max-urls=20`.

## 6. Prochaine étape recommandée
L'exploitation se faisant **en local avec la BDD connectée** (cf. 4.1), la boucle
analyse → correction est déjà possible (lecture directe des snapshots). Démarrer
par **4.2** : synthèse priorisée (pires pages / problèmes fréquents) + amorce du
mapping symptôme → code. Un helper console de lecture (4.1, optionnel) peut
accélérer l'exploitation.

---
*Toute correction issue de ce module passe par le cycle normal : branche →
modification → revue humaine → déploiement. Pas d'intervention automatique en
production.*
