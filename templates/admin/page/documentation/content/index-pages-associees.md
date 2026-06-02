# Colonne « Pages associées » dans les index de modules

Les index admin des **modules plaçables** (carrousel, formulaire, galerie, teaser,
listing, FAQ, agenda, portfolio, etc.) affichent une colonne **Pages associées** :
la liste des pages front sur lesquelles le module est utilisé, sous forme de badges
cliquables.

- Badge **vert** : la page est en ligne pour la locale courante. Le lien ouvre l'URL
  publique front.
- Badge **orange** : la page n'est pas en ligne. Le lien ouvre l'**aperçu**
  (`front_page_preview`, `ROLE_ADMIN`).
- Badge **gris** : page sans URL pour la locale (lien indisponible) ou compteur `+N`
  quand plus de 8 pages utilisent le module.

La colonne couvre la **locale admin courante** uniquement.

---

## Fonctionnement

Le rattachement module -> page passe par le layout :
`Page -> layout -> zones -> cols -> blocks -> action / actionIntls`. Une `Action`
porte le classname du module (`Action.entity`) et chaque `ActionIntl.actionFilter`
porte l'**ID** de l'entité module référencée.

La colonne est **automatique et opt-in par données** : elle apparaît dès qu'au moins
une `Action` référence le classname de l'entité de l'index. Aucun YAML de configuration
à modifier, les futurs modules sont couverts sans intervention.

## Composants

- `PageRepository::findPagesGroupedByActionFilter()` : une **seule** requête, locale
  courante, retourne `array<int actionFilter, Page[]>` (URLs de la locale fetch-jointes).
  **Non mise en cache** volontairement (index admin à faible trafic ; évite tout risque
  de donnée périmée, l'invalidation par clé exacte étant impraticable sur un jeu d'IDs
  variable).
- `App\Service\Admin\ModuleUsageProvider` :
  - `supports(classname)` : l'entité est-elle un module plaçable ? (COUNT sur `Action`,
    result-cache 1 h car les `Action` sont des données de seed quasi immuables).
  - `forItems(...)` : projette les pages de la page de pagination courante en DTO
    `App\Model\Admin\ModulePageUsage` `{name, href, online}`. URL en ligne construite
    via `WebsiteRuntime::domain()`, aperçu via le routeur (`front_page_preview`).
- `AdminController::index` : appelle le provider sur `pagination.getItems()` et passe
  `displayUsedPages` + `usedPages` au template.
- `templates/admin/core/index.html.twig` : colonne dédiée rendue en chips
  `.btn-app small` (`success` / `warning` / `muted`), déjà thématisées light + dark.
  Affichage plafonné à 8 badges + `+N` pour éviter les débordements.

## Coût

Deux requêtes supplémentaires par chargement d'index de module (`supports` mis en
cache + la requête groupée bornée aux ~15 items de la page). Zéro pour les index non
modules.
