# Performance - Correctif N+1 du catalogue (home)

Réduction du nombre de requêtes Doctrine sur la home front, de **235 à ~49**
(première passe 235 -> 73, deuxième passe médias 73 -> ~49), sans aucun changement de rendu.

Commits sur `main` :
- `a7bf2842` - `onlyForUrl` honoré + amorçage des entités rendues (235 -> ~97)
- `f763c08e` - menu : `disabledSubCategories` + amorçage EAGER features/values (97 -> 79)
- layout : fetch-join de `b.actionIntls` (locale courante) dans le rendu des blocs (79 -> 73)

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

### 3. Layout : fetch-join des `actionIntls` des blocs

La fonction Twig `intlAction(block)` (i18n) itère `block.actionIntls` pour trouver la traduction
de la locale courante. Les blocs de la home étaient chargés sans cette collection
(`PageRepository::optimizedQueryBuilder`), d'où un `SELECT` par bloc. Ajout de
`leftJoin('b.actionIntls', 'bai', 'WITH', 'bai.locale = :locale')` + `addSelect`, en miroir du
join `b.intls` déjà présent (hydratation partielle locale, sans produit cartésien notable).
Bénéficie à toutes les pages front. Résultat caché 1 h (`findIndex`), donc penser au
`cache:clear` pour valider.

### 4. Deuxième passe : médias des blocs et des teasers (73 -> ~49)

Le N+1 résiduel venait des collections `EAGER` de `Media` (`thumbs`, `intls`) et des
`mediaRelations` (`media`, `intl`, eux aussi `EAGER`), chargées **une par média**.

- **`PageRepository::optimizedQueryBuilder`** : fetch-join de la chaîne média des blocs et de
  la page (`bmr.media` + `bmr.intl` + `media.thumbs` + `media.intls`, idem `p.mediaRelations`).
  Les côtés to-one ne multiplient pas ; seul `thumbs` ajoute des lignes (cartésien borné).
  Bénéficie à toutes les pages CMS riches en images ; servi depuis le cache sur cache-hit.
- **`SliderRepository::findOneByWithRelations`** : ajout de `m.thumbs` + `m.intls` au join média.
- **`CatalogController::teaser`** : `disabledValues => true` dans les options du modèle. Les
  cartes du teaser (`macros/card.html.twig`) n'affichent **pas** de feature/value (seulement
  intl, média, ville/adresse via `information`), donc `getValues()` et tout son amorçage
  features/values (`primeWebsiteEager` x2, `findAllByWebsiteIterate`, `findByCatalog`) étaient
  inutiles ici (~11 requêtes économisées).
- **`ProductRepository::primeForRendering`** : l'étape « media relations » joint désormais
  `m.thumbs` + `m.intls`. Subtilité : `Media.thumbs`/`Media.intls` étant `EAGER`, ils se
  déclenchent **dès la première hydratation du média** ; l'ancien `primeThumbsAndIntls()`
  (deux requêtes batch séparées) s'exécutait donc **trop tard** et était neutralisé. Joindre
  les deux collections au moment où le média est chargé initialise tout en une requête.

> Exception au pattern « batch `WHERE IN` plutôt que JOIN » ci-dessous : il ne vaut que pour
> des collections `LAZY`. Pour une collection `EAGER`, le premier chargement de l'entité
> parente la charge de toute façon ; il faut donc la fetch-joindre **à ce moment-là**, sinon
> tout amorçage séparé ultérieur est redondant.

### 5. Cache de fragments des blocs statiques (~49 -> ~20 en anonyme)

Les blocs `core-action` sont rendus via `render_esi(controller(...))` dans
`templates/front/default/include/zone.html.twig`. Sans reverse-proxy, `render_esi` rend en
inline -> la sous-requête (et ses requêtes SQL) s'exécute à chaque affichage.

`twig/cache-extra` étant installé, on enveloppe le rendu des blocs **statiques sûrs** dans
`{% cache clé ttl(3600) %}` : sur cache-hit, la sous-requête n'est pas exécutée du tout.

- **Whitelist** (statique, public, sans CSRF/état utilisateur/pagination) : `SliderController::view`,
  et les `teaser` de Catalog / Newscast / Gallery / Portfolio / Faq. Exclus : formulaires
  (Contact, Form, Newsletter, Search -> CSRF), contenu utilisateur (espace sécurisé, panier,
  favoris), listes paginées/filtrées (`index`/`search` -> query string absente de la clé).
- **Clé** : `frag-{action}-{block.id}-{locale}-{url.id}-{cacheClearDate.timestamp}`. Le timestamp
  de `website.cacheClearDate` (bumpé par `CacheInvalidationSubscriber` à chaque modif de contenu)
  fait office de version : toute édition invalide les fragments. TTL 1 h en filet de sécurité.
- **Garde de requête** : pas de cache si la requête porte des query params (`?page`, filtres...),
  pour ne pas figer une variante sous une clé statique.
