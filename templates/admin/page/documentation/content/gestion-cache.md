# Gestion du cache (back-office)

Outils d'invalidation et de vidage du cache exposés dans le back, et rappel des
**couches de cache** de l'application. À lire avant tout dev qui modifie une donnée
rendue derrière du cache.

> Règle transverse (cf. `CLAUDE.md`) : se demander systématiquement **quels caches
> mettre à jour** quand une donnée change, et réutiliser les services existants plutôt
> que de réinventer une invalidation.

---

## Les couches de cache

| Couche | Clé / emplacement | Invalidée par |
|---|---|---|
| Fragments Twig `{% cache %}` (blocs `core-action`) | `frag-{action}-{block.id}-{locale}-{url.id}-{cacheClearDate.ts}-{block.updatedAt.ts}` | `cacheClearDate` (global) **ou** `block.updatedAt` (par bloc) |
| Result-cache page/action (Doctrine result cache) | `page-*`, `pages_action_*`, `layout_*`... | `CacheInvalidationSubscriber` (postFlush) |
| Pool `cache.app` + pools Symfony | `var/cache/<env>/pools/...` | page « Pools de cache » / `CachePoolManager` |
| Caches applicatifs disque | `var/cache/<env>/*.cache.json` (BaseModel) | `cache:clear` |
| Médias Liip | `public/medias/webp` | `cache_liip_clear` |
| Traductions, routes JS, OPcache | BDD + `var/cache`, `fos:js-routing:dump` | commandes dédiées |

---

## 1. Vider le cache du site (dashboard)

Bouton **« Vider le cache »** dans le hero du dashboard (à côté de la date).

- Route `admin_website_cache_invalidate` (POST + CSRF, `ROLE_ADMIN`).
- Service `WebsiteCacheInvalidator` : bump de `website.cacheClearDate` (timezone
  `Europe/Paris`) **+** `cache.app->clear()`. Le bump versionne instantanément la clé des
  fragments `{% cache %}` et invalide le result-cache (adossé à `cache.app`).
- Usage : forcer la régénération du rendu public du **site courant** après un changement
  qui ne déclenche pas l'invalidation automatique.

## 2. Pools de cache (liste + vidage)

Page **« Pools de cache »** : tuile « Accès rapides » du dashboard + lien sidebar droite
(groupe Cache). `ROLE_INTERNAL`.

- Route `cache_pools_index` (liste) et `cache_pool_clear` (POST + CSRF, par pool ou
  `__all__`). Redirige vers le `referer` (utilisable depuis le dashboard).
- Service `CachePoolManager` : liste canonique des pools (miroir de `cache:pool:list`) +
  taille disque agrégée du dossier `var/cache/<env>`. Le vidage délègue à
  `CacheCommand::clearPool()` / `clearAllPools()`, exécutés **in-process**
  (`Application->run()`, pas de `shell_exec` : compatible mutualisé).
- Contrainte : les `FilesystemAdapter` n'exposent pas leurs clés ni un mapping
  nom->répertoire fiable (sous-dossiers hashés) -> granularité au **niveau pool**.

## 3. Invalider le cache d'une fiche (édition)

Bouton **« Invalider le cache »** dans la vue d'édition **et** la vue
« Mise en page » des entités à layout (Page, Product, Newscast...), bloc `#layout-grid`,
visible si `entity.layout` a au moins une zone. Markup factorisé dans le partiel
`templates/admin/core/layout/cache-invalidate-button.html.twig`, inclus par
`edit.html.twig` et `layout.html.twig`.

- Route `admin_entity_cache_invalidate` (POST + CSRF, `ROLE_ADMIN`). Garde stricte :
  `hasMetadataFor` (entité Doctrine connue) + `denyUnlessEntityWebsite` (site courant) +
  `EntityCacheInvalidator::supports()` (layout + zones).
- Service `EntityCacheInvalidator` : bump `updatedAt` de **tous les blocs** du layout de
  l'entité, puis `flush`. Combiné au segment `block.updatedAt` de la clé fragment, seuls les
  fragments de cette entité sont régénérés ; le flush en contexte admin laisse
  `CacheInvalidationSubscriber` purger le result-cache page/action, comme une édition.

> **Clé fragment enrichie** : `block.updatedAt` a été ajouté à la clé `{% cache %}`
> (`templates/front/default/include/zone.html.twig`). Changement à portée site (un
> cache-miss unique au prochain rendu). `cacheClearDate` reste dans la clé : le bouton
> dashboard (point 1) continue d'invalider **tout** le site, ce bouton n'invalide **qu'une**
> entité.

---

## Pour aller plus loin (per-item / per-groupe)

Si un jour on veut invalider par **groupe logique** plutôt que par pool : passer `cache.app`
en `TagAwareAdapter` et tagger les groupes (`fragments`, `page`, `menu`...), ou maintenir un
registre des préfixes déjà utilisés par `CacheInvalidationSubscriber` (`frag-*`,
`page-index-*`, `menu_links_*`, `products_in_menus_*`, `pages_action_*`). Non implémenté à
ce jour (le niveau pool suffit).
