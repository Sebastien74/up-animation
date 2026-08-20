# Performance - Preload responsive de l'image LCP

Correction d'un défaut du préchargement de l'image above-the-fold (élément LCP) sur le
front : le `<link rel="preload">` désignait une variante que le `<picture>` rendu
n'utilisait **jamais**. Le preload ne servait donc pas le LCP et consommait de la bande
passante en priorité haute devant lui.

---

## Symptôme mesuré

Mesure PageSpeed Insights mobile sur la home de preprod (`www.up.abcvd.com`) :

| Métrique | Valeur | Score |
| --- | --- | --- |
| Performance | 89 | - |
| FCP | 1,2 s | 0,99 |
| TBT | 100 ms | 0,98 |
| CLS | 0 | 1,00 |
| **LCP** | **3,6 s** | **0,61** |

Le LCP était le seul frein réel. Sa décomposition attribuait **1 526 ms sur 1 907 ms** au
`resourceLoadDelay` : le navigateur découvrait bien l'image (`requestDiscoverable`,
`priorityHinted`, `eagerlyLoaded` tous à `true`) mais ne lançait sa requête utile que très
tard.

Le waterfall montrait **trois** variantes du même visuel hero téléchargées :

- `thumbnails/media1/rc/.../rounded-events.png.webp` - 5,9 Ko, **référencée nulle part**
  dans le HTML (une seule occurrence : le `<link rel="preload">` lui-même)
- `thumbnails/media80/rc/NWhLbOPQ/...` (480w) - 34,1 Ko, chargée par le preload
- `thumbnails/media80/rc/oaTiaK3U/...` (768w) - 49,3 Ko, **la vraie ressource LCP**

Soit environ **40 Ko inutiles en priorité haute**, servis avant l'image réellement affichée.

---

## Cause racine

`ThumbService::doPreload()` émettait **un preload par breakpoint avec un `href` figé**, plus
un preload du `lazyFile` (la variante de très basse qualité, clé `0`) sans attribut `media`
donc systématiquement chargée.

Or le `<picture>` rendu par `templates/core/image.html.twig` sélectionne sa variante via
`srcset` + `sizes`, donc **en fonction de la largeur de viewport et du DPR**. Un `href` fixe
ignore cette résolution : les deux mécanismes ne pouvaient pas tomber d'accord.

Sur l'appareil émulé par PSI (412 px, DPR 1,75), `sizes="100vw"` demande 721 px : le
`<picture>` choisit le candidat 768w, tandis que le preload imposait le 480w. La divergence
existait en réalité sur **tous** les appareils testés :

| Appareil | `<picture>` | Preload avant | Preload après |
| --- | --- | --- | --- |
| Moto G Power (PSI) | 768w | 480w | 768w |
| iPhone 14/15 | 1200w | 480w | 1200w |
| Pixel 7 | 1200w | 480w | 1200w |
| Galaxy S22 | 1200w | 480w | 1200w |
| iPad mini | 1536w | 480w | 1536w |

À noter : `ImageThumbnail::preload()` (utilisé pour le logo) implémentait **déjà** le bon
pattern. Seul le chemin `ViewModel::preload()` -> `ThumbService::doPreload()`, qui traite
justement l'image above-the-fold, n'avait pas été migré.

---

## Correctif

`ThumbService::doPreload()` émet désormais **un seul** preload responsive, aligné sur le
`<picture>` :

- `imagesrcset` reprend **le même jeu de candidats** que le `srcset` rendu (toutes les
  tailles de `thumbs`, dédoublonnées par fichier), et `imagesizes` la même valeur `sizes` ;
  le navigateur applique alors sa propre résolution et retombe forcément sur la variante
  affichée
- le preload du `lazyFile` (clé `0`) est supprimé : il n'était référencé par aucun élément
- `templates/front/default/base.html.twig` rend un `<link>` unique avec
  `imagesrcset` / `imagesizes` (le `Link:` HTTP couvre Chromium, la balise couvre Firefox et
  Safari qui n'honorent pas le header)

### Piège à connaître

Le jeu de candidats du preload doit inclure les **tailles retina**
(`RETINA_SIZES = [960, 1536, 1984, 2400]`). Le filtre historique les excluait : légitime
avec un `href` figé, mais avec `imagesrcset` cela recrée un décalage (sur iPad mini, le
`<picture>` prend 1536w et un preload sans retina prendrait 1920w, soit un double
téléchargement).

Règle générale : **le jeu de candidats et le `sizes` du preload doivent être identiques à
ceux du `<picture>`**, sinon le preload devient une requête perdue.

---

## Limite connue (non corrigée)

`doPreload()` appelle `ImageThumbnail::execute()` **sans `options`**, donc sans `colSize` :
`imagesizes` vaut `100vw`. C'est exact pour les breakpoints mobile et tablette
(`image.html.twig` y code `sizes: '100vw'` en dur), mais approximatif sur laptop/desktop où
le `<picture>` utilise `sizesDesktop` calculé depuis `colSize` (par exemple `41.67vw` pour un
hero en `col-lg-5`). Le comportement n'est pas dégradé par rapport à l'existant, qui posait
déjà une hypothèse pleine largeur, mais un preload desktop parfaitement aligné demanderait de
propager `colSize` jusqu'à ce chemin.
