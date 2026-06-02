# Colonne « Pages associées » dans les index de modules

Les index admin des **modules plaçables** (carrousel, formulaire, galerie, teaser,
listing, FAQ, agenda, portfolio, etc.) affichent une colonne **Pages associées** :
la liste des emplacements front sur lesquels le module est utilisé, sous forme de
badges cliquables.

Un module peut être posé non seulement dans le layout d'une **page**, mais aussi dans
le layout d'un **autre template** : actualité, produit, catalogue, fiche portfolio,
**catégorie**, listing, formulaire. Tous ces emplacements remontent dans la colonne.

- Badge **vert** : emplacement en ligne pour la locale courante. Le lien ouvre l'URL
  publique front.
- Badge **orange** : page hors ligne. Le lien ouvre l'**aperçu**
  (`front_page_preview`, `ROLE_ADMIN`, pages uniquement).
- Badge **gris** : emplacement sans lien (catégorie, template sans URL, ou hors ligne
  pour un template non-page) ou compteur `+N` au-delà de 8 badges.

Les **catégories** (actualités, portfolio) sont volontairement affichées **par leur seul
nom**, sans lien : ce sont des templates, pas une page front unique.

La colonne couvre la **locale admin courante** uniquement.

---

## Fonctionnement

Le rattachement module -> emplacement passe par le layout :
`(template) -> layout -> zones -> cols -> blocks -> action / actionIntls`. Une `Action`
porte le classname du module (`Action.entity`) et chaque `ActionIntl.actionFilter`
porte l'**ID** de l'entité module référencée.

Le **propriétaire** d'un layout (page, actualité, produit, catégorie...) est toute entité
qui porte une clé étrangère `layout`. Cette liste est **découverte automatiquement** depuis
les métadonnées Doctrine (hors `Page`, traitée à part, et `Zone`, qui est un enfant de
layout), puis mise en cache : tout futur template avec un layout est couvert sans
intervention.

La colonne est **automatique et opt-in par données** : elle apparaît dès qu'au moins
une `Action` référence le classname de l'entité de l'index. Aucun YAML de configuration
à modifier, les futurs modules sont couverts sans intervention.

## Composants

- `PageRepository::findPagesGroupedByActionFilter()` : une **seule** requête, locale
  courante, **projection scalaire** (`array<int actionFilter, array<rows {pageId,
  adminName, urlId, code, online}>>`). Le scalaire est volontaire : un select d'entité
  laisserait l'object hydrator **dédupliquer** une page hébergeant plusieurs modules,
  ne gardant qu'un seul `actionFilter` (cas de la page d'accueil avec deux sliders).
  **Non mise en cache** volontairement (index admin à faible trafic ; évite tout risque
  de donnée périmée, l'invalidation par clé exacte étant impraticable sur un jeu d'IDs
  variable).
- `App\Service\Admin\ModuleUsageProvider` :
  - `supports(classname)` : l'entité est-elle un module plaçable ? (COUNT sur `Action`,
    result-cache 1 h car les `Action` sont des données de seed quasi immuables).
  - `forItems(...)` : projette les emplacements de la page de pagination courante en DTO
    `App\Model\Admin\ModulePageUsage` `{name, href, online}`. Pages via
    `PageRepository::findPagesGroupedByActionFilter()` (URL en ligne via
    `WebsiteRuntime::domain()`, aperçu via `front_page_preview`), puis emplacements
    non-page via `appendNonPageUsages()`.
- `LayoutRepository::findNonPageLayoutIdsGroupedByActionFilter()` : une **seule** requête
  renvoyant `array<int actionFilter, int[] layoutId>` pour les layouts **non-page**
  (`LEFT JOIN Page WITH p.layout = l ... p.id IS NULL`). Renvoie vide dès qu'aucun module
  n'est posé hors page (cas courant).
- `App\Service\Admin\LayoutOwnerResolver` : `resolve(layoutIds, ...)` -> `array<int layoutId,
  ModulePageUsage>`. Liste des entités propriétaires découverte depuis les métadonnées
  Doctrine et **mise en cache** (`cache.app`, clé `module_layout_owners_v1`). Une requête
  projetée bornée **par type de propriétaire**, avec **arrêt anticipé** dès que tous les
  layouts sont résolus. `linkable = a une association urls ET n'est pas une catégorie`.
- `AdminController::index` : appelle le provider sur `pagination.getItems()` et passe
  `displayUsedPages` + `usedPages` au template.
- `templates/admin/core/index.html.twig` : colonne dédiée rendue en chips
  `.btn-app small` (`success` / `warning` / `muted`), déjà thématisées light + dark.
  Affichage plafonné à 8 badges + `+N` pour éviter les débordements.

## Coût

Cas courant (module posé uniquement dans des pages) : trois requêtes par chargement
d'index de module (`supports` mis en cache + requête pages groupée + requête layouts
non-page qui renvoie vide). Lorsque des emplacements non-page existent, s'ajoute **une
requête projetée par type de propriétaire concerné** (bornée au petit jeu de layouts
trouvés, arrêt anticipé), la carte des propriétaires étant mise en cache. Coût **borné**
(jamais fonction du nombre de lignes : pas de N+1). Zéro pour les index non modules.
