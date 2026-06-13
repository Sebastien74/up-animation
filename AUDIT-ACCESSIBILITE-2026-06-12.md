# Audit d'accessibilité — WCAG 2.2 AA (front)

**Date** : 12 juin 2026
**Périmètre** : front Symfony — `templates/front/default/**`, `assets/scss/front/default/**`, `assets/js/front/default/**`, thèmes clair + sombre, widget d'accessibilité visiteur.
**Outil** : agent `web-accessibility-wizard` (Community-Access/accessibility-agents), référentiel WCAG 2.2 niveau AA.
**Type** : audit en lecture seule (aucune modification du code applicatif).

---

## Méthodologie & limite

Le **scan dynamique axe-core n'a pas pu aboutir** en local : Apache sert `up-animation.local` en HTTPS depuis un vhost empilé et l'application force le domaine canonique (`security.yaml: requires_channel`), provoquant une boucle de redirection pour toute approche en lecture seule (port alternatif, en-têtes `X-Forwarded-Proto`). La modification du vhost Apache partagé étant hors périmètre, l'audit repose sur une **analyse statique exhaustive** du code source.

Chaque finding est étiqueté :

- **[FAIT]** — markup/CSS présent et vérifié dans le code.
- **[HYPOTHÈSE]** — dépend du rendu, des données de contenu ou d'une mesure (contraste) à confirmer.

