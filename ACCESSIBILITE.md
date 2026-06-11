# Module d'accessibilité (front)

Barre d'outils d'accessibilité destinée aux visiteurs, accompagnée d'améliorations
de navigation au clavier orientées conformité **RGAA**.

Le module est **conditionnel** : son code (JavaScript et CSS) n'est chargé que
lorsque le module d'accessibilité est activé pour le site. Le bundle front global
n'est donc pas alourdi quand le module est désactivé.

---

## 1. Activation

Le module est piloté par un interrupteur unique dans l'administration :

> **Site → Configuration → « Activer le module d'accessibilité »**
> (`Configuration::$accessibilityStatus`, activé par défaut)

Effets de cet interrupteur :

| Statut | Widget visiteur | Chunks JS/CSS du widget | Navigation clavier Splide |
|--------|-----------------|--------------------------|----------------------------|
| Activé | Affiché | Chargés | Activée + libellés FR |
| Désactivé | Masqué | Non chargés | Désactivée (comportement d'origine) |

Le statut est exposé au front via l'attribut `data-accessibility` sur la balise
`<html>` (`1` actif / `0` inactif), lu par les composants JavaScript.

---

## 2. Fonctionnalités du widget

Un bouton flottant (en bas à gauche) ouvre un panneau de réglages. Les préférences
sont appliquées sur l'élément `<html>` et **persistées dans `localStorage`**
(clé `a11y-prefs`), donc conservées d'une page à l'autre et entre les visites.

| Réglage | Effet | Classe sur `<html>` |
|---------|-------|----------------------|
| Taille du texte | 4 paliers (100 → 150 %), via `font-size` racine (respecte le rem) | `a11y-font-scaled` + `--a11y-font-scale` |
| Contraste élevé | Texte noir sur fond blanc, liens et focus renforcés | `a11y-contrast` |
| Police lisible (dyslexie) | Police OpenDyslexic, interlignage et espacement augmentés | `a11y-readable-font` |
| Espacement du texte | Interligne / inter-lettre / inter-mot (WCAG 1.4.12) | `a11y-spacing` |
| Souligner les liens | Souligne tous les liens | `a11y-underline-links` |
| Réduire les animations | Neutralise animations et transitions CSS | `a11y-reduce-motion` |
| Grand curseur | Curseur agrandi | `a11y-big-cursor` |
| Guide de lecture | Règle horizontale suivant le pointeur | `a11y-reading-guide` |
| Réinitialiser | Remet tous les réglages par défaut | — |

Le panneau est utilisable au clavier (ouverture/fermeture, `Échap`, fermeture au
clic extérieur, focus déplacé à l'ouverture, `aria-expanded` sur le déclencheur).

---

## 3. Fichiers

| Rôle | Chemin |
|------|--------|
| Markup (CSP-safe, i18n) | `templates/front/default/include/accessibility-widget.html.twig` |
| Comportement | `assets/js/front/default/components/accessibility-widget.js` |
| Styles (chunk dédié) | `assets/scss/front/default/components/accessibility-widget.scss` |
| Inclusion + flag `data-accessibility` | `templates/front/default/base.html.twig` |
| Init conditionnelle | `assets/js/front/default/vendor.js` |

### Chargement conditionnel

- Le template n'est inclus que si `configuration.accessibilityStatus` est vrai.
- Dans `vendor.js`, le chunk JS n'est importé que si l'élément `#a11y-widget`
  est présent dans le DOM.
- Le CSS est importé dynamiquement **depuis** le module JS
  (`import('…/accessibility-widget.scss')`), il forme donc un chunk séparé
  chargé uniquement à l'initialisation du widget.

---

## 4. Conformité RGAA — navigation au clavier

### Carrousels / sliders

- **Carrousel Bootstrap** : navigation clavier déjà active (`keyboard: true`),
  boutons pause/lecture présents et opérationnels — inchangé.
- **Sliders Splide** : par défaut la navigation clavier était désactivée
  (`keyboard: false`). Lorsque le module d'accessibilité est actif, elle est
  désormais :
  - `keyboard: 'focused'` — les flèches ne pilotent le slider que lorsqu'il a le
    focus (n'accapare pas le défilement clavier de la page) ;
  - `pauseOnFocus` — le défilement automatique se suspend dès qu'un élément reçoit
    le focus (RGAA 13 / WCAG 2.2.2) ;
  - libellés lecteur d'écran en français (boutons précédent/suivant, pagination,
    lecture/pause).

  Ces ajouts sont **gatés** sur `data-accessibility="1"` : si le module est
  désactivé, le comportement d'origine est conservé.

### Existant (rappel)

- `assets/js/front/default/components/accessibility.js` gère déjà : skip-links,
  focus visible (`.focused-el`), fermeture des sous-menus/menus au `Tab`,
  neutralisation du focus sur les vidéos décoratives.

### Points restant à vérifier / pistes (non couverts par cette itération)

À traiter dans un audit manuel dédié (tests lecteur d'écran + parcours clavier
complet), idéalement gatés sur le même flag `data-accessibility` :

- **Menus déroulants « au survol »** (`navigation-functions.js`,
  `dropdownHoverAndClick`) : sur desktop, les sous-menus s'ouvrent au
  `mouseenter`/`mouseleave` uniquement. Ajouter l'ouverture/fermeture au focus
  clavier (`focusin`/`focusout`) pour rendre les sous-niveaux atteignables au
  clavier.
- **Modale de recherche** : vérifier le piège de focus (focus trap), le retour de
  focus à la fermeture et la fermeture par `Échap`.
- **Composants personnalisés** (onglets, accordéons, cartes cliquables) :
  vérifier rôles/états ARIA et l'activation par `Entrée`/`Espace`.
- **Préférence « Réduire les animations »** : envisager de suspendre aussi les
  défilements automatiques pilotés en JS (Splide/Bootstrap) quand la classe
  `a11y-reduce-motion` ou `prefers-reduced-motion` est présente.

---

## 5. Référence technique

- Clé `localStorage` : `a11y-prefs` (objet JSON des préférences).
- Variable CSS : `--a11y-font-scale` (multiplicateur de taille de police).
- Signal global : `document.documentElement.dataset.accessibility` (`'1'`/`'0'`).
- Police dyslexie : OpenDyslexic, déclarée via `assets/lib/fonts/dyslexic.scss`.
