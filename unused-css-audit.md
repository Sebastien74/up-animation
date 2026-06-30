# Audit CSS/JS non utilisé

Origin : https://www.up.abcvd.com · pages crawlées : 40 · viewports : mobile 412 + desktop 1366

> ⚠️ **Couverture au chargement de page uniquement.** Les classes posées par JS (`show`, `active`, `collapsing`, `modal-open`, validation de formulaire), les états `:hover`/`:focus`, les styles `print`, et tout ce qui vit sur une page ou un état non visité par le crawl apparaissent ici comme « non utilisés » alors qu'ils sont nécessaires. **Liste de CANDIDATS à revoir, jamais à supprimer en l'état.**
>
> Les classes sont scindées en **Custom** (classes propres au projet jamais touchées = vrai code mort probable, à prioriser) et **Bootstrap** (utilitaires/composants non touchés = surtout des faux positifs liés aux viewports/états non crawlés).

## Par feuille de style

| Fichier | Total | Utilisé | % mort | Custom morts | BS morts |
|---|--:|--:|--:|--:|--:|
| front-default-light.95b103a1.css | 376 Ko | 70 Ko | 81% | 1023 | 787 |
| front-default-light-desktop.95b103a1.css | 123 Ko | 9 Ko | 93% | 242 | 637 |
| 4774.11f24f1e.css | 35 Ko | 2 Ko | 94% | 22 | 10 |
| 7675.fd61c537.css | 11 Ko | 0 Ko | 100% | 20 | 1 |
| 5186.73b4e0d0.css | 10 Ko | 2 Ko | 81% | 22 | 29 |
| front-default-security-light.6358fb4b.css | 18 Ko | 11 Ko | 38% | 1 | 1 |
| front-default-fonts.b2db18e3.css | 8 Ko | 1 Ko | 82% | 78 | 0 |
| 3285.36c3d020.css | 5 Ko | 1 Ko | 69% | 16 | 0 |
| 3489.e4d8abba.css | 4 Ko | 1 Ko | 65% | 12 | 19 |
| debug.cd42db02.css | 1 Ko | 0 Ko | 100% | 19 | 0 |
| front-default-cms-light.4c2920c6.css | 1 Ko | 1 Ko | 36% | 0 | 0 |
| front-default-catalog-light.e8495e0b.css | 1 Ko | 1 Ko | 28% | 2 | 0 |
| front-default-home-light.16a4d5b7.css | 1 Ko | 0 Ko | 33% | 0 | 0 |
| front-default-newscast-light.3564c3c6.css | 0 Ko | 0 Ko | 39% | 0 | 0 |

## Exclusions CMS appliquées

> Les classes et id **générés dynamiquement par le page-builder** (zones / colonnes / blocs) ont été retirés de la liste ci-dessous : ils dépendent de choix d'administration (ombre, couleur, espacement, ordre, alignement, visibilité, police, classes personnalisées) et ne peuvent pas être couverts par un crawl. Ils ne sont **jamais** supprimables. **1948 classes** retirées sur ce critère.

Sources de génération : `src/Twig/Content/LayoutRuntime.php`, `src/Form/Widget/ShadowType.php`, `src/Form/Type/Layout/Management/{MarginType,ScreensType}.php`, `assets/scss/front/default/layout/_layout.scss`, `assets/scss/front/default/utilities/_mixin-margin*.scss`.

| Règle | Classes retirées | Motif |
|---|--:|---|
| `zone-prefix` | 162 | Classes prefixees `zone-` (custom + ombres + rendu) emises par LayoutRuntime |
| `block-prefix` | 169 | Classes prefixees `block-` (custom + ombres + rendu + svg-icon) emises par LayoutRuntime |
| `col-grid` | 75 | Tailles de colonne (grille) choisies par ecran (ScreensType) |
| `col-shadow` | 180 | Ombres de colonne (ShadowType) |
| `col-render` | 2 | Modes de rendu de colonne (page-builder) |
| `order` | 199 | Ordre des elements par ecran (ScreensType) |
| `spacing` | 814 | Marges/paddings par ecran, variantes -neg/-mobile/-tablet/-laptop (MarginType) |
| `gutter` | 39 | Gouttieres de rangee (grille page-builder) |
| `typography` | 11 | Police par bloc: taille/graisse/famille (styleClass) |
| `z-index` | 10 | z-index par bloc (styleClass) |
| `display` | 29 | Affichage/visibilite responsive par element (getHiddenClasses) |
| `flex` | 67 | Utilitaires flex emis par la mise en page builder |
| `align` | 54 | Alignement flex (align-items/self/content) emis par le builder |
| `justify` | 38 | Justification flex (justify-content) emise par le builder |
| `as` | 25 | Helpers de mise en page colonne (as-fluid-right, LayoutRuntime) |
| `text-align` | 20 | Alignement de texte par ecran (getAlignments) |
| `text-color` | 21 | Couleur de texte (theme) choisie par bloc |
| `bg-color` | 25 | Couleur de fond (theme) choisie par element |
| `hash-id` | 8 | Identifiants/ancres hashes generes dynamiquement |

Familles entièrement retirées (100% CMS) : `order-*` (199), `block-*` (169), `zone-*` (162), `ms-*` (99), `me-*` (92), `mt-*` (84), `mb-*` (81), `flex-*` (67), `pe-*` (55), `align-*` (54), `ps-*` (53), `pt-*` (53), `pb-*` (50), `m-*` (48), `mx-*` (46), `my-*` (44), `p-*` (39), `justify-*` (38), `py-*` (36), `px-*` (34), `d-*` (29), `as-*` (25), `g-*` (14), `gx-*` (13), `gy-*` (12), `z-*` (10), `fz-*` (5), `ff-*` (4), `fw-*` (2).

