# Performance - Fonds en asset statique (loader async)

Suppression du blocage de **~17 s à froid** sur la page de **connexion back-office**, sans
changer le rendu. La page passe de ~17,9 s à **~1,2 s** quand les vignettes du fond ne sont
pas encore générées.

> Périmètre : les **fonds rendus depuis un asset statique** passés à `|file` (cas du
> `background-security` de la page login). Les fonds de **zone/bloc** (MediaModel entité) et
> la page maintenance (`background: url()` inline) ne sont pas concernés, ils étaient déjà
> efficaces.

---

## Cause racine

Le filtre Twig `|file` (`ThumbnailRuntime`) a deux chemins :

- **MediaModel entité** : consulte le cache `thumbnails/generated/*.cache.json`, rend un
  placeholder léger, puis **diffère la génération** des vignettes à une sous-requête async
  (`FrontController::mediaLoader` via `<hx:include>`). Le rendu principal ne génère jamais.
- **Asset string** (`asset('build/...jpg')|file(...)`) : partait dans `fileRender() → thumb()`
  qui **génère toutes les variantes en synchrone, dans la requête** (le média synthétique n'a
  pas d'`id`, donc `ImageThumbnail::execute()` force la génération), **sans** passer par le
  cache.json ni le loader.

Conséquence : à chaque fois que les vignettes disque manquaient (déploiement, rotation
`app:cache:reclaim`, purge), la page login régénérait **~26 variantes** (≈ 13 tailles x
jpg + webp) inline, soit ~17 s de blocage.

---

## Correctif : étendre le loader async au cas « string »

Le mécanisme entité a été rendu disponible pour un asset statique, **sans entité**, via un
flag opt-in `pathLoader: true`.

### 1. `ThumbnailRuntime::pathLoaderRender()`

Nouveau chemin emprunté quand `|file` reçoit une **string** raster avec `pathLoader: true`.
Il rend `core/image-loader.html.twig` avec un placeholder immédiat (le fond brut en
`background-image` + SVG vide) et un `<hx:include>`, **sans appeler `execute()`** : zéro
génération dans la requête principale. La génération est déléguée à la sous-requête async.

### 2. `templates/core/image-loader.html.twig`

Branche `hx:include` **basée chemin** (`pathSource`) en plus de la branche entité existante :
elle transmet `src` / `width` / `height` / `thumbConfigurationJson` au lieu de
`classname` / `id`.

### 3. `FrontController::mediaLoader` (mode chemin)

Quand l'appel async porte un `src` (et pas d'entité), le contrôleur régénère depuis le path
(`$runtime->file('/'.$src, ..., ['forceThumb' => true, 'only_arguments' => true])`) et renvoie
le `<picture>` final (`core/image-config.html.twig`), que le JS `medias-loader` injecte à la
place du placeholder.

### 4. CSS

`#bg-container` (security) fait remplir la chaîne loader (`.img-loader-wrap`, `.inner`,
`picture`) pour couvrir le conteneur `position: fixed`.

---

## Sécurité (path traversal)

Le mode chemin de `mediaLoader` reçoit `src` depuis la requête. Une **double garde** rejette
tout chemin hors `public/` :

- `FrontController::mediaLoader` (frontière de confiance) : `400` si `src` contient `\0`, un
  segment `..`, ou ne résout pas (`realpath`) sous `public/`.
- `ThumbnailRuntime::pathLoaderRender` (défense en profondeur) : même contrôle, `return false`.

La route `_fragment` est par ailleurs signée HMAC par Symfony, mais la validation explicite
reste nécessaire (le contrôleur est la frontière ; la signature peut être désactivée).

---

## Utilisation

Pour un **fond plein écran rendu depuis un asset statique**, ajouter `pathLoader: true` :

```twig
{{ asset('build/security/images/background-security.jpg', 'security')|file({}, {
    maxWidth: 1520,
    maxHeight: 919,
    priority: 'high',
    pathLoader: true
}) }}
```

- Ne s'applique qu'aux **images raster** (jpg/png/webp). Un SVG/icône retombe sur le rendu
  direct (`fileRender`), inchangé.
- Le fond reste **visible immédiatement** (placeholder = l'asset brut en CSS), puis est
  remplacé par le `<picture>` webp optimisé. Dégradation gracieuse si l'async échoue.
- Réservé aux **fonds** : pour une image de contenu entité, le chemin MediaModel fait déjà
  ce travail sans flag.

---

## Mesurer

- Temps de réponse : `curl -sk -o /dev/null -w "%{time_total}s" <url-login>`. Purger d'abord
  `public/thumbnails/*background-security*` pour simuler le froid.
- Vérifier que la requête principale **ne génère plus** : le nombre de fichiers
  `public/thumbnails/...background-security...` doit rester stable après l'affichage de la page
  (la génération a lieu dans la sous-requête `mediaLoader`, pas dans la page).
