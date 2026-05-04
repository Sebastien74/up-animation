## Project Overview

Projet Symfony 7.4 utilisant PHP 8.5.
Application web basée sur Symfony Framework, Doctrine ORM et Webpack Encore pour la gestion des assets.

---

## Commands

### Backend (Symfony)

* Install: `composer install`
* Database: `php bin/console doctrine:database:create` / `php bin/console doctrine:migrations:migrate`
* Fixtures: `php bin/console doctrine:fixtures:load`
* Cache Clear: `php bin/console cache:clear`
* Routing JS: `php bin/console fos:js-routing:dump`

### Frontend (Yarn)

* Install: `yarn install`
* Build: `yarn build`
* Watch: `yarn watch`
* Dev Server: `yarn dev-server`

---

## Architecture

* `src/`: Code source PHP (Controllers, Entity, Service, Form, Command, etc.)
* `templates/`: Templates Twig
* `assets/`: Sources frontend (SCSS, JS, images)
* `public/`: Fichiers publics et assets compilés
* `config/`: Configuration de l'application
* `migrations/`: Migrations de base de données

---

## Code Style

### PHP

* Respect strict de **PSR-12**
* Typage strict obligatoire (`declare(strict_types=1);`)
* Utilisation systématique des types PHP (arguments + retours)
* Injection de dépendances via constructeur uniquement
* Favoriser les services immuables

### Twig

* Respect des conventions Symfony
* Templates découplés et réutilisables (partials, components)

### Frontend

* JavaScript ES6 modulaire
* Utilisation minimale de Stimulus (uniquement si pertinent)
* SCSS structuré (architecture modulaire)

---

## Performance (CRITIQUE)

Les performances PHP sont une priorité absolue.

### Règles obligatoires

* Éviter toute logique coûteuse en boucle (N+1 queries)
* Utiliser les **fetch joins** Doctrine lorsque nécessaire
* Limiter les hydratations d'entités (préférer DTO / projections)
* Activer et exploiter le **cache Symfony** (HTTP / applicatif)
* Utiliser OPcache correctement configuré
* Préférer des services stateless
* Minimiser les appels I/O (DB, API, filesystem)

### Doctrine

* Toujours analyser les requêtes générées
* Indexation SQL obligatoire sur colonnes critiques
* Pagination obligatoire sur gros datasets

---

## Security (CRITIQUE)

La sécurité est non négociable.

### Règles obligatoires

* Validation systématique de toutes les entrées (Request, API, formulaires)
* Utilisation stricte des **Voters / Security component**
* Protection CSRF sur tous les formulaires
* Échapper toutes les sorties Twig (autoescape actif)
* Ne jamais faire confiance aux données utilisateur

### Bonnes pratiques

* Hash des mots de passe avec les algorithmes modernes (password_hasher)
* Gestion stricte des rôles et permissions
* Headers de sécurité HTTP (CSP, HSTS, etc.)
* Logs des actions sensibles

---

## Development Notes

* Utiliser `symfony console` si disponible, sinon `php bin/console`
* L'application utilise `Webpack Encore`
* Configuration via `.env` et `.env.local`

---

## Figma Integration

Si une implémentation depuis Figma est demandée (ex: `@https://www.figma.com/design/...`), respecter strictement :

### Emplacements

* **HTML (Twig)**: `templates/front/template/figma.html.twig`
* **CSS (SCSS)**: `assets/scss/front/default/templates/figma.scss`
* **JS**: `assets/js/front/default/templates/figma.js`

### Contraintes fortes

* **Images**: toujours utiliser `asset('medias/placeholder.jpg')`

### Bootstrap (OBLIGATOIRE)

Utilisation maximale de Bootstrap :

* Grid system (`container`, `row`, `col-*`)
* Utilities (margin, padding, flex, display)
* Components natifs Bootstrap

### JS Bootstrap

* Utiliser les modules JS natifs Bootstrap (import ES6 via Encore)
* Ne pas réinventer des comportements existants (modal, collapse, dropdown, etc.)

### Layout & CSS

* Prioriser **Bootstrap Grid** et **Flex utilities**
* Utiliser CSS Grid uniquement si Bootstrap ne couvre pas le besoin
* Limiter fortement le CSS custom
* SCSS propre, modulaire et maintenable

### Conventions CSS

* Ne jamais utiliser "figma" dans les noms de classes ou ID
* Nommage clair, orienté métier

---

## Anti-patterns interdits