Couleurs de thème détectées (pour `text-*` / `bg-*`) : `beige`, `black`, `danger`, `dark`, `dark-lighten`, `info`, `info-dark`, `info-light`, `info-lighten`, `light`, `lighten`, `muted`, `primary`, `primary-darken`, `secondary`, `secondary-light`, `secondary-lighten`, `success`, `warning`, `white`.
## Classes inutilisées, par famille (824 au total)

_Toutes les classes jamais touchées, dédoublonnées sur l'ensemble des feuilles. `_(custom)_` = classe propre au projet (à prioriser) ; les autres sont des utilitaires/composants Bootstrap._

<details><summary><code>col-*</code> — 3 · 3 custom</summary>

- `col-form-label` _(custom)_
- `col-form-label-lg` _(custom)_
- `col-form-label-sm` _(custom)_

</details>

<details><summary><code>(sans préfixe)</code> — 97 · 80 custom</summary>

- `accordion`
- `active`
- `alert`
- `animation` _(custom)_
- `around` _(custom)_
- `arrows` _(custom)_
- `baseline` _(custom)_
- `beige` _(custom)_
- `bi` _(custom)_
- `black` _(custom)_
- `body` _(custom)_
- `bottom` _(custom)_
- `btn`
- `carousel`
- `center` _(custom)_
- `choices` _(custom)_
- `choices__button` _(custom)_
- `choices__heading` _(custom)_
- `choices__inner` _(custom)_
- `choices__input` _(custom)_
- `choices__item` _(custom)_
- `choices__list` _(custom)_
- `choices__placeholder` _(custom)_
- `city` _(custom)_
- `clear` _(custom)_
- `collapsed`
- `collapsing`
- `com` _(custom)_
- `content` _(custom)_
- `controls` _(custom)_
- `custom` _(custom)_
- `danger` _(custom)_
- `dark` _(custom)_
- `disabled`
- `editor` _(custom)_
- `eot` _(custom)_
- `feature` _(custom)_
- `features` _(custom)_
- `file` _(custom)_
- `flip` _(custom)_
- `generated` _(custom)_
- `h4` _(custom)_
- `h5` _(custom)_
- `height` _(custom)_
- `hover` _(custom)_
- `info` _(custom)_
- `initial` _(custom)_
- `inner` _(custom)_
- `left` _(custom)_
- `light` _(custom)_
- `lighten` _(custom)_
- `link` _(custom)_
- `loader` _(custom)_
- `loading` _(custom)_
- `logo` _(custom)_
- `mark`
- `muted` _(custom)_
- `novideo` _(custom)_
- `offcanvas`
- `open` _(custom)_
- `parallax` _(custom)_
- `portrait` _(custom)_
- `preview` _(custom)_
- `primary` _(custom)_
- `progress`
- `right` _(custom)_
- `rotate` _(custom)_
- `secondary` _(custom)_
- `show`
- `showing`
- `side` _(custom)_
- `small`
- `spin` _(custom)_
- `splide__arrows` _(custom)_
- `splide__container` _(custom)_
- `splide__pagination` _(custom)_
- `splide__pagination__page` _(custom)_
- `splide__progress__bar` _(custom)_
- `splide__spinner` _(custom)_
- `splide__sr` _(custom)_
- `splide__toggle` _(custom)_
- `splide__toggle__pause` _(custom)_
- `splide__toggle__play` _(custom)_
- `sublist` _(custom)_
- `success` _(custom)_
- `svg` _(custom)_
- `table`
- `text` _(custom)_
- `toast`
- `tooltip`
- `top` _(custom)_
- `ttf` _(custom)_
- `warning` _(custom)_
- `white` _(custom)_
- `width` _(custom)_
- `woff` _(custom)_
- `woff2` _(custom)_

</details>

<details><summary><code>text-*</code> — 37</summary>

- `text-black-50`
- `text-body`
- `text-body-emphasis`
- `text-body-secondary`
- `text-body-tertiary`
- `text-ellipsis`
- `text-hover-beige`
- `text-hover-black`
- `text-hover-danger`
- `text-hover-dark`
- `text-hover-dark-lighten`
- `text-hover-info`
- `text-hover-info-dark`
- `text-hover-info-light`
- `text-hover-info-lighten`
- `text-hover-light`
- `text-hover-lighten`
- `text-hover-muted`
- `text-hover-primary-darken`
- `text-hover-secondary`
- `text-hover-secondary-light`
- `text-hover-secondary-lighten`
- `text-hover-success`
- `text-hover-warning`
- `text-hover-white`
- `text-left`
- `text-normal`
- `text-opacity-100`
- `text-opacity-25`
- `text-opacity-50`
- `text-opacity-75`
- `text-reset`
- `text-right`
- `text-small`
- `text-vertical`
- `text-white-50`
- `text-xs-justify`

</details>

<details><summary><code>icon-*</code> — 67 · 67 custom</summary>