- **Garde ROLE_ADMIN (sécurité, NE PAS retirer)** : les fragments cachables rendent les overlays
  `WebmasterEdit` (visibles seulement en `ROLE_ADMIN`), dont les liens pointent vers des routes
  `admin-%security_token%/...` qui **contiennent le jeton secret**. Mutualiser le cache entre
  admin et visiteur divulguerait ce jeton au public si un admin réchauffe le cache en premier.
  Donc : **admin = rendu live (non caché)**, visiteur = cache. Comportement volontaire.

Résultat : home anonyme **~49 -> ~20 requêtes**. Bénéficie aussi aux autres pages partageant
ces blocs (sliders, teasers, menu).

> Rappel timing : le wall-time élevé en local (plusieurs secondes) vient de **Xdebug en mode
> `develop`** + profiler dev, pas du SQL (la DB est à ~17-27 ms). Mettre `xdebug.mode=off` hors
> session de debug pour un local représentatif.

### 6. Quels blocs sont cachables (référence)

**Point clé d'architecture** : sur les ~40 types de `BlockType` (`BlockTypeFixtures`), **un seul
passe par `render_esi(controller(...))` : `core-action`** (slug `core-action`, catégorie `core`).
Tous les autres (`title`, `text`, `media`, `link`, `card`, `video`, `collapse`, `modal`, `alert`,
`icon`, `counter`, `social-networks`, `zones-navigation`, les `layout-*` et les `form-*`) sont
rendus **inline** via `renderBlock(_context)` : ils n'émettent pas de sous-requête, ils sont déjà
dans la requête page (et son result-cache `findIndex` 1 h). **Le `{% cache %}` de la phase 5 ne les
concerne donc pas** : il ne sert qu'à éviter la sous-requête HTTP des blocs `core-action`.

Pour un bloc `core-action`, la sûreté dépend de **l'action** (`Action.controller::action`) qu'il
invoque, pas du type de bloc. Classement des actions front :

**CACHABLES sans réserve** (affichage statique, public, déterministe, ni CSRF, ni nonce, ni état
utilisateur, ni dépendance à la query) :
- `SliderController::view`
- `CatalogController::teaser`, `CatalogController::teaserCategories`
- `NewscastController::teaser`
- `GalleryController::teaser`
- `PortfolioController::teaser`
- `FaqController::view`, `FaqController::teaser`
- `TimelineController::view`
- `TableController::view`
- `InformationController::view`
- `MapController::view` (markers statiques via `data-*`, pas de script inline nonce'd)

**CACHABLES sous condition** :
- `TabController::view` : OK **sauf si un onglet contient un formulaire** (alors CSRF figé).
- `AgendaController::view` : contenu **temporel** (filtre « à partir de maintenant ») -> n'ajouter
  qu'avec un TTL court, sinon des évènements passés restent affichés jusqu'à expiration.

**JAMAIS CACHABLES** :
- Formulaires (jeton CSRF + souvent nonce CSP) : `FormController::*`, `ContactController::contact`,
  `NewsletterController::view`, `SearchController::view`/`results`, `RecruitmentController::index`,
  `CustomizedController::zoneContactUs`.
- Contenu utilisateur / session : `App\Controller\Security\Front\FrontController::*` (espace
  personnel), `CatalogController::cart`, favoris.
- Listes paginées / filtrées (la query string `?page`/`?filters`/`?text` n'est pas dans la clé) :
  `CatalogController::index`/`search`, `NewscastController::index`, `GalleryController::index`,
  `PortfolioController::index`.

**Pourquoi pas tout cacher (whitelist, pas blacklist)** : un bloc n'est cachable que si sa sortie
est identique pour tous les visiteurs anonymes d'une page. Cacher un formulaire fige son **jeton
CSRF** (soumissions cassées) ; cacher un fragment à `csp_nonce()` fige un **nonce** qui ne
correspondra plus à l'en-tête CSP (script bloqué) ; cacher du contenu utilisateur le **divulgue**
à un autre ; cacher une liste paginée sert la mauvaise page. Une whitelist échoue en sécurité : un
futur type de bloc n'est pas caché tant qu'il n'a pas été vérifié. Pour ajouter une action :
vérifier absence de CSRF, de `csp_nonce()`, d'état utilisateur et de dépendance à la query, puis
l'inscrire dans `cacheableFragmentActions` (`templates/front/default/include/zone.html.twig`).

> Note : le garde `not granted('ROLE_ADMIN')` reste indispensable quel que soit l'élargissement -
> les fragments rendent les overlays `WebmasterEdit` dont les liens portent le `security_token`.

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

Restes connus sur la home (non traités, gain faible ou architectural) : les 3 sous-requêtes
`module_slider` (un bloc slider = une sous-requête `render_esi`), les médias du teaser
`module_newscast` (chargés en amont par `findTeaserEntities`/`findOptimized`, l'amorçage
arriverait trop tard) et quelques `block_media_relations` de blocs appartenant aux layouts
des teasers.