Claude ne doit jamais produire ni recommander les pratiques suivantes.

### Symfony / PHP

* Logique métier dans les Controllers
* Controllers volumineux ou responsables de plusieurs cas métier
* Services sans typage strict
* Méthodes publiques inutiles dans les services
* Absence de `declare(strict_types=1);`
* Injection de dépendances via le container dans les méthodes métier
* Utilisation de `$_POST`, `$_GET`, `$_SESSION`, `$_SERVER` directement hors cas très spécifique
* Requêtes SQL brutes non justifiées
* Utilisation de `dd()`, `dump()` ou `var_dump()` dans du code final
* Exceptions génériques sans contexte métier
* Code silencieux qui masque les erreurs critiques

### Doctrine / Base de données

* Requêtes Doctrine dans les Controllers lorsque la logique dépasse un simple accès trivial
* Requêtes Doctrine dans Twig
* Requêtes N+1
* Chargement complet d'entités pour un simple affichage de liste
* Absence de pagination sur les collections volumineuses
* Absence d'index sur les colonnes utilisées en recherche, tri ou jointure
* Hydratation excessive d'objets Doctrine lorsque des DTO/projections suffisent
* Mélange entre logique métier et logique de persistance

### Twig

* Logique métier dans les templates
* Appels à des services complexes depuis Twig
* Boucles Twig déclenchant indirectement des requêtes Doctrine
* HTML dupliqué au lieu de partials/components
* Désactivation injustifiée de l'autoescape
* Utilisation abusive de `raw`

### Sécurité

* Faire confiance aux données utilisateur
* Absence de validation côté serveur
* Absence de contrôle d'accès explicite sur une action sensible
* Formulaires sans protection CSRF
* Exposition d'informations techniques dans les messages d'erreur
* Construction manuelle d'URLs sensibles sans vérification
* Upload de fichiers sans validation stricte du type, de la taille et du stockage

### Frontend / JavaScript

* JavaScript global non modulaire
* Code JS inline dans Twig sauf cas exceptionnel très limité
* Réimplémentation d'un comportement déjà fourni par Bootstrap
* Manipulation DOM fragile basée sur des sélecteurs trop génériques
* Absence de séparation claire entre JS métier et JS UI
* Stimulus utilisé par réflexe alors qu'un module ES6 simple suffit

### SCSS / CSS / Bootstrap

* CSS custom inutile lorsque Bootstrap couvre déjà le besoin
* Surcharge excessive des classes Bootstrap
* Styles non scopés ou trop globaux
* Usage abusif de `!important`
* Duplications de règles SCSS
* Layout custom complexe alors que Bootstrap Grid/Flex suffit
* Classes CSS nommées selon l'origine du design plutôt que selon le rôle métier

### Figma

* Copie pixel-perfect naïve qui dégrade la maintenabilité
* Génération massive de CSS custom depuis le design
* Ignorer Bootstrap pour recréer une grille ou des espacements équivalents
* Utiliser le mot `figma` dans les classes ou IDs
* Utiliser des images externes au lieu de `asset('medias/placeholder.jpg')`

### Performance

* Calculs lourds exécutés à chaque requête sans cache
* Appels API synchrones non nécessaires dans le cycle HTTP
* Chargement d'assets inutiles sur toutes les pages
* Bundles JS/CSS monolithiques sans justification
* Absence de lazy loading lorsque le comportement n'est pas nécessaire au premier rendu
* Boucles PHP coûteuses sur de gros volumes sans optimisation préalable

---

## Webpack Encore avancé

L'optimisation des assets est obligatoire. Claude doit toujours privilégier une approche modulaire, chargée uniquement lorsque nécessaire.

### Entrypoints

* Séparer clairement les entrypoints par contexte : front, admin, back-office, pages spécifiques
* Ne pas créer un bundle global unique si des pages n'ont pas besoin du même JavaScript ou CSS
* Charger uniquement les entrypoints nécessaires dans Twig avec `encore_entry_script_tags()` et `encore_entry_link_tags()`
* Éviter d'inclure un entrypoint lourd dans un layout global sans justification

### Code splitting

* Utiliser le découpage automatique des chunks lorsque pertinent
* Isoler les dépendances lourdes dans des chunks séparés
* Éviter la duplication de librairies entre entrypoints
* Vérifier l'impact réel des chunks générés après build

### Lazy loading