- `icon-arrow-circle-down` _(custom)_
- `icon-arrow-circle-left` _(custom)_
- `icon-arrow-circle-right` _(custom)_
- `icon-arrow-circle-up` _(custom)_
- `icon-arrow-down` _(custom)_
- `icon-arrow-up` _(custom)_
- `icon-at` _(custom)_
- `icon-badge-check` _(custom)_
- `icon-ban` _(custom)_
- `icon-block` _(custom)_
- `icon-bullseye-pointer` _(custom)_
- `icon-calendar-alt` _(custom)_
- `icon-capsules` _(custom)_
- `icon-check-circle` _(custom)_
- `icon-check-solid` _(custom)_
- `icon-chevron-circle-down` _(custom)_
- `icon-chevron-circle-left` _(custom)_
- `icon-chevron-circle-right` _(custom)_
- `icon-chevron-circle-up` _(custom)_
- `icon-chevron-down` _(custom)_
- `icon-clock` _(custom)_
- `icon-copyright` _(custom)_
- `icon-desktop-alt` _(custom)_
- `icon-directions` _(custom)_
- `icon-download` _(custom)_
- `icon-edit` _(custom)_
- `icon-exclamation` _(custom)_
- `icon-exclamation-triangle` _(custom)_
- `icon-expand-arrows-alt` _(custom)_
- `icon-external-link-alt` _(custom)_
- `icon-face-grin` _(custom)_
- `icon-feather` _(custom)_
- `icon-file-alt` _(custom)_
- `icon-file-excel` _(custom)_
- `icon-file-word` _(custom)_
- `icon-link` _(custom)_
- `icon-link-hover` _(custom)_
- `icon-linkedin` _(custom)_
- `icon-lock-alt` _(custom)_
- `icon-mailbox` _(custom)_
- `icon-moon` _(custom)_
- `icon-phone` _(custom)_
- `icon-phone-alt` _(custom)_
- `icon-phone-rounded` _(custom)_
- `icon-pinterest` _(custom)_
- `icon-power-off` _(custom)_
- `icon-print` _(custom)_
- `icon-save` _(custom)_
- `icon-search-plus` _(custom)_
- `icon-shield-alt` _(custom)_
- `icon-sign-in` _(custom)_
- `icon-sun` _(custom)_
- `icon-teamspeak` _(custom)_
- `icon-thumbs-up` _(custom)_
- `icon-tiktok` _(custom)_
- `icon-times-circle` _(custom)_
- `icon-trash-alt` _(custom)_
- `icon-triangle` _(custom)_
- `icon-tripadvisor` _(custom)_
- `icon-twitter` _(custom)_
- `icon-undo-alt` _(custom)_
- `icon-unlink` _(custom)_
- `icon-upload` _(custom)_
- `icon-user-edit` _(custom)_
- `icon-video` _(custom)_
- `icon-volume-slash` _(custom)_
- `icon-volume-up` _(custom)_

</details>

<details><summary><code>btn-*</code> — 59</summary>

- `btn-beige`
- `btn-black`
- `btn-blur-black`
- `btn-blur-danger`
- `btn-blur-danger-light`
- `btn-blur-dark`
- `btn-blur-dark-lighten`
- `btn-blur-info`
- `btn-blur-light`
- `btn-blur-primary`
- `btn-blur-primary-darken`
- `btn-blur-secondary`
- `btn-blur-success`
- `btn-blur-warning`
- `btn-blur-white`
- `btn-close`
- `btn-close-white`
- `btn-danger`
- `btn-danger-light`
- `btn-dark`
- `btn-dark-lighten`
- `btn-gradient`
- `btn-info`
- `btn-info-dark`
- `btn-info-light`
- `btn-info-lighten`
- `btn-lg`
- `btn-light`
- `btn-lighten`
- `btn-muted`
- `btn-outline-beige`
- `btn-outline-black`
- `btn-outline-danger`
- `btn-outline-danger-light`
- `btn-outline-dark`
- `btn-outline-dark-lighten`
- `btn-outline-info`
- `btn-outline-info-dark`
- `btn-outline-info-light`
- `btn-outline-info-lighten`
- `btn-outline-light`
- `btn-outline-lighten`
- `btn-outline-muted`
- `btn-outline-primary`
- `btn-outline-primary-darken`
- `btn-outline-secondary`
- `btn-outline-secondary-light`
- `btn-outline-secondary-lighten`
- `btn-outline-success`
- `btn-outline-warning`
- `btn-outline-white`
- `btn-primary`
- `btn-primary-darken`
- `btn-secondary-light`
- `btn-secondary-lighten`
- `btn-sm`
- `btn-success`
- `btn-warning`
- `btn-white`

</details>

<details><summary><code>link-*</code> — 49</summary>

- `link-offset-1`
- `link-offset-1-hover`
- `link-offset-2`
- `link-offset-2-hover`
- `link-offset-3`
- `link-offset-3-hover`
- `link-opacity-10`
- `link-opacity-10-hover`
- `link-opacity-100`
- `link-opacity-100-hover`
- `link-opacity-25`
- `link-opacity-25-hover`
- `link-opacity-50`
- `link-opacity-50-hover`
- `link-opacity-75`
- `link-opacity-75-hover`
- `link-underline`
- `link-underline-beige`
- `link-underline-black`
- `link-underline-danger`
- `link-underline-dark`
- `link-underline-dark-lighten`
- `link-underline-info`
- `link-underline-info-dark`
- `link-underline-info-light`
- `link-underline-info-lighten`
- `link-underline-light`
- `link-underline-lighten`
- `link-underline-muted`
- `link-underline-opacity-0`
- `link-underline-opacity-0-hover`
- `link-underline-opacity-10`
- `link-underline-opacity-10-hover`
- `link-underline-opacity-100`
- `link-underline-opacity-100-hover`
- `link-underline-opacity-25`
- `link-underline-opacity-25-hover`
- `link-underline-opacity-50`
- `link-underline-opacity-50-hover`
- `link-underline-opacity-75`
- `link-underline-opacity-75-hover`
- `link-underline-primary`
- `link-underline-primary-darken`
- `link-underline-secondary`
- `link-underline-secondary-light`
- `link-underline-secondary-lighten`
- `link-underline-success`
- `link-underline-warning`
- `link-underline-white`

</details>

<details><summary><code>gap-*</code> — 37</summary>

