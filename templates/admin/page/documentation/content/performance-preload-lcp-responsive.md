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

---

## Résultat mesuré après déploiement (2026-08-20)

Le correctif est en ligne et vérifié sur la page canonique : **un seul** preload
`as="image"` avec `imagesrcset`, le preload `media1` inutilisé a disparu, et le hero ne
télécharge plus qu'**une** variante (49,0 Ko contre 89,3 Ko en trois requêtes). Le TBT est
passé de 100 ms à 10-50 ms.

En revanche le **score n'a pas bougé grâce à ce correctif seul**, et c'est l'enseignement
principal de la campagne de mesure.

### Le score mobile est limité par la bande passante, pas par le preload

Sur une mesure stabilisée, les phases du LCP somment à **1 012 ms** (TTFB 16 +
`resourceLoadDelay` 620 + `resourceLoadDuration` 70 + `elementRenderDelay` 306) alors que la
métrique LCP annoncée est de **5,4 s**. L'écart n'est pas une incohérence : les phases
proviennent de la trace **réelle** (le hero peint en ~1 s), la métrique est la **simulation**
4G de Lantern. À ~1,6 Mbps, les 1 356 Ko de la page valent à eux seuls ~6,8 s de
téléchargement.

Conséquence pratique : **tant que le poids total reste élevé, optimiser le preload ne peut
pas déplacer le score.** Deux corollaires observés :

- dans le graphe Lantern, une sous-ressource ne démarre jamais avant la **fin du document** :
  le hero part systématiquement ~48 ms après lui. Un preload ne rattrape pas ce verrou ;
- la durée modélisée du document est très instable d'un run à l'autre (589 ms à 2 192 ms pour
  une taille identique de ~29 Ko compressés), ce qui suffit à faire varier le score d'une
  dizaine de points. **Ne jamais conclure sur un seul run.**

### Le vrai levier : les placeholders `generating`

Quand les vignettes d'un média ne sont pas encore générées, `image-loader.html.twig` rend un
placeholder dont le `background-image` pointe sur l'**original pleine résolution**. Sur la
home, cela représentait **912 Ko sur 1 356 Ko, soit 67 % du poids de la page** (deux JPEG
1920x1080 et un `share.jpg`).

Mesure comparative de la même page, à code identique :

| Version | Score | LCP | Poids | Originaux |
| --- | --- | --- | --- | --- |
| Placeholders `generating` | 79 | 5,4 s | 1 356 Ko | 912 Ko |
| Vignettes générées | **90-92** | **2,7-2,9 s** | **431 Ko** | 0 |

### Procédure de remise en état

1. Générer les vignettes manquantes. L'étape « warm » du déploiement ne suffit pas : elle
   récupère les URLs `/thumbnails/` présentes dans le HTML, mais **pas** les fragments
   `_fragment ... mediaLoader` qui déclenchent la génération. Les appeler explicitement
   (ils sont signés par `_hash`, donc valides tels quels dans le HTML) ou utiliser
   `app:thumbs:generate`.
2. **Invalider le cache du site** (bouton du dashboard admin, qui bompe `cacheClearDate`).
   Sans cette étape, l'URL canonique continue de servir le rendu en cache avec les
   placeholders alors que `?x=RANDOM` rend déjà la version correcte - le symptôme classique
   « canonique vieux / query param neuf ».

### Reste à faire

- `unused-css-rules` : ~48 Ko de CSS non utilisé sur la home.
- 8 fichiers de police pour ~218 Ko, dont 4 préchargés en priorité haute. Attention :
  `font-display: optional` est en place, retirer un preload peut faire rendre le texte en
  police de repli.

---

## État final confirmé (2026-08-20, après invalidation du cache du site)

Vignettes générées + cache du site invalidé : la page canonique sert le rendu propre
(0 bloc `generating`, 0 original pleine résolution) avec le preload corrigé.

Quatre mesures PSI mobile consécutives : **91, 92, 92, 92** (médiane 92). La dispersion est
retombée à 1 point, contre 76-89 lors des mesures prises juste après déploiement, cache froid
et génération de vignettes en cours - ce qui confirme qu'il faut mesurer sur un site au repos.

| Indicateur | Avant | Après |
| --- | --- | --- |
| Score mobile | 86-89 | **91-92** |
| Score desktop | - | **97** |
| LCP | 3,6 s | **2,9 s** |
| TBT | 95 ms | **52 ms** (score plein) |
| CLS | 0 | 0 |
| Poids page | 1 385 Ko | **431 Ko** |
| dont images | 1 011 Ko | **59 Ko** |
| Requêtes | 64 | 57 |
| Requêtes hero | 3 (89 Ko) | **1 (49 Ko)** |

Seul indicateur en retrait : le **FCP** (1,2 s -> 1,9 s). Ce n'est pas une régression de
rendu : à taille de document quasi identique (28,8 Ko contre 28,5 Ko compressés), la durée
modélisée du document est passée de 1 513 ms à 1 987 ms. C'est la variance Lantern décrite
plus haut, pas un effet du code.

Leviers restants, par ordre d'intérêt : `unused-css-rules` (~48 Ko), `render-blocking`, puis
les 218 Ko de polices (avec la réserve `font-display: optional`).