* Utiliser les imports dynamiques `import()` pour les comportements non critiques au premier rendu
* Lazy loader les modules liés à des composants spécifiques : charts, maps, sliders, editors, uploaders, modals complexes
* Ne pas charger un module JS si le composant correspondant est absent du DOM
* Initialiser les modules conditionnellement via des sélecteurs explicites

### Bootstrap JS

* Importer uniquement les modules Bootstrap nécessaires : `Modal`, `Collapse`, `Dropdown`, `Tooltip`, etc.
* Éviter l'import global de tout Bootstrap JS si seuls quelques composants sont utilisés
* Ne pas réimplémenter en custom JS un comportement natif Bootstrap
* Initialiser proprement les composants Bootstrap via modules ES6

### SCSS / CSS

* Séparer les styles globaux, composants et pages spécifiques
* Éviter les fichiers SCSS monolithiques
* Factoriser variables, mixins et utilitaires uniquement lorsqu'ils sont réellement réutilisés
* Prioriser Bootstrap utilities avant toute règle custom
* Limiter le CSS chargé sur les pages qui n'en ont pas besoin

### Images / Fonts

* Optimiser les images avant intégration
* Préférer les formats modernes lorsque compatibles avec le projet
* Définir les dimensions des images pour limiter le layout shift
* Ne pas charger de fonts inutilisées
* Limiter les variantes de fonts (weights/styles)
* Précharger uniquement les fonts critiques

### Performance build

* Vérifier la taille des bundles après build
* Supprimer les dépendances inutilisées
* Éviter les librairies lourdes pour des besoins simples
* Utiliser des imports ciblés plutôt que des imports globaux
* Contrôler les assets générés dans `public/build`

---

## Review checklist pour PR

Avant de considérer une implémentation comme correcte, Claude doit appliquer cette checklist.

### PHP / Symfony

* `declare(strict_types=1);` présent lorsque pertinent
* Arguments et retours typés
* Pas de logique métier dans les Controllers
* Services correctement injectés via constructeur
* Responsabilités clairement séparées
* Exceptions explicites et utiles
* Aucun `dd()`, `dump()`, `var_dump()` ou code de debug résiduel

### Doctrine / SQL

* Pas de requêtes N+1
* Requêtes SQL/Doctrine adaptées au besoin réel
* Pagination présente sur les listes volumineuses
* Index nécessaires identifiés sur recherche, tri et jointures
* Hydratation limitée au strict nécessaire
* Pas de requêtes Doctrine déclenchées depuis Twig

### Sécurité

* Entrées utilisateur validées côté serveur
* Contrôles d'accès explicites sur actions sensibles
* CSRF activé sur les formulaires
* Sorties Twig échappées correctement
* Aucun usage injustifié de `raw`
* Uploads sécurisés si présents
* Données sensibles non exposées dans erreurs, logs ou templates

### Performance

* Pas de calcul lourd non caché dans le cycle HTTP
* Pas d'appel API synchrone inutile
* Cache Symfony utilisé lorsque pertinent
* Assets chargés uniquement sur les pages concernées
* Bundles JS/CSS raisonnables
* Lazy loading appliqué aux modules non critiques

### Frontend

* JS modulaire ES6
* Pas de JS global inutile
* Pas de JS inline dans Twig sauf exception justifiée
* Bootstrap utilisé au maximum avant CSS/JS custom
* Comportements Bootstrap natifs privilégiés
* Sélecteurs DOM robustes et explicites

### SCSS / UI

* SCSS structuré et maintenable
* Pas de duplication de règles
* Pas d'abus de `!important`
* Responsive vérifié
* Accessibilité minimale vérifiée : labels, focus, contrastes, navigation clavier
* Layout basé prioritairement sur Bootstrap Grid/Flex

### Figma

* Respect strict des chemins imposés
* Images remplacées par `asset('medias/placeholder.jpg')`
* Pas de classe ou ID contenant `figma`
* Bootstrap utilisé au maximum
* CSS custom limité au strict nécessaire
* Rendu maintenable plutôt que copie pixel-perfect fragile

### Qualité finale

* Code lisible et cohérent avec l'architecture existante
* Pas de duplication évidente
* Nommage clair et métier
* Aucune dépendance ajoutée sans justification
* Aucune régression évidente sur sécurité, performance ou maintenabilité

---

## Qualité globale attendue

* Code lisible, maintenable et optimisé
* Aucun compromis sur performance et sécurité
* Approche pragmatique : utiliser l'existant avant de créer du custom
* Cohérence stricte backend / frontend