- `gap-0`
- `gap-5`
- `gap-lg-0`
- `gap-lg-1`
- `gap-lg-2`
- `gap-lg-3`
- `gap-lg-4`
- `gap-lg-5`
- `gap-md-0`
- `gap-md-1`
- `gap-md-3`
- `gap-md-4`
- `gap-md-5`
- `gap-sm-0`
- `gap-sm-1`
- `gap-sm-2`
- `gap-sm-3`
- `gap-sm-4`
- `gap-sm-5`
- `gap-xl-0`
- `gap-xl-1`
- `gap-xl-2`
- `gap-xl-3`
- `gap-xl-4`
- `gap-xl-5`
- `gap-xxl-0`
- `gap-xxl-1`
- `gap-xxl-2`
- `gap-xxl-3`
- `gap-xxl-4`
- `gap-xxl-5`
- `gap-xxxl-0`
- `gap-xxxl-1`
- `gap-xxxl-2`
- `gap-xxxl-3`
- `gap-xxxl-4`
- `gap-xxxl-5`

</details>

<details><summary><code>border-*</code> — 33</summary>

- `border-beige`
- `border-black`
- `border-danger`
- `border-danger-subtle`
- `border-dark`
- `border-dark-lighten`
- `border-dark-subtle`
- `border-info`
- `border-info-dark`
- `border-info-light`
- `border-info-lighten`
- `border-info-subtle`
- `border-light`
- `border-light-subtle`
- `border-lighten`
- `border-muted`
- `border-opacity-10`
- `border-opacity-100`
- `border-opacity-25`
- `border-opacity-50`
- `border-opacity-75`
- `border-primary`
- `border-primary-darken`
- `border-primary-subtle`
- `border-secondary`
- `border-secondary-light`
- `border-secondary-lighten`
- `border-secondary-subtle`
- `border-success`
- `border-success-subtle`
- `border-warning`
- `border-warning-subtle`
- `border-white`

</details>

<details><summary><code>bg-*</code> — 6</summary>

- `bg-body`
- `bg-body-secondary`
- `bg-body-tertiary`
- `bg-danger-light`
- `bg-fixed`
- `bg-primary-gradient`

</details>

<details><summary><code>alert-*</code> — 24</summary>

- `alert-beige`
- `alert-black`
- `alert-danger`
- `alert-dark`
- `alert-dark-lighten`
- `alert-dismissible`
- `alert-heading`
- `alert-icon`
- `alert-info`
- `alert-info-dark`
- `alert-info-light`
- `alert-info-lighten`
- `alert-light`
- `alert-lighten`
- `alert-link`
- `alert-muted`
- `alert-primary`
- `alert-primary-darken`
- `alert-secondary`
- `alert-secondary-light`
- `alert-secondary-lighten`
- `alert-success`
- `alert-warning`
- `alert-white`

</details>

<details><summary><code>focus-*</code> — 20 · 20 custom</summary>

- `focus-ring-beige` _(custom)_
- `focus-ring-black` _(custom)_
- `focus-ring-danger` _(custom)_
- `focus-ring-dark` _(custom)_
- `focus-ring-dark-lighten` _(custom)_
- `focus-ring-info` _(custom)_
- `focus-ring-info-dark` _(custom)_
- `focus-ring-info-light` _(custom)_
- `focus-ring-info-lighten` _(custom)_
- `focus-ring-light` _(custom)_
- `focus-ring-lighten` _(custom)_
- `focus-ring-muted` _(custom)_
- `focus-ring-primary` _(custom)_
- `focus-ring-primary-darken` _(custom)_
- `focus-ring-secondary` _(custom)_
- `focus-ring-secondary-light` _(custom)_
- `focus-ring-secondary-lighten` _(custom)_
- `focus-ring-success` _(custom)_
- `focus-ring-warning` _(custom)_
- `focus-ring-white` _(custom)_

</details>

<details><summary><code>hover-*</code> — 20 · 20 custom</summary>

- `hover-beige` _(custom)_
- `hover-black` _(custom)_
- `hover-danger` _(custom)_
- `hover-dark` _(custom)_
- `hover-dark-lighten` _(custom)_
- `hover-info` _(custom)_
- `hover-info-dark` _(custom)_
- `hover-info-light` _(custom)_
- `hover-info-lighten` _(custom)_
- `hover-light` _(custom)_
- `hover-lighten` _(custom)_
- `hover-muted` _(custom)_
- `hover-primary` _(custom)_
- `hover-primary-darken` _(custom)_
- `hover-secondary` _(custom)_
- `hover-secondary-light` _(custom)_
- `hover-secondary-lighten` _(custom)_
- `hover-success` _(custom)_
- `hover-warning` _(custom)_
- `hover-white` _(custom)_

</details>

<details><summary><code>modal-*</code> — 20</summary>

- `modal-backdrop`
- `modal-body`
- `modal-content`
- `modal-dialog`
- `modal-dialog-centered`
- `modal-dialog-scrollable`
- `modal-footer`
- `modal-fullscreen`
- `modal-fullscreen-lg-down`
- `modal-fullscreen-md-down`
- `modal-fullscreen-sm-down`
- `modal-fullscreen-xl-down`
- `modal-fullscreen-xxl-down`
- `modal-fullscreen-xxxl-down`
- `modal-header`
- `modal-lg`
- `modal-sm`
- `modal-static`
- `modal-title`
- `modal-xl`

</details>

<details><summary><code>sf-*</code> — 19 · 19 custom</summary>

- `sf-dump` _(custom)_
- `sf-dump-const` _(custom)_
- `sf-dump-ellipsis-note` _(custom)_
- `sf-dump-index` _(custom)_
- `sf-dump-key` _(custom)_
- `sf-dump-meta` _(custom)_
- `sf-dump-note` _(custom)_
- `sf-dump-num` _(custom)_
- `sf-dump-private` _(custom)_
- `sf-dump-protected` _(custom)_
- `sf-dump-public` _(custom)_
- `sf-dump-ref` _(custom)_
- `sf-dump-str` _(custom)_
- `sf-dump-str-collapse` _(custom)_
- `sf-dump-str-expand` _(custom)_
- `sf-dump-toggle` _(custom)_
- `sf-toolbar` _(custom)_
- `sf-toolbar-icon-closed` _(custom)_
- `sf-toolbar-toggle-button` _(custom)_

