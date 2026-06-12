# ⚡ Audit PageSpeed Insights — front `up-animation` (home + CMS)

> Branche : `claude/pagespeed-audit-fixes-7rtf5y` — généré le 2026-06-12.
> Rapport de référence : PageSpeed Insights, profils **mobile + bureau**, locale `fr`
> (`pagespeed.web.dev/analysis/.../z8gmtijwc5`).
>
> **TL;DR — Perf/A11y/Bonnes pratiques au vert ; le seul chantier réel est le SEO (69).**

---

## 0. Scores réels du rapport *(faits — captures du 2026-06-12)*

| Catégorie | Mobile | Bureau | Lecture |
|-----------|:------:|:------:|---------|
| **Performances** | **98** 🟢 | **87** 🟠 | Bon ; anomalie bureau < mobile (cf. §3.0) |
| **Accessibilité** | **97** 🟢 | **97** 🟢 | Excellent |
| **Bonnes pratiques** | **100** 🟢 | **100** 🟢 | Parfait |
| **SEO** | **69** 🟠 | **69** 🟠 | **Point faible n°1** — identique sur les 2 profils |

> CrUX : « Aucune donnée » → pas de données terrain (trafic faible / site récent) ;
> les scores sont **en laboratoire** (variables d'un run à l'autre).

**Conséquence directe sur la priorisation :** la performance est bonne, **l'effort doit
porter sur le SEO (69)**. C'est l'inverse de l'intuition « PageSpeed = vitesse ».

---

## ⚠️ Limite de méthode

Les **scores** ci-dessus sont des faits (captures d'écran). En revanche, le **détail des
audits SEO qui échouent** n'est pas encore visible : le domaine n'est pas résolu par le
réseau de cet environnement et l'API PSI renvoie un quota à zéro. Le reste de l'audit croise
donc le code servi (`templates/front/default`, `templates/core`, `src/Twig/Content`,
`src/Service/Content`, `webpack.config.js`, `public/.htaccess`) avec les critères Lighthouse.

- **Fait vérifié** = score capturé, ou code constaté (référence `fichier:ligne`).
- **Hypothèse** = mécanisme Lighthouse plausible, à confirmer avec le détail des audits.
- **Opinion** = recommandation de priorisation.

👉 **Pour pinpointer le SEO**, dépliez dans le rapport la catégorie **SEO** et envoyez la
liste des audits en échec (les lignes rouges/orange). J'identifie alors le correctif exact.

---

## 1. Synthèse exécutive

**Opinion.** Performance, accessibilité et bonnes pratiques sont au vert ou proches : le
front implémente nativement la quasi-totalité des leviers Lighthouse (cf. §2). **Le seul
chantier réel est le SEO (69)**, identique mobile/bureau.

| # | Levier Lighthouse | État | Sévérité | Statut |
|---|-------------------|------|----------|--------|
| 1 | **SEO 69** — `is-crawlable` (page bloquée à l'indexation) | meta robots = champ admin par URL | **P1** | **Hypothèse n°1** |
| 2 | SEO — meta description / hreflang / canonical / liens | dépend du contenu CMS de la home | P1 | Hypothèse |
| 3 | Perf bureau 87 (vs mobile 98) | LCP/CSS bloquant sur la courbe desktop | P2 | Hypothèse |
| 4 | CSS framework bloquant le rendu | `<link rel=stylesheet>` synchrone (`base:158`) | P2 | Fait |
| 5 | Préchargement des fontes (woff2) | preload commenté / TODO ouvert | P3 | Fait |
| 6 | Images nouvelle génération | **WebP actif** (`ALWAYS_WEBP=true`), AVIF off | P3 | Fait |
| 7 | Bug `<meta og:url>` repli + type MIME picture | `base:111` + espace `image-loader:79` | P3 | Fait |
| 8 | Transitions sur propriétés de layout | `max-height`/`margin` animés (13×) | P3 | Fait |

**Répartition :** P0 = 0 · **P1 = 2 (SEO)** · P2 = 2 · P3 = 4.

---

## 1bis. Chantier SEO (69) — diagnostic *(le vrai sujet)*

**Problème.** SEO 69 **identique** sur mobile et bureau. En scoring Lighthouse, un 69 avec
tout le reste au vert correspond presque toujours à **un seul audit à forte pondération en
échec, indépendant du profil**. Le candidat n°1 est `is-crawlable` (« La page est bloquée
pour l'indexation »), qui pèse à lui seul ~30 % de la catégorie → fait chuter exactement
vers ~70.

**Ce que le code permet d'écarter ou de retenir :**

- ❌ **`.htaccess`** : `X-Robots-Tag: noindex` ne vise que `.doc/.docx/.pdf`
  (`public/.htaccess:142-144`) — **pas** les pages HTML. Écarté.
- ❌ **robots.txt** : `Disallow: /` uniquement si `seoStatus` désactivé
  (`src/Service/Content/RobotsService.php:46`). Sur un site de prod indexé, non déclenché.
- ⚠️ **meta robots de la page** : la valeur `index`/`noindex` provient du **champ SEO par
  URL géré en admin** (`templates/front/default/include/seo.html.twig:18-23`, `seo.index`).
  → **Hypothèse n°1 :** la page d'accueil est réglée sur **`noindex`** côté CMS
  (SEO > indexation de l'URL). Vérification : `<meta name="robots">` de la home en prod ;
  si `noindex` présent, c'est la cause unique du 69.
- ⚠️ Si SEO global désactivé (`configuration.seoStatus = false`), `seo.html.twig:80-83`
  sort `<title>[SEO Désactivé]…` + `robots = noIndex` **et** robots.txt en `Disallow: /`.
  → à vérifier en second.

**Autres audits SEO possibles (si `is-crawlable` passe)** — à confirmer avec le détail :

| Audit Lighthouse | Cause probable côté CMS | Où vérifier |
|------------------|-------------------------|-------------|
| Meta description manquante | champ « description » SEO de la home vide | admin SEO de l'URL home |
| `hreflang` invalide | alternate mal formé si multi-langue | `seo.html.twig:33-43` |
| Liens sans texte explicite | boutons icône-only / « en savoir plus » | blocs `link`, header/footer |
| Images sans `alt` | médias home/gallery sans titre renseigné | `image.html.twig:104` (alt = `intlTitle`) |
| Liens non explorables | ancres en `role=button`/JS sans `href` | menu, CTA |

**Enjeu.** Le SEO conditionne l'indexation et la visibilité organique — bénéfice client
direct, bien plus structurant qu'un point de performance.

**Dispositif.** 1) Lire le `<meta name="robots">` de la home en prod (ou le détail PSI) ;
2) si `noindex` → corriger l'indexation de l'URL en admin (action de contenu, pas de code) ;
3) sinon, traiter les audits listés un par un.

**KPI.** SEO ≥ 90 ; audit `is-crawlable` au vert ; page présente dans l'index Google
(Search Console).

> **Garde-fou.** Si la home est volontairement en `noindex` (préprod, site pas encore
> lancé), alors **le 69 est normal et attendu** — aucun correctif à faire. À confirmer
> avec vous avant toute modification d'indexation.

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

## 3. Pistes de correctifs performance (problème → enjeu → réponse → dispositif → KPI)

### [P2] Anomalie : perf bureau 87 < mobile 98 *(hypothèse)*

- **Problème.** Inhabituel — le bureau score généralement plus haut que le mobile. Deux
  explications plausibles : (a) variance de run en labo (CrUX « Aucune donnée », donc pas de
  lissage terrain), ou (b) un **élément LCP plus lourd en bureau** (image d'en-tête servie en
  grande résolution) ou le **CSS framework bloquant** (`base:158`) qui pèse davantage sur la
  courbe de notation desktop.
- **Enjeu.** Score Performance bureau ; pas de gain métier majeur (98/87 = déjà bon).
- **Réponse.** 1) **Re-tester 2-3 fois** pour écarter la variance ; 2) si stable, identifier
  l'élément LCP bureau dans le rapport (section « Largest Contentful Paint ») et vérifier la
  variante d'image servie (`<picture>` desktop) + le preload `media="(min-width:1400px)"`
  (`base:191`).
