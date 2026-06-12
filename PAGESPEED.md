# ⚡ Audit PageSpeed Insights — front `up-animation` (home + CMS)

> Branche : `claude/pagespeed-audit-fixes-7rtf5y` — généré le 2026-06-12.
> Rapport de référence : PageSpeed Insights, profil **desktop**, locale `fr`
> (`pagespeed.web.dev/analysis/.../z8gmtijwc5`).

---

## ⚠️ Limite de méthode (à lire avant tout)

**Les chiffres du rapport PSI n'ont pas pu être récupérés automatiquement** depuis cet
environnement : le domaine du site n'est pas résolu par le réseau (politique réseau de
l'environnement) et l'API PageSpeed Insights publique renvoie un quota à zéro
(`RESOURCE_EXHAUSTED`). Cet audit est donc **statique** : il croise le code réellement
servi (`templates/front/default`, `templates/core`, `src/Twig/Content`,
`src/Service/Content`, `webpack.config.js`, `public/.htaccess`) avec les critères Lighthouse.

Conséquence, en suivant la règle « faits / hypothèses / opinions » :

- **Fait vérifié** = constaté dans le code de ce dépôt (référence `fichier:ligne`).
- **Hypothèse** = mécanisme Lighthouse plausible, **à confirmer avec les chiffres réels** du rapport.
- **Opinion** = recommandation de priorisation.

👉 **Pour transformer les hypothèses en faits**, collez ici les sections du rapport
(scores des 4 catégories, Core Web Vitals, et surtout la liste « Opportunités » /
« Diagnostics » avec leurs gains estimés). L'audit sera alors recalé sur les vrais postes.

---

## 1. Synthèse exécutive

**Opinion.** Le front est **déjà fortement optimisé** : il implémente nativement la
quasi-totalité des leviers à fort impact de Lighthouse. Les marges de progression restantes
sont **ciblées** (tierces parties, CSS framework bloquant, AVIF, fontes) et non structurelles.

| # | Levier Lighthouse | État dans le code | Sévérité | Statut |
|---|-------------------|-------------------|----------|--------|
| 1 | Images formats nouvelle génération | **WebP actif** (`ALWAYS_WEBP=true`), AVIF désactivé | P2 | Fait |
| 2 | JS tierces parties (CMP, Matomo, AddThis, Tawk) | defer/async + preconnect, mais bloc TBT probable | P1 | Hypothèse |
| 3 | CSS framework bloquant le rendu | `<link rel=stylesheet>` synchrone (`base:158`) | P2 | Fait |
| 4 | Préchargement des fontes (woff2) | preload commenté / TODO ouvert | P2 | Fait |
| 5 | LCP : preload image + fetchpriority | **déjà en place**, media-scopé | — | Fait (à préserver) |
| 6 | CLS : width/height + aspect-ratio | **déjà en place** sur `<picture>` | — | Fait (à préserver) |
| 7 | Transitions sur propriétés de layout | `max-height`/`margin` animés (13×) | P3 | Fait |
| 8 | Bugs mineurs `<meta og:url>` / type MIME picture | repli `base:111` + espace `image-loader:79` | P3 | Fait |

**Répartition :** P0 = 0 · P1 = 1 (hypothèse) · P2 = 3 · P3 = 2.

---

## 2. Ce qui est déjà fait (à préserver, ne PAS « corriger »)

Ces points couvrent les opportunités Lighthouse les plus fréquentes et sont **déjà traités**.
Les lister évite de proposer des correctifs inutiles.

- **Images nouvelle génération** *(fait)* : pipeline thumbnails custom avec WebP forcé
  — `src/Service/Content/ImageThumbnail.php:32-34` (`ACTIVE_WEBP=true`, `ALWAYS_WEBP=true`).
  La config Liip commentée (`config/packages/liip_imagine.yaml`) est un faux signal : ce
  n'est pas le pipeline utilisé sur le front.
- **Images responsives** *(fait)* : `<picture>` + `srcset` en descripteurs `w` + `sizes`
  par media query — `templates/core/image.html.twig:65-107`.
- **Anti-CLS** *(fait)* : `width`/`height` + `aspect-ratio` sur chaque image, et SVG
  placeholder dimensionné — `image.html.twig:100-107`, `image-loader.html.twig:53,60`.
- **LCP** *(fait)* : preload `fetchpriority="high"` **media-scopé** par breakpoint pour
  l'image d'en-tête — `base.html.twig:184-197` ; image hors-héros en `loading="lazy"`
  via `lazyLoad = zone.position != 1` (`blocks/media/default.html.twig`).