</details>

<details><summary><code>rounded-*</code> — 18</summary>

- `rounded-bottom`
- `rounded-bottom-3`
- `rounded-bottom-circle`
- `rounded-bottom-pill`
- `rounded-circle`
- `rounded-end`
- `rounded-end-3`
- `rounded-end-circle`
- `rounded-end-pill`
- `rounded-pill`
- `rounded-start`
- `rounded-start-3`
- `rounded-start-circle`
- `rounded-start-pill`
- `rounded-top`
- `rounded-top-3`
- `rounded-top-circle`
- `rounded-top-pill`

</details>

<details><summary><code>next-*</code> — 16 · 16 custom</summary>

- `next-bg-black` _(custom)_
- `next-bg-danger` _(custom)_
- `next-bg-dark` _(custom)_
- `next-bg-dark-lighten` _(custom)_
- `next-bg-info` _(custom)_
- `next-bg-info-dark` _(custom)_
- `next-bg-info-light` _(custom)_
- `next-bg-lighten` _(custom)_
- `next-bg-muted` _(custom)_
- `next-bg-primary` _(custom)_
- `next-bg-primary-darken` _(custom)_
- `next-bg-secondary` _(custom)_
- `next-bg-secondary-lighten` _(custom)_
- `next-bg-success` _(custom)_
- `next-bg-warning` _(custom)_
- `next-bg-white` _(custom)_

</details>

<details><summary><code>form-*</code> — 13 · 13 custom</summary>

- `form-check-reverse` _(custom)_
- `form-control` _(custom)_
- `form-control-color` _(custom)_
- `form-control-lg` _(custom)_
- `form-control-sm` _(custom)_
- `form-group` _(custom)_
- `form-range` _(custom)_
- `form-select` _(custom)_
- `form-select-lg` _(custom)_
- `form-select-sm` _(custom)_
- `form-success-card` _(custom)_
- `form-switch` _(custom)_
- `form-thanks-container` _(custom)_

</details>

<details><summary><code>is-*</code> — 13 · 13 custom</summary>

- `is-active` _(custom)_
- `is-closing` _(custom)_
- `is-disabled` _(custom)_
- `is-favorite` _(custom)_
- `is-focus-in` _(custom)_
- `is-focused` _(custom)_
- `is-header-submenu` _(custom)_
- `is-highlighted` _(custom)_
- `is-invalid` _(custom)_
- `is-near-footer` _(custom)_
- `is-open` _(custom)_
- `is-overflow` _(custom)_
- `is-valid` _(custom)_

</details>

<details><summary><code>carousel-*</code> — 12</summary>

- `carousel-caption`
- `carousel-content`
- `carousel-control-next`
- `carousel-control-next-icon`
- `carousel-control-pause`
- `carousel-control-play`
- `carousel-control-prev`
- `carousel-control-prev-icon`
- `carousel-controls`
- `carousel-dark`
- `carousel-indicators`
- `carousel-inner`

</details>

<details><summary><code>-*</code> — 10 · 10 custom</summary>

- `-btn` _(custom)_
- `-card-arrow` _(custom)_
- `-faded` _(custom)_
- `-hidden` _(custom)_
- `-large` _(custom)_
- `-link` _(custom)_
- `-magnetic` _(custom)_
- `-pointer` _(custom)_
- `-primary` _(custom)_
- `-small` _(custom)_

</details>

<details><summary><code>shadow-*</code> — 10</summary>

- `shadow-bottom`
- `shadow-bottom-mobile`
- `shadow-box`
- `shadow-left`
- `shadow-left-mobile`
- `shadow-none`
- `shadow-right`
- `shadow-right-mobile`
- `shadow-top`
- `shadow-top-mobile`

</details>

<details><summary><code>table-*</code> — 9</summary>

- `table-active`
- `table-bordered`
- `table-content`
- `table-group-divider`
- `table-responsive`
- `table-sm`
- `table-striped`
- `table-striped-columns`
- `table-title`

</details>

<details><summary><code>icw-*</code> — 8 · 8 custom</summary>

- `icw-briefcase` _(custom)_
- `icw-chart-line` _(custom)_
- `icw-eye` _(custom)_
- `icw-pencil-alt` _(custom)_
- `icw-repeat` _(custom)_
- `icw-tachometer-alt` _(custom)_
- `icw-times` _(custom)_
- `icw-users` _(custom)_

</details>

<details><summary><code>accordion-*</code> — 6</summary>

- `accordion-body`
- `accordion-button`
- `accordion-collapse`
- `accordion-flush`
- `accordion-header`
- `accordion-item`

</details>

<details><summary><code>navbar-*</code> — 6</summary>

- `navbar-expand-md`
- `navbar-expand-sm`
- `navbar-expand-xl`
- `navbar-expand-xxl`
- `navbar-expand-xxxl`
- `navbar-text`

</details>

<details><summary><code>no-*</code> — 6 · 6 custom</summary>

- `no-backdrop` _(custom)_
- `no-dots` _(custom)_
- `no-head` _(custom)_
- `no-head-body` _(custom)_
- `no-min-width` _(custom)_
- `no-radius` _(custom)_

</details>

<details><summary><code>size-*</code> — 6 · 6 custom</summary>

- `size-lg-icon` _(custom)_
- `size-md-icon` _(custom)_
- `size-sm-icon` _(custom)_
- `size-xl-icon` _(custom)_
- `size-xs-icon` _(custom)_
- `size-xxl-icon` _(custom)_

</details>

<details><summary><code>bs-*</code> — 5 · 5 custom</summary>

- `bs-tooltip-auto` _(custom)_
- `bs-tooltip-bottom` _(custom)_
- `bs-tooltip-end` _(custom)_
- `bs-tooltip-start` _(custom)_
- `bs-tooltip-top` _(custom)_

</details>

