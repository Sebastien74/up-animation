# Performance - Correctif N+1 du catalogue (home)

Réduction du nombre de requêtes Doctrine sur la home front, de **235 à 79**
(-66 %, temps DB ~243 ms -> ~155 ms), sans aucun changement de rendu.

Commits sur `main` :
- `a7bf2842` - `onlyForUrl` honoré + amorçage des entités rendues (235 -> ~97)
- `f763c08e` - menu : `disabledSubCategories` + amorçage EAGER features/values (97 -> 79)

> Périmètre : la **home** (`front_index`), qui assemble des blocs de layout. Le N+1
> venait surtout du **menu de navigation** et de la **résolution de liens**, pas des
> cartes produit.

---

## Cause racine

`IntlModel::intlLink` construit un `ProductModel` juste pour résoudre une **URL** de lien
(`ProductModel::fromEntity($targetProduct, ['onlyForUrl' => true])`). Or
`ProductModel::fromEntity` **ignorait l'option `onlyForUrl`** et bâtissait le modèle complet
par produit lié : `getValues()`, `getSubCategories()`, médias, `findOnlineByCatalogs()`.
Chaque lien de menu déclenchait donc une cascade de requêtes.

S'ajoutaient :
- les collections `EAGER` de `Feature` / `FeatureValue` (`intls`, `mediaRelations`), chargées
  une par entité dans le warmup de `getValues()` (le warmup itère via `toIterable()`, qui ne
  peut pas fetch-joindre) ;
- la branche menu de `IntlModel::intlLink` qui chargeait les sous-catégories par produit.

---

## Correctifs

### 1. `ProductModel::fromEntity` honore `onlyForUrl`

Quand `onlyForUrl` est vrai, le modèle se limite à ce qu'un lien exige (URL, intl) :
défauts `disabled*` (values, products, layout, medias, categories, subCategories, agency),
saut de `findOnlineByCatalogs()` / `findByProductIds()` et de `getSubCategories()`.

> **Important** : ne pas désactiver `info` ni `intl` dans ce court-circuit. Le footer et le
> menu affichent la **ville / le code postal des agences** via `information`/`address` ;
> les désactiver vide ces libellés (régression « Agence » sans nom). Le flag est aligné sur
> le comportement de `CatalogModel`, qui expansait déjà `onlyForUrl`.

`getSubCategories()` n'utilisait `disabledSubCategories` que pour le warmup du cache, pas pour
la boucle `foreach ($product->getSubCategories())`. La garde a été remontée au niveau de
l'appel : `$subCategories = $onlyForUrl || !empty($options['disabledSubCategories']) ? [] : ...`.

### 2. Amorçage par lots (priming de l'UnitOfWork)

Plutôt qu'un JOIN unique (produit cartésien des multiples collections to-many), on précharge
chaque collection manquante par une requête `WHERE ... IN (:ids)`. Une fois hydratées dans
l'UnitOfWork, les accès `LAZY`/`EAGER` ultérieurs ne requêtent plus.

- `ProductRepository::primeForRendering($products, $locale)` : values (+ value, feature),
  subCategories (+ catégorie), intls (locale), urls, mediaRelations (+ media), puis
  `MediaRepository::primeThumbsAndIntls()`. Appelé via le hook surchargeable
  `ActionController::primeRenderEntities()` (teaser) et dans `CatalogSearchService` (listing).
- `FeatureRepository::primeWebsiteEager()` et `FeatureValueRepository::primeWebsiteEager()` :
  préchargent les collections `EAGER` (intls, mediaRelations) du site entier, appelés une fois
  dans le warmup de `getValues()`. `toIterable()` réutilise ensuite les entités amorcées.

---

## Mesurer (méthode de diagnostic)

1. **Compteur rapide** : en-tête HTTP `Server-Timing: db;dur=...;desc="N queries"` sur la
   réponse de la page.
2. **Détail** : profiler Symfony, `/_profiler/<token>?panel=db`. Le token est dans l'en-tête
   `X-Debug-Token`.
3. **Isoler les vrais N+1** : compter les requêtes à signature lazy (`FROM <table> ... WHERE <fk> = ?`),
   pas les occurrences brutes de `FROM` (qui double-comptent jointures et sous-requêtes).
4. **Trouver l'appelant** : instrumenter temporairement le constructeur de modèle
   (`ProductModel::fromEntity`) avec `debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)` pour
   logger la chaîne d'appel, puis retirer l'instrumentation.
5. **Non-régression** : comparer le HTML avant/après en neutralisant les jetons CSRF et nonces
   (`sed -E 's/[a-f0-9]{6,}/X/g'`) ; viser un diff de contenu nul.

---

## Pattern à retenir

- Une option « légère » (`onlyForUrl`, `disabled*`) passée à un constructeur de modèle n'a
  d'effet que si **ce modèle la lit réellement**. Vérifier que le flag court-circuite bien les
  chargements coûteux, pas seulement un warmup secondaire.
- Pour plusieurs collections to-many, **préférer plusieurs requêtes batch `WHERE IN`** (amorçage
  de l'UnitOfWork) à un seul JOIN monstrueux (produit cartésien).
- Une association `EAGER` non fetch-jointe = un `SELECT` par entité parente. La précharger par
  lot la neutralise ; `toIterable()` ne peut pas fetch-joindre, d'où l'amorçage séparé.

Restes connus (hors module catalogue, non traités) : `layout_action_intl`, `module_slider`,
`module_newscast` sur la home.