- **CSS asynchrone** *(fait)* : fontes, print et GDPR chargés en `rel=preload` +
  `onload→stylesheet` + repli `<noscript>` — `base.html.twig:153-174`.
- **JS différé** *(fait)* : `vendor` en `modulepreload`/`defer`/`type=module` ;
  GDPR/Matomo/Analytics en `async+defer`, sortis du chemin critique — `base.html.twig:177,330-348`.
- **Compression & cache** *(fait)* : Brotli + Gzip et `Cache-Control: immutable`
  (assets build) + `Expires` 1 an sur images/CSS/JS — `public/.htaccess:88-211`.
- **PurgeCSS** *(fait)* : purge du CSS inutilisé en build prod — `webpack.config.js:312,601`.
- **Code-splitting** *(fait)* : un bundle JS/CSS par template (`home`, `cms`, `catalog`…),
  imports dynamiques (`form`, `pagination`, `medias`…) — `webpack.config.js:203-242`,
  `assets/js/front/default/templates/cms.js`.

> **Opinion.** Sur ces postes, un éventuel signalement Lighthouse serait surtout du
> **bruit** (économies théoriques de quelques Ko) ; ne pas y consacrer d'effort sans
> chiffre réel le justifiant.

---

## 3. Pistes de correctifs (problème → enjeu → réponse → dispositif → KPI)

### [P1] Tierces parties — premier suspect du TBT / « Réduire le JS inutilisé » *(hypothèse)*

- **Problème.** CMP (Axeptio), Matomo, AddThis, Tawk.to, et potentiellement GA/GTM
  s'exécutent au chargement. Sur desktop, le poste qui plombe le plus souvent Performance
  et Bonnes pratiques, ce sont ces scripts (TBT, « JS inutilisé », « coût des requêtes tierces »).
- **Enjeu.** TBT et interactivité (INP) ; score Performance.
- **Réponse stratégique.**
  1. **AddThis** (`base.html.twig:304`, filtre `|addThis`) : service **arrêté par Oracle
     depuis 2023** — à supprimer purement (gain réseau + 1 requête tierce en moins).
     *Escalade humaine : confirmer qu'aucun partage social ne dépend encore de ce widget.*
  2. **CMP / Axeptio** : charger après interaction ou en `requestIdleCallback` plutôt qu'au
     `load`, si la conformité consentement le permet.
  3. **Matomo / Analytics** : déjà `async+defer` — vérifier qu'ils ne sont pas injectés en
     `head` par `headScript` (zone admin `api.custom.headScript`).
- **Dispositif.** Audit des entrées `api.custom.*` et des filtres `|addThis`/`|tawkTo` ;
  suppression d'AddThis ; mesure avant/après sur PSI.
- **KPI.** TBT < 200 ms (desktop) ; nombre de requêtes tierces ; « Réduire le JS inutilisé » résorbé.
- ⚠️ **Hypothèse** : à pondérer avec la section « Diagnostics » du rapport réel.

### [P2] CSS framework bloquant le rendu *(fait)*

- **Problème.** Le CSS framework du thème est un `<link rel="stylesheet">` **synchrone**
  (`base.html.twig:158`), donc render-blocking — alors que fontes/print/GDPR sont async.
- **Enjeu.** FCP / LCP (« Éliminer les ressources qui bloquent le rendu »).
- **Réponse stratégique.** C'est un **choix anti-FOUC assumé** : le passer en async
  exposerait à un flash sans style. Deux options graduées :
  - *Conservatrice* : **CSS critique inliné** (above-the-fold de la home) + reste en async.
  - *Mesurée* : ne rien changer si le rapport ne signale pas ce CSS comme bloquant
    significatif (le fichier est purgé + caché 1 an, donc faible en visites répétées).
- **Dispositif.** Générer le critical CSS de `template-page` (home) ; tester le FCP.
- **KPI.** FCP ; « Render-blocking resources » (ms économisées).
- **Opinion.** À ne traiter **que si** le rapport le chiffre — risque de régression FOUC.

### [P2] Préchargement des fontes woff2 *(fait — TODO ouvert)*

- **Problème.** Le CSS des fontes est chargé en async (bien pour ne pas bloquer), mais les
  `.woff2` eux-mêmes **ne sont pas préchargés** : les `<link rel=preload as=font>` sont en
  commentaire (`base.html.twig:128-138`, marqueurs `REGARDER ISACAR`).
- **Enjeu.** Si le texte est l'élément LCP de la home, le préchargement de la graisse
  principale accélère le LCP ; sinon, réduit le décalage de fonte (FOUT).