<details><summary><code>dropdown-*</code> — 5</summary>

- `dropdown-back`
- `dropdown-item`
- `dropdown-menu`
- `dropdown-menu-level-1`
- `dropdown-toggle`

</details>

<details><summary><code>favorites-*</code> — 5 · 5 custom</summary>

- `favorites-actions` _(custom)_
- `favorites-container` _(custom)_
- `favorites-count` _(custom)_
- `favorites-empty` _(custom)_
- `favorites-header` _(custom)_

</details>

<details><summary><code>img-*</code> — 5</summary>

- `img-copyright`
- `img-not-contractual`
- `img-rotation`
- `img-title`
- `img-wrap`

</details>

<details><summary><code>media-*</code> — 5 · 5 custom</summary>

- `media-block` _(custom)_
- `media-col` _(custom)_
- `media-content` _(custom)_
- `media-cta` _(custom)_
- `media-wrap` _(custom)_

</details>

<details><summary><code>card-*</code> — 4</summary>

- `card-body`
- `card-header-pills`
- `card-header-tabs`
- `card-link`

</details>

<details><summary><code>feature-*</code> — 4 · 4 custom</summary>

- `feature-head` _(custom)_
- `feature-icon` _(custom)_
- `feature-label` _(custom)_
- `feature-value` _(custom)_

</details>

<details><summary><code>in-*</code> — 4 · 4 custom</summary>

- `in-bg-primary` _(custom)_
- `in-bg-secondary` _(custom)_
- `in-bg-secondary-light` _(custom)_
- `in-footer` _(custom)_

</details>

<details><summary><code>input-*</code> — 4 · 4 custom</summary>

- `input-file-upload-svg` _(custom)_
- `input-group-lg` _(custom)_
- `input-group-sm` _(custom)_
- `input-group-text` _(custom)_

</details>

<details><summary><code>sub-*</code> — 4 · 4 custom</summary>

- `sub-title-h3` _(custom)_
- `sub-title-h4` _(custom)_
- `sub-title-h5` _(custom)_
- `sub-title-h6` _(custom)_

</details>

<details><summary><code>video-*</code> — 4 · 4 custom</summary>

- `video-block` _(custom)_
- `video-block-html` _(custom)_
- `video-container` _(custom)_
- `video-fluid` _(custom)_

</details>

<details><summary><code>choices__list-*</code> — 3 · 3 custom</summary>

- `choices__list--dropdown` _(custom)_
- `choices__list--multiple` _(custom)_
- `choices__list--single` _(custom)_

</details>

<details><summary><code>container-*</code> — 3</summary>

- `container-fluid-left`
- `container-xxl`
- `container-xxxl`

</details>

<details><summary><code>content-*</code> — 3 · 3 custom</summary>

- `content-body-box` _(custom)_
- `content-col` _(custom)_
- `content-page-box` _(custom)_

</details>

<details><summary><code>have-*</code> — 3 · 3 custom</summary>

- `have-content-side` _(custom)_
- `have-features` _(custom)_
- `have-head` _(custom)_

</details>

<details><summary><code>hide-*</code> — 3 · 3 custom</summary>

- `hide-cursor` _(custom)_
- `hide-overlay` _(custom)_
- `hide-text` _(custom)_

</details>

<details><summary><code>info-*</code> — 3 · 3 custom</summary>

- `info-dark` _(custom)_
- `info-light` _(custom)_
- `info-lighten` _(custom)_

</details>

<details><summary><code>nav-*</code> — 3</summary>

- `nav-fill`
- `nav-item`
- `nav-pills`

</details>

<details><summary><code>not-*</code> — 3 · 3 custom</summary>

- `not-desktop` _(custom)_
- `not-expanded` _(custom)_
- `not-mobile` _(custom)_

</details>

<details><summary><code>player-*</code> — 3 · 3 custom</summary>

- `player-control` _(custom)_
- `player-control-play-wrap` _(custom)_
- `player-control-wrap` _(custom)_

</details>

<details><summary><code>progress-*</code> — 3</summary>

- `progress-bar`
- `progress-bar-animated`
- `progress-bar-striped`

</details>

<details><summary><code>spinner-*</code> — 3</summary>

- `spinner-border`
- `spinner-border-sm`
- `spinner-wrap`

</details>

<details><summary><code>sticky-*</code> — 3</summary>

- `sticky-md-top`
- `sticky-sm-top`
- `sticky-top`

</details>

<details><summary><code>toast-*</code> — 3</summary>

- `toast-body`
- `toast-container`
- `toast-header`

</details>

<details><summary><code>vertical-*</code> — 3 · 3 custom</summary>

- `vertical-align` _(custom)_
- `vertical-align-bottom` _(custom)_
- `vertical-align-lg` _(custom)_

</details>

<details><summary><code>choices__item-*</code> — 2 · 2 custom</summary>

- `choices__item--disabled` _(custom)_
- `choices__item--selectable` _(custom)_

</details>

<details><summary><code>close-*</code> — 2</summary>

- `close-menu`
- `close-menu-level-1`

</details>

<details><summary><code>control-*</code> — 2 · 2 custom</summary>

- `control-pause` _(custom)_
- `control-pause-btn` _(custom)_

</details>

<details><summary><code>display-*</code> — 2</summary>

- `display-initial`
- `display-text`

</details>

<details><summary><code>edit-*</code> — 2 · 2 custom</summary>

- `edit-trans-block` _(custom)_
- `edit-trans-btn-modal` _(custom)_

</details>

<details><summary><code>email-*</code> — 2 · 2 custom</summary>

- `email-link` _(custom)_
- `email-text` _(custom)_

</details>

<details><summary><code>embed-*</code> — 2 · 2 custom</summary>

- `embed-youtube` _(custom)_
- `embed-youtube-play` _(custom)_

</details>

