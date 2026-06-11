# 🔍 Audit global — front `up-animation`

> Généré via la skill **impeccable** (`audit`) — 2026-06-11.
>
> **Périmètre & méthode.** Audit **statique** du front public (`templates/front/default`,
> `assets/scss/front/default`, `assets/js/front`) : lecture du design system + détecteur
> d'anti-patterns intégré sur ~150 fichiers SCSS custom (hors Bootstrap/highlight vendored)
> et les templates Twig. **Non couvert** (impossible sans build + navigateur) : contraste
> mesuré au rendu, Lighthouse/axe, tests clavier réels, rendu multi-viewport. Ces points
> sont signalés comme *à valider en runtime*, pas comme faits vérifiés.

## Score de santé

| # | Dimension | Score | Constat clé |
|---|-----------|-------|-------------|
| 1 | Accessibilité | **3 / 4** | Socle RGAA solide (skip-links, 394 `aria-label`, live regions, reduced-motion global) ; pause clavier marquee à confirmer |
| 2 | Performance | **3 / 4** | Infra excellente (CSS async, preload, `modulepreload`, `defer`, lazy) ; 13 transitions sur propriétés de layout |
| 3 | Responsive | **3 / 4** | Type fluide RFS, système de marges en ratio, breakpoints xs→xxxl ; rendu non vérifiable ici |
| 4 | Theming | **4 / 4** | Système de tokens complet, light/dark par maps, custom props exposées, `data-theme` anti-FOUC |
| 5 | Anti-patterns | **4 / 4** | Aucun tell IA : palette de marque assumée, police unique + fallback métriqué |
| **Total** | | **17 / 20** | **Good (haut de fourchette)** |

## Verdict anti-patterns — *commencer ici*

**PASS, sans ambiguïté.** Ce n'est pas du code généré par IA : c'est un thème CMS artisanal et mature.

- Palette **engagée** : orange `#ff7100` + navy `#22254e` sur fond blanc — aucune trace de la « cream/sand » par défaut de l'IA 2026.
- **Une seule** famille (Hankengrotesk) en plusieurs graisses + fallback Arial *métriqué* (`size-adjust`, `ascent-override`) → anti-CLS.
- Patterns d'interaction réels (`aria-expanded`/`controls`/`haspopup`), pas de gradient-text, pas de glassmorphism décoratif par défaut, pas de grilles de cartes identiques.
- Indices de maîtrise : commentaire `_variables` documentant le remplacement de `all .4s` (repaint) par une liste de propriétés explicites ; exceptions `@media` documentées vs la mixin `mediaQuery()` du projet.

## Synthèse exécutive

- **Score : 17/20 (Good).** Aucun P0.
- **Répartition** : P0 = 0 · P1 = 0 · **P2 = 3** · **P3 = 4**.
- **Top constats :**
  1. *(P2)* 13 transitions animant `width`/`height`/`max-height` (carousel, audio, video, website-alert, mc-calendar) → risque de jank.
  2. *(P2)* Marquee infini : pause au **survol uniquement** → WCAG 2.2.2 (clavier) à confirmer.
  3. *(P2)* Contrastes réels & parcours clavier non mesurés ici — à valider au runtime (cohérent avec `ACCESSIBILITE.md §6 « À valider »`).
  4. *(P3)* Bug `og:url` dans la branche **de repli** de `base.html.twig:111` (le chemin réel `seo.html.twig` est correct).
  5. *(P3)* Code mort / TODO non soldés (`REGARDER ISACAR` ×4, preloads de police commentés).

## Constats détaillés par sévérité

### [P2] Transitions sur propriétés de layout
- **Localisation** : `components/_audio.scss` (6×), `components/_carousel.scss` (3×), `components/blocks/_video.scss:106`, `components/_website-alert.scss:130-135` (`margin-top/bottom`), `components/form/_mc-calendar.scss:348` (`max-height`), `layout/_navigation.scss:129` (`max-height`).
- **Catégorie** : Performance. **Impact** : `width/height/margin/max-height` déclenchent layout + paint à chaque frame.
- **Nuance vérifiée** : plusieurs `width` dans `_audio.scss` sont des barres de progression (acceptable). À traiter en priorité : `_website-alert` (marges) et `_navigation`/`_mc-calendar` (`max-height` → préférer `grid-template-rows: 0fr→1fr` ou `transform`).
- **Commande** : `/impeccable optimize`