- **Réponse stratégique.** Précharger **uniquement** la/les graisse(s) réellement utilisées
  au-dessus de la ligne de flottaison (Hankengrotesk Regular, et titre si différent),
  avec `crossorigin` + `nonce`. Atténuation déjà en place : fallback Arial **métriqué**
  (`size-adjust`) limite le CLS — donc bénéfice surtout sur LCP-texte.
- **Dispositif.** Dé-commenter/écrire les preloads ciblés ; supprimer les TODO `REGARDER ISACAR`.
- **KPI.** LCP si LCP-texte ; « Ensure text remains visible during webfont load ».

### [P2] AVIF désactivé *(fait)*

- **Problème.** `ACTIVE_AVIF=false` (`ImageThumbnail.php:33`). WebP couvre déjà l'essentiel ;
  l'AVIF apporte un gain supplémentaire (~10–30 % vs WebP, **hypothèse** selon contenu).
- **Enjeu.** Poids images / LCP sur la home (souvent riche en visuels).
- **Réponse stratégique.** Activer AVIF avec négociation `Accept` (déjà gérée,
  `ImageThumbnail.php:124-145`) + `<source type="image/avif">` en tête de `<picture>`.
  **Coût** : CPU de génération + espace disque thumbnails → activer après mesure.
- **Dispositif.** `ACTIVE_AVIF=true`, vérifier `function_exists('imageavif')` sur l'hébergement,
  préchauffer le cache thumbnails (tâche warmup déjà présente).
- **KPI.** Octets images économisés ; LCP.

### [P3] Transitions animant des propriétés de layout *(fait — repris de `AUDIT.md`)*

- **Problème.** 13 transitions sur `max-height`/`margin`/`width` (nav, alerte, calendrier,
  carousel) → layout + paint à chaque frame.
- **Enjeu.** Jank / CLS sur interactions ; marginal sur le score initial.
- **Réponse.** Remplacer par `grid-template-rows: 0fr→1fr` ou `transform`.
- **KPI.** CLS d'interaction ; fluidité (qualitatif).

### [P3] Bugs mineurs (corrigeables sans risque)

- **`og:url` du repli SEO** *(fait)* : `base.html.twig:111` met `metaDescription` dans
  `og:url`. N'affecte **que** les pages sans objet `seo` (le chemin réel `seo.html.twig:48`
  est correct). → corriger en `metaCanonical`.
- **Type MIME `<source>`** *(fait)* : espace parasite `type="image/ {{ source.extension }}"`
  dans `image-loader.html.twig:79` → `type="image/{{ source.extension }}"`. Branche
  secondaire (`activeIsGeneratedLoader=false`, **probablement dormante**) ; le chemin live
  `image.html.twig:93` est correct. Correctif trivial, sans régression attendue.

---

## 4. Plan d'action recommandé (par ROI)

| Ordre | Action | Effort | Impact attendu | Pré-requis |
|-------|--------|--------|----------------|------------|
| 1 | Supprimer AddThis (service mort) | faible | requête tierce en moins | valider usage social |
| 2 | Différer/optimiser CMP + tierces | moyen | **TBT** ↓ | chiffres PSI + conformité RGPD |
| 3 | Précharger woff2 above-the-fold | faible | LCP-texte ↓ | identifier graisses LCP |
| 4 | Corriger `og:url` repli + type MIME | trivial | propreté | — |
| 5 | Activer AVIF | moyen | octets images ↓ | `imageavif` dispo + warmup |
| 6 | Critical CSS home (si chiffré) | élevé | FCP ↓ | rapport confirmant le blocage |
| 7 | Transitions non-layout | moyen | jank ↓ | — |

> **Garde-fou conformité.** La CMP relève du consentement RGPD : toute modification de son
> déclenchement doit être **validée** (le consentement doit rester recueilli avant tout dépôt
> non essentiel). Les correctifs 1, 3, 4 sont les plus sûrs pour une première itération.

---

## 5. Prochaine étape

1. Collez les **chiffres réels** du rapport (scores + Core Web Vitals + Opportunités/Diagnostics).
2. Je recale cet audit (faits/hypothèses) et je peux **implémenter** les correctifs 1/3/4
   (faibles risques) sur cette branche, avec validation humaine avant tout changement touchant
   la CMP ou le CSS critique.

---

*Audit statique, lecture seule — aucune modification de code applicatif n'a été effectuée.
Les métriques de rendu (LCP, TBT, CLS mesurés) restent à confirmer avec le rapport PSI réel.*