<details><summary><code>file-*</code> — 2 · 2 custom</summary>

- `file-btn-help-text` _(custom)_
- `file-group` _(custom)_

</details>

<details><summary><code>large-*</code> — 2 · 2 custom</summary>

- `large-file-container` _(custom)_
- `large-file-info` _(custom)_

</details>

<details><summary><code>layout-*</code> — 2 · 2 custom</summary>

- `layout-block-type-title-header` _(custom)_
- `layout-zone-navigation` _(custom)_

</details>

<details><summary><code>list-*</code> — 2 · 1 custom</summary>

- `list-group`
- `list-style-check` _(custom)_

</details>

<details><summary><code>mf-*</code> — 2 · 2 custom</summary>

- `mf-cursor` _(custom)_
- `mf-cursor-text` _(custom)_

</details>

<details><summary><code>pagination-*</code> — 2</summary>

- `pagination-lg`
- `pagination-sm`

</details>

<details><summary><code>scrollbar-*</code> — 2 · 2 custom</summary>

- `scrollbar-thumb` _(custom)_
- `scrollbar-track` _(custom)_

</details>

<details><summary><code>search-*</code> — 2 · 2 custom</summary>

- `search-container` _(custom)_
- `search-engine-form` _(custom)_

</details>

<details><summary><code>secondary-*</code> — 2 · 2 custom</summary>

- `secondary-light` _(custom)_
- `secondary-lighten` _(custom)_

</details>

<details><summary><code>splide__arrows-*</code> — 2 · 2 custom</summary>

- `splide__arrows--rtl` _(custom)_
- `splide__arrows--ttb` _(custom)_

</details>

<details><summary><code>tooltip-*</code> — 2</summary>

- `tooltip-arrow`
- `tooltip-inner`

</details>

<details><summary><code>trans-*</code> — 2 · 2 custom</summary>

- `trans-edit-form` _(custom)_
- `trans-edit-input-group` _(custom)_

</details>

<details><summary><code>webmaster-*</code> — 2 · 2 custom</summary>

- `webmaster-link-edit` _(custom)_
- `webmaster-tooltip` _(custom)_

</details>

<details><summary><code>active-*</code> — 1</summary>

- `active-gdpr-cookie`

</details>

<details><summary><code>arrows-*</code> — 1 · 1 custom</summary>

- `arrows-wrap` _(custom)_

</details>

<details><summary><code>aspect-*</code> — 1 · 1 custom</summary>

- `aspect-ratio` _(custom)_

</details>

<details><summary><code>badge-*</code> — 1</summary>

- `badge-wrap`

</details>

<details><summary><code>caption-*</code> — 1 · 1 custom</summary>

- `caption-title` _(custom)_

</details>

<details><summary><code>center-*</code> — 1 · 1 custom</summary>

- `center-arrows` _(custom)_

</details>

<details><summary><code>choices-*</code> — 1 · 1 custom</summary>

- `choices-users-select` _(custom)_

</details>

<details><summary><code>clear-*</code> — 1 · 1 custom</summary>

- `clear-wrap` _(custom)_

</details>

<details><summary><code>collapse-*</code> — 1</summary>

- `collapse-horizontal`

</details>

<details><summary><code>contact-*</code> — 1 · 1 custom</summary>

- `contact-action-container` _(custom)_

</details>

<details><summary><code>cta-*</code> — 1 · 1 custom</summary>

- `cta-content` _(custom)_

</details>

<details><summary><code>dark-*</code> — 1 · 1 custom</summary>

- `dark-lighten` _(custom)_

</details>

<details><summary><code>date-*</code> — 1 · 1 custom</summary>

- `date-group` _(custom)_

</details>

<details><summary><code>div-*</code> — 1 · 1 custom</summary>

- `div-introduction` _(custom)_

</details>

<details><summary><code>fa-*</code> — 1 · 1 custom</summary>

- `fa-spin` _(custom)_

</details>

<details><summary><code>faq-*</code> — 1 · 1 custom</summary>

- `faq-accordion` _(custom)_

</details>

<details><summary><code>fixed-*</code> — 1</summary>

- `fixed-news`

</details>

<details><summary><code>floating-*</code> — 1</summary>

- `floating-form`

</details>

<details><summary><code>focused-*</code> — 1 · 1 custom</summary>

- `focused-el` _(custom)_

</details>

<details><summary><code>footer-*</code> — 1 · 1 custom</summary>

- `footer-link` _(custom)_

</details>

<details><summary><code>gdpr-*</code> — 1 · 1 custom</summary>

- `gdpr-activation-placeholder` _(custom)_

</details>

<details><summary><code>ginner-*</code> — 1 · 1 custom</summary>

- `ginner-container` _(custom)_

</details>

<details><summary><code>glightbox-*</code> — 1 · 1 custom</summary>

- `glightbox-container` _(custom)_

</details>

<details><summary><code>h-*</code> — 1</summary>

- `h-0`

</details>

<details><summary><code>html-*</code> — 1 · 1 custom</summary>

- `html-video` _(custom)_

</details>

<details><summary><code>hx-*</code> — 1 · 1 custom</summary>

- `hx-include-media-loader` _(custom)_

</details>

<details><summary><code>infos-*</code> — 1 · 1 custom</summary>

- `infos-img` _(custom)_

</details>

<details><summary><code>internal-*</code> — 1 · 1 custom</summary>

- `internal-error-show` _(custom)_

</details>

<details><summary><code>label-*</code> — 1 · 1 custom</summary>

- `label-help` _(custom)_

</details>

<details><summary><code>lazy-*</code> — 1 · 1 custom</summary>

- `lazy-picture` _(custom)_

</details>

<details><summary><code>learn-*</code> — 1 · 1 custom</summary>

- `learn-more` _(custom)_

</details>

<details><summary><code>level-*</code> — 1 · 1 custom</summary>