**Pour fiabiliser à 100 %** : exécuter un scan axe-core + un test clavier réel une fois le DocumentRoot SSL local pointé sur `up-animation/public` (action d'admin local), notamment sur `/nous-contacter` et une fiche produit, pour couvrir les états dynamiques (menu déroulant, modale de recherche, validation de formulaire).

**Exclusions (décidées avec l'équipe, non recomptées)** :

- Contraste de l'orange de marque `#ff7100` en texte → **décision design, à ne pas corriger**.
- `target-size` des cases à cocher de formulaire → **déjà corrigé** (24px, WCAG 2.5.8).

---

## Points forts vérifiés (à préserver)

- **[FAIT]** `base.html.twig` : `<html lang dir>`, `<title>`, `viewport`, `canonical`, 2 skip-links (`#body-page`, `#footer`), `<main id="body-page" role="main">`.
- **[FAIT]** Focus clavier visible **global** : `layout/_layout.scss:25` → `:focus-visible { outline: 2px solid $focus-color; outline-offset: 2px }`. Le seul `outline:none` concerne `:focus:not(:focus-visible)` (souris). **WCAG 2.4.7 / 2.4.11 satisfaits.** (Les `outline:none` des composants de formulaire sont en `:focus` simple, neutralisés pour le clavier par la règle globale — pas un défaut.)
- **[FAIT]** Skip-links masqués hors focus / visibles au focus (pas de `display:none`) → 2.4.1 OK. `.sr-only` correct (clip-rect).
- **[FAIT]** **Widget d'accessibilité exemplaire** : `button` natifs, `aria-haspopup="dialog"`, `aria-expanded`, `aria-controls`, `aria-label`, `fieldset/legend`, SVG `aria-hidden + focusable="false"`, Escape ferme et renvoie le focus au déclencheur, panneau `hidden`, guide de lecture `aria-hidden`.
- **[FAIT]** Réduction de mouvement honorée en JS : AOS (`aos.js:11`), video-scroll (`video-scroll.js:9`), Splide (`splide-slider.js` + écoute `a11y:motion`) → 2.3.3 / 2.2.2 couverts pour ces composants.
- **[FAIT]** Thème de formulaire (`bootstrap_5_layout.html.twig`) : labels associés, erreurs avec `role="alert"`, `aria-describedby` vers l'aide quand `help` présent.

---

## Findings

### CRITIQUE

| ID | Statut | WCAG | Emplacement | Problème | Recommandation |
|----|--------|------|-------------|----------|----------------|
| C1 | [HYPOTHÈSE] | 1.1.1 (A) | médias via filtre `file()` (macros card, gallery, img-hover) | Si l'`alt` provient d'un champ média non renseigné, des images informatives peuvent sortir en `alt=""` | Auditer le remplissage des `alt` côté contenu ; garantir un `alt` par défaut signifiant pour visuels produits/agences |

*Sévérité conditionnelle aux données de contenu.*

### SERIOUS

| ID | Statut | WCAG | Emplacement | Problème | Recommandation |
|----|--------|------|-------------|----------|----------------|
| S1 | [FAIT] | 4.1.2 / 3.2.2 | `actions/menu/main.html.twig:39-40`, `bootstrap.html.twig:41-42`, `footer.html.twig:27-30`, `lateral.html.twig:68-69` | `target="_blank"` posé sur `linkBlank`, mais `rel="noopener noreferrer"` seulement sur `linkExternal` → liens internes en nouvel onglet sans `rel` ni indication | Conditionner `rel="noopener"` sur `linkBlank` + ajouter un texte masqué « (nouvelle fenêtre) » (le footer social le fait déjà) |
| S2 | [FAIT] | 3.3.1 (A) / 4.1.2 (A) | `actions/form/include/elements.html.twig:22-33`, thème `bootstrap_5_layout.html.twig:573,496` | Erreur annoncée (`role="alert"`) mais champ sans `aria-invalid="true"` ni `aria-describedby` vers le message (seule la classe `is-invalid`) | Donner un `id` au bloc erreur ; sur le widget invalide : `aria-invalid="true"` + `aria-describedby="{id}_error"` |
| S3 | [FAIT] | 1.3.1 (A) / 2.4.8 | `include/breadcrumb.html.twig:13,30,42` | `<ol id="breadcrumb">` sans `<nav aria-label>` parent ; dernier élément `.active` sans `aria-current="page"` | Envelopper dans `<nav aria-label="Fil d'Ariane">` ; ajouter `aria-current="page"` sur l'élément actif |
| S4 | [FAIT] | 2.5.8 (AA, 2.2) | `components/_carousel.scss:99-100` | Indicateurs de carrousel interactifs en `width/height: 15px` (< 24px) | Porter la zone cliquable à ≥ 24px (padding + flex en gardant le visuel 15px) |

### MODÉRÉ

| ID | Statut | WCAG | Emplacement | Problème | Recommandation |
|----|--------|------|-------------|----------|----------------|
| M1 | [FAIT] | 2.2.2 (A) | `marquee.js`, `_infinite-marquee.scss:142` | Marquee infini sans garde `prefers-reduced-motion`/`a11y:motion` (pause seulement au hover) | Ajouter `matchMedia('(prefers-reduced-motion: reduce)')` + écoute `a11y:motion` (comme Splide) et/ou `@media (prefers-reduced-motion){ animation:none }` |
| M2 | [FAIT] | 1.3.1 (A) | `actions/menu/main.html.twig`, `bootstrap.html.twig` | État actif du menu porté uniquement par la classe `.active` | Ajouter `aria-current="page"` sur le lien actif |
| M3 | [FAIT] | 4.1.2 (A) | `actions/menu/main.html.twig:108,102`, `lateral.html.twig:274` | `aria-label` redondant sur `span.nav-toggler-icon` (dans un `button` déjà labellisé) ; `type="button"` manquant | Retirer l'`aria-label` du span (ou `aria-hidden="true"`) ; ajouter `type="button"` |
| M4 | [HYPOTHÈSE] | 1.4.3 (AA) | `layout/_elements.scss:70-75` | `.dotes-link` : `font-size:12px; opacity:.5; color:inherit` → ratio probablement < 4.5:1 | Mesurer sur fonds réels (clair + sombre) ; remonter l'opacité au repos ou figer une couleur conforme |
| M5 | [HYPOTHÈSE] | 4.1.2 | `actions/slider/template/bootstrap.html.twig` | Slides de carrousel inactifs sans `aria-hidden`/`inert` → contenu hors écran potentiellement tabulable (selon masquage opacity vs display) | Confirmer au rendu ; si masquage par opacité, ajouter `aria-hidden`/`inert` sur les slides inactifs |

### MINEUR

| ID | Statut | WCAG | Emplacement | Problème | Recommandation |
|----|--------|------|-------------|----------|----------------|
| m1 | [HYPOTHÈSE] | 1.4.3 | `themes/_maps-light.scss`, `_maps-dark.scss` | Variables type `$secondary-lighten:#dddef0`, `$gray-600` à mesurer | Mesurer les ratios (aucun échec certain hors `#ff7100`) |
| m2 | [FAIT] | 1.4.12 | `layout/_elements.scss:254` | `.date` : `line-height` fixe en px (≈ 1.0) | Valeur sans unité (≥ 1.5) |
| m3 | [FAIT] | 2.4.4 / 3.2.2 | `result.html.twig:53` | Lien `target="_blank" download` sans `rel="noopener"` ni type/poids de fichier | Ajouter `rel`, indiquer le type/poids |
| m4 | [HYPOTHÈSE] | 2.4.4 | `include/macros/card.html.twig`, `img-hover.html.twig` | Liens d'action répétés (« En savoir plus », « Agrandir »…) sans `aria-label` contextualisé | Confirmer selon le rendu ; `aria-label` contextuel si le titre adjacent ne suffit pas |

---

## Synthèse & séquencement recommandé

1. **Quick wins mutualisés** (menus + breadcrumb partagés sur tout le site) : **S1, S3, M2, M3**.
2. **Thème de formulaire** (corrige tous les formulaires d'un coup) : **S2**.
3. **CSS** : **S4, M1, m2**.
4. **À confirmer au rendu réel / mesure** : **C1, M4, M5, m1, m4** — nécessitent un rendu réel ou une mesure de contraste (idéalement via scan axe-core une fois le SSL local corrigé).

> **Note conformité (RGAA / AI Act)** : ce document est un audit technique interne. Toute déclaration de conformité publique et tout contenu juridique associé relèvent d'une **validation humaine** ; l'ajustement de l'orange de marque relève d'une décision **identité visuelle**.