- **Dispositif.** Re-runs PSI ; inspection de l'élément LCP.
- **KPI.** Perf bureau ≥ 90 ; LCP bureau < 2,5 s.
- **Opinion.** Priorité basse : 87 reste un bon score ; ne pas sur-investir.

### [P2] Tierces parties — TBT / « Réduire le JS inutilisé » *(hypothèse)*

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

Le SEO (69) est le seul vrai chantier ; le reste est de l'optimisation marginale.

| Ordre | Chantier | Action | Effort | Impact | Pré-requis |
|-------|----------|--------|--------|--------|------------|
| **1** | **SEO** | Lire le `<meta robots>` de la home → si `noindex`, corriger l'indexation de l'URL en admin | faible | **SEO 69 → ~90+** | confirmer que la home doit être indexée |
| **2** | **SEO** | Traiter les audits SEO résiduels (description, hreflang, alt, liens) | faible/moyen | SEO ↑ | détail des audits PSI |
| 3 | Perf | Re-tester le bureau 2-3× + inspecter le LCP bureau | trivial | confirme/écarte l'anomalie | — |
| 4 | Propreté | Corriger `og:url` repli (`base:111`) + type MIME (`image-loader:79`) | trivial | bonnes pratiques | — |
| 5 | Perf | Supprimer AddThis (service Oracle arrêté en 2023) | faible | requête tierce ↓ | valider usage social |
| 6 | Perf | Précharger woff2 above-the-fold (lever les TODO) | faible | LCP-texte ↓ | identifier graisses LCP |
| 7 | Perf | Différer la CMP / tierces | moyen | TBT ↓ | conformité RGPD |
| 8 | Perf | Activer AVIF / Critical CSS home | moyen/élevé | octets/FCP ↓ | mesure préalable |

> **Garde-fou conformité.** (a) Ne pas rendre une page indexable si elle est volontairement
> en `noindex` (préprod) — à confirmer avec vous. (b) Toute modification du déclenchement de
> la CMP relève du consentement RGPD et doit être validée.

---

## 5. Prochaine étape

1. **Dépliez la catégorie SEO** du rapport et envoyez la liste des audits en échec (ou
   collez le `<meta name="robots">` de la home en prod). → j'isole la cause exacte du 69.
2. Confirmez que la page d'accueil **doit** être indexée (vs préprod volontairement masquée).
3. Je peux alors **implémenter** sur cette branche les correctifs à faible risque (4, 5, 6),
   et vous guider sur le point SEO (souvent une action de contenu en admin, pas de code).

---

*Audit statique, lecture seule — aucune modification de code applicatif n'a été effectuée.
Les métriques de rendu (LCP, TBT, CLS mesurés) restent à confirmer avec le rapport PSI réel.*