### [P2] Pause clavier des contenus en mouvement (marquees)
- **Localisation** : `components/_infinite-marquee.scss:140` (pause `:hover` seul) ; `_vanilla-marquee.scss` a une classe `.paused` (mieux).
- **Catégorie** : Accessibilité (WCAG **2.2.2 Pause/Stop/Hide**, niveau A). **Impact** : un défilement >5 s sans contrôle atteignable au clavier.
- **Atténuation constatée** : le kill-switch global `prefers-reduced-motion` (`_layout.scss:782`) coupe l'animation pour les utilisateurs concernés — mais 2.2.2 exige un contrôle **indépendamment** de cette préférence.
- **Commande** : `/impeccable harden`

### [P2] Mesures runtime manquantes (hypothèse, non vérifié)
- Contraste réel, focus visibles au rendu, ordre de tabulation, débordements mobiles : non testables ici. `$placeholder-color:#6c757d` sur blanc ≈ 4,68:1 (passe AA *en théorie*).
- **Commande** : `/impeccable audit` après build (Lighthouse + axe).

### [P3] `og:url` = description dans le repli SEO
- `base.html.twig:111`. N'affecte que les pages sans `seo` défini. → `/impeccable harden`

### [P3] Code mort / TODO
- `base.html.twig` (`REGARDER ISACAR` ×4, preloads Sora commentés). → `/impeccable polish`

### [P3] Préchargement woff2 non soldé
- Police rendue via CSS async, woff2 non préchargés (TODO ouvert) ; fallback métriqué limite le risque CLS. → `/impeccable optimize`

### [P3] Faux positifs détecteur (documentés, sans action)
- `Arial` dans le style reCAPTCHA, « 193 em-dashes » et « ratio 1.4:1 » dans `accessibility-widget.scss` = commentaires + pas du font-size du widget a11y.

## Patterns & enjeux systémiques

- **Theming exemplaire à généraliser** : tokens + maps light/dark + custom props. À maintenir comme référence.
- **`max-height`/`margin` animés** : motif récurrent pour les ouvertures (nav, calendrier, alerte). Enjeu = adopter un pattern unique non-layout (`grid 0fr→1fr`) et le diffuser.
- **Reduced-motion couvert globalement** mais 2.2.2 (contrôle explicite) reste à traiter composant par composant.

## Points positifs (à préserver)

- **Sécurité** : `csp_nonce()` systématique sur scripts/styles.
- **Perf** : chargement CSS asynchrone + `<noscript>`, preconnect/dns-prefetch tiers, `modulepreload`, `fetchpriority`, preloads d'images *media-scopés*, `loading="lazy"`.
- **A11y** : skip-links, `<main role>`, `lang`/`dir` (RTL), widget d'accessibilité dédié, doc RGAA `ACCESSIBILITE.md` avec section « à valider » assumée.
- **i18n** : tout passe par `trans` ; direction de lecture dynamique.

## Actions recommandées (par priorité)

1. **[P2] `/impeccable optimize`** — remplacer les transitions `max-height`/`margin` (nav, alerte, calendrier) par `grid-template-rows`/`transform`.
2. **[P2] `/impeccable harden`** — ajouter un bouton pause/lecture clavier sur le marquee infini ; corriger le repli `og:url`.
3. **[P2] `/impeccable audit`** *(post-build)* — relancer avec Lighthouse + axe pour les mesures runtime.
4. **[P3] `/impeccable polish`** — nettoyer code mort / TODO de `base.html.twig`, statuer sur le preload des woff2.

> Relancez `/impeccable audit` après corrections pour voir le score progresser.

---

*Audit statique, lecture seule — aucune modification de code n'a été effectuée. Les mesures
de rendu (contraste, clavier, mobile) restent à valider après un build + passage navigateur.
Un `/impeccable init` (création de `PRODUCT.md`) permettrait des recommandations plus ciblées
sur le registre du site.*
