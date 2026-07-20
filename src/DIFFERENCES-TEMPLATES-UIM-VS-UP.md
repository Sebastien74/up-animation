# Différences `templates/` — uninstantmagique vs up-animation

Comparaison des dossiers de templates Twig des deux projets (base CMS commune).

- **UIM** = `C:\wamp64\www\uninstantmagique\templates`
- **UP** = `C:\wamp64\www\up-animation\templates`

Généré le 2026-07-19.

> **Note de méthode** : `diff -rq` remonte ~78 fichiers « différents », mais la
> majorité ne diffèrent que par les **fins de ligne / l'indentation** (CRLF vs LF,
> réindentation). En ignorant ce bruit (`diff -w -B`), il reste **~40 fichiers**
> avec de vraies différences de contenu, listées ci-dessous. Une vingtaine de
> fichiers (surtout `security/front/*`, `template/{build,cms,home,legacy}`,
> `vendor/view`) ne diffèrent **que** par les fins de ligne.

---

## 1. Fichiers uniquement dans **UIM**

| Fichier | Nature |
|---|---|
| `front/default/actions/catalog/view/agencies.html.twig` | Fiche agence standardisée (contenu prod UIM) |
| `front/default/blocks/video/hero.html.twig` | Bloc vidéo « hero » plein écran (overlay logo + reveal) |

## 2. Fichiers uniquement dans **UP**

| Fichier | Nature |
|---|---|
| `admin/page/documentation/content/reco-ia.md` | Doc back-office « Reco IA - AI Overviews / AI Mode » (chantier AI Overviews France, sept. 2026) |

---

## 3. Divergences fonctionnelles réelles

### a) **UP en avance / spécificités UP**

| Fichier | Détail |
|---|---|
| `front/default/actions/catalog/view.html.twig` (+7) | **FAQ produit** : rend `FaqController::view` en ESI si `entity.faq`. Cohérent avec la feature FAQ côté `src/`. |
| `admin/page/catalog/product-edit.html.twig` (+3) | Support du champ FAQ dans l'édition produit. |
| `front/default/include/zone.html.twig` (+42) | **Séparateurs SVG décoratifs** (`wave-top`, `wave-top-inner`, `curve-top`, `wave-shadow-bottom`, `wave-bottom`, `curve-bottom-left`) selon les classes de zone. |
| `front/default/actions/search/view.html.twig` (+6) | Ajout d'un `wave-divider-top` + nom de marque « Up Animations! ». |
| `core/image-loader.html.twig` (+12) | Bloc de construction des `args` (classname/id/loaderFilename, purge des objets) réintroduit ; absent côté UIM. |

### b) **UIM en avance / spécificités UIM**

| Fichier | Détail |
|---|---|
| `front/default/template/error.html.twig` (uim -69 / up +18) | UIM a une **page 404 « magic »** élaborée (éventail de cartes qui se retournent, macro `socials`). UP a une page d'erreur beaucoup plus simple. |
| `front/default/include/macros/card.html.twig` (uim -44 / up +21) | Macro carte UIM plus riche : **lien explicite autour de l'image** (reste cliquable pendant la génération asynchrone de vignette, évite le lien imbriqué via `onlyLink/haveLink`), gestion carte transparente via tokens `$card-border-*` (pas de `border-0`). UP a une version antérieure (`bg-white`, `border-0`). |
| `front/default/actions/menu/lateral.html.twig` (uim -19 / up +11) | UIM : menu **toujours `fixed-top`** + pleine largeur (`alwaysFixed`, `container-fluid`). UP : version sans ce forçage, `bg-white` sur le submenu. |

### c) Nom de marque (attendu)

« Un Instant Magique » (UIM) vs « Up Animations! » (UP) — dans
`search/view.html.twig`, `menu/lateral.html.twig`, etc.

### d) Divergences mineures de contenu

`admin/base.html.twig`, `admin/core/edit.html.twig`,
`admin/core/form/intls.html.twig` (34/34, surtout réindentation),
`admin/core/layout/blocks.html.twig`, `core/address.html.twig`,
`core/email/base*.html.twig` (3 lignes réelles chacun),
`front/default/base.html.twig`, `front/default/include/footer.html.twig`,
`front/default/include/breadcrumb.html.twig`,
`front/default/blocks/title-header/default.html.twig`,
`front/default/actions/slider/template/*`, `front/default/actions/timeline/view.html.twig`,
`front/default/include/{preloader,thanks-modal,locale-switcher}.html.twig`,
`security/base.html.twig`, etc. (1 à quelques lignes).

---

## 4. ⚠️ Point de vigilance détecté dans UP

**`front/default/include/zone.html.twig` ligne 68 — commentaire Twig cassé.**

Côté UIM :
```twig
{# Simplified title position management #}
```
Côté UP, les marqueurs `{# #}` ont disparu :
```twig
{%- if intlZone.title is defined and intlZone.title|striptags|length > 0 -%}
    Simplified title position management        {# <-- texte nu, plus de {# #} #}
    {%- set tp = zone.titlePosition -%}
```
Ce texte n'est plus un commentaire : il est **émis en clair dans le HTML** de
chaque zone qui a un titre. À corriger (remettre `{# ... #}` ou supprimer la ligne).

> Autre écart notable sur ce fichier : `fragmentCacheable` teste
> `app.environment != 'dev'` côté UP, alors que UIM exclut aussi `local`
> (`not in ['dev', 'local']`). UP peut donc mettre en cache des fragments en env
> `local`.

---

## 5. Synthèse

- Même arborescence de templates ; **UIM a 2 fichiers exclusifs** (fiche agence,
  bloc vidéo hero), **UP en a 1** (doc `reco-ia.md`).
- **UP devance** sur la FAQ produit et les séparateurs SVG (identité visuelle
  vagues/courbes propre à up-animation).
- **UIM devance** sur la page 404 « magic », la macro `card` (robustesse liens +
  vignettes asynchrones) et le menu latéral `fixed-top`.
- ~20 fichiers ne diffèrent que par les **fins de ligne** (aucun impact fonctionnel).
- **1 bug à corriger dans UP** : commentaire Twig nu dans `zone.html.twig` (texte
  parasite rendu).

### Portages suggérés vers UP

1. **Corriger** `zone.html.twig` (commentaire nu ligne 68) — bug visible.
2. `macros/card.html.twig` — reprendre la gestion du lien explicite autour de
   l'image (vignettes asynchrones) et des cartes transparentes de UIM.
3. Éventuellement la page 404 « magic » de UIM si l'effet est souhaité côté UP.