- `level-1` _(custom)_

</details>

<details><summary><code>listing-*</code> — 1 · 1 custom</summary>

- `listing-box` _(custom)_

</details>

<details><summary><code>loader-*</code> — 1 · 1 custom</summary>

- `loader-image-wrapper` _(custom)_

</details>

<details><summary><code>locales-*</code> — 1 · 1 custom</summary>

- `locales-switcher` _(custom)_

</details>

<details><summary><code>menu-*</code> — 1 · 1 custom</summary>

- `menu-open` _(custom)_

</details>

<details><summary><code>news-*</code> — 1 · 1 custom</summary>

- `news-category-title` _(custom)_

</details>

<details><summary><code>offcanvas-*</code> — 1</summary>

- `offcanvas-body`

</details>

<details><summary><code>opacity-*</code> — 1</summary>

- `opacity-50`

</details>

<details><summary><code>overflow-*</code> — 1</summary>

- `overflow-initial`

</details>

<details><summary><code>overlay-*</code> — 1 · 1 custom</summary>

- `overlay-video` _(custom)_

</details>

<details><summary><code>parlx-*</code> — 1 · 1 custom</summary>

- `parlx-children` _(custom)_

</details>

<details><summary><code>phone-*</code> — 1 · 1 custom</summary>

- `phone-link` _(custom)_

</details>

<details><summary><code>pictogram-*</code> — 1 · 1 custom</summary>

- `pictogram-wrap` _(custom)_

</details>

<details><summary><code>placeholder-*</code> — 1 · 1 custom</summary>

- `placeholder-tawk-to` _(custom)_

</details>

<details><summary><code>pointer-*</code> — 1 · 1 custom</summary>

- `pointer-event` _(custom)_

</details>

<details><summary><code>primary-*</code> — 1 · 1 custom</summary>

- `primary-darken` _(custom)_

</details>

<details><summary><code>product-*</code> — 1 · 1 custom</summary>

- `product-card` _(custom)_

</details>

<details><summary><code>radio-*</code> — 1 · 1 custom</summary>

- `radio-custom` _(custom)_

</details>

<details><summary><code>radius-*</code> — 1 · 1 custom</summary>

- `radius-large` _(custom)_

</details>

<details><summary><code>reset-*</code> — 1 · 1 custom</summary>

- `reset-select-wrap` _(custom)_

</details>

<details><summary><code>row-*</code> — 1 · 1 custom</summary>

- `row-container` _(custom)_

</details>

<details><summary><code>separator-*</code> — 1 · 1 custom</summary>

- `separator-block` _(custom)_

</details>

<details><summary><code>show-*</code> — 1</summary>

- `show-more-text`

</details>

<details><summary><code>skin-*</code> — 1 · 1 custom</summary>

- `skin-admin` _(custom)_

</details>

<details><summary><code>slider-*</code> — 1 · 1 custom</summary>

- `slider-link` _(custom)_

</details>

<details><summary><code>small-*</code> — 1</summary>

- `small-fixed`

</details>

<details><summary><code>sound-*</code> — 1 · 1 custom</summary>

- `sound-control` _(custom)_

</details>

<details><summary><code>splide-*</code> — 1 · 1 custom</summary>

- `splide--rtl` _(custom)_

</details>

<details><summary><code>splide__pagination-*</code> — 1 · 1 custom</summary>

- `splide__pagination--ttb` _(custom)_

</details>

<details><summary><code>splide__track-*</code> — 1 · 1 custom</summary>

- `splide__track--ttb` _(custom)_

</details>

<details><summary><code>sr-*</code> — 1</summary>

- `sr-only`

</details>

<details><summary><code>submenu-*</code> — 1 · 1 custom</summary>

- `submenu-columns` _(custom)_

</details>

<details><summary><code>thanks-*</code> — 1 · 1 custom</summary>

- `thanks-modal` _(custom)_

</details>

<details><summary><code>title-*</code> — 1 · 1 custom</summary>

- `title-header-features` _(custom)_

</details>

<details><summary><code>turbo-*</code> — 1 · 1 custom</summary>

- `turbo-progress-bar` _(custom)_

</details>

<details><summary><code>two-*</code> — 1 · 1 custom</summary>

- `two-columns` _(custom)_

</details>

<details><summary><code>user-*</code> — 1</summary>

- `user-select-none`

</details>

<details><summary><code>was-*</code> — 1 · 1 custom</summary>

- `was-validated` _(custom)_

</details>

## JavaScript (octets non exécutés au chargement)

| Script | Total | Non exécuté |
|---|--:|--:|
| 1693.8b9be840.js | 74 Ko | 86% |
| 1576.5629c6a9.js | 23 Ko | 81% |
| 7846.68a64b56.js | 39 Ko | 47% |
| 7065.7cf26c04.js | 25 Ko | 60% |
| front-default-vendor.b199b706.js | 18 Ko | 55% |
| 9824.ffd8966f.js | 15 Ko | 63% |
| 9104.b93a098a.js | 11 Ko | 84% |
| 1594.87f6684a.js | 9 Ko | 76% |
| 9753.56bda82a.js | 12 Ko | 59% |
| 6613.49803dd2.js | 11 Ko | 60% |
| 5299.a1c6eb95.js | 11 Ko | 46% |
| 3825.125f2492.js | 6 Ko | 81% |
| front-default-catalog.afa0a0df.js | 5 Ko | 82% |
| front-default-cms.f154aade.js | 8 Ko | 45% |
| front-default-newscast.a0a08e58.js | 5 Ko | 71% |
| 5863.0c2d549a.js | 3 Ko | 75% |
| 3929.37bc91d9.js | 3 Ko | 58% |
| front-default-security.643d0638.js | 5 Ko | 38% |
| 2410.88878dd0.js | 4 Ko | 40% |
| 2218.88c89751.js | 774 Ko | 0% |
