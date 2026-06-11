# Pack SEO — Twitter Cards, Open Graph & robots

Améliorations SEO du `<head>` front, dans la continuité de l'existant
(title, description, canonical, hreflang, Open Graph de base, JSON-LD
`Organization`, sitemap et robots dynamiques).

Principe appliqué : **automatiser quand c'est possible**, et **rendre
administrable** ce qui ne peut pas l'être automatiquement.

---

## 1. Ce qui a été ajouté

### Twitter / X Cards
Les balises `twitter:*` n'étaient pas générées alors que les données existaient
déjà dans le modèle. Elles sont désormais rendues :

- `twitter:card`, `twitter:title`, `twitter:description`, `twitter:image`,
  `twitter:site`, `twitter:creator`.

### Open Graph complété
- `og:type` **dynamique** : `article` pour une actualité (newscast), `website`
  sinon (voir §3).
- Pour les articles : `article:published_time`, `article:modified_time`,
  `article:author`.
- Image : ajout de `og:image:secure_url` et `og:image:alt`.

### Directives robots enrichies
Pour les pages indexables, la balise `robots` passe de `index` à :

```
index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1
```

(grandes vignettes et extraits non tronqués dans les résultats Google). Les pages
en `noindex` ne sont pas affectées.

---

## 2. Administrable vs automatique

| Donnée | Source | Administrable ? |
|--------|--------|------------------|
| Type de carte Twitter (`twitter:card`) | `Seo::metaOgTwitterCard` | **Oui**, par page (SEO > Twitter). Repli auto : `summary_large_image` si image, sinon `summary`. |
| Compte X/Twitter (`twitter:site`/`creator`) | `Seo::metaOgTwitterHandle` | **Oui**, par page. **Repli automatique** sur le réseau social X/Twitter du site (Informations > Réseaux sociaux) si le champ par page est vide. |
| `og:type` | Interface de la page | Automatique (newscast → `article`). |
| `og:image` | Image SEO de la page, repli sur le logo de partage du site | Automatique. |
| `article:*` | Dates de publication / mise à jour + auteur de l'entité | Automatique. |
| `robots` | Statut d'indexation de l'URL | Automatique (déjà administrable via le statut d'index de l'URL). |

Le champ « compte X/Twitter » accepte indifféremment `@compte`, `compte` ou une
URL complète (`https://x.com/compte`) : la normalisation est faite à l'affichage.

> Aucun changement de schéma de base de données n'a été nécessaire : les champs
> Twitter étaient déjà présents dans l'entité `Seo` et son formulaire d'admin
> (`SeoType`).

---

## 3. Fichiers modifiés

| Rôle | Chemin |
|------|--------|
| Rendu des balises (robots, OG, Twitter) | `templates/front/default/include/seo.html.twig` |
| Calcul du `og:type` selon l'interface | `src/Service/Content/SeoService.php` (`getOgType()`) |

---

## 4. Données structurées JSON-LD (résultats enrichis)

État des types schema.org émis par page :

| Type | Page | État |
|------|------|------|
| `Organization` | Toutes | Existant (`include/microdata.html.twig`) — logo, `sameAs`, `contactPoint`, `address` |
| `WebSite` | Toutes | **Ajouté** — nom, URL racine, `inLanguage` |
| `FAQPage` | FAQ | Existant (`actions/faq/view.html.twig`) |
| `ItemList` / `Article` | Liste d'actualités | Existant (`actions/newscast/index/microdata.html.twig`) |
| `Product` + `Offer`/`AggregateOffer` | Fiche produit catalogue | **Ajouté** |
| `JobPosting` | Offre d'emploi | **Ajouté** |
| `BreadcrumbList` | Fil d'Ariane | Existant en microdata inline (`include/breadcrumb.html.twig`) |

### Ce qui a changé

Les fiches **produit** et **offre d'emploi** émettaient jusqu'ici un JSON-LD
générique de type `Article` (via le macro `microdata.view()`), sémantiquement
incorrect. Elles utilisent désormais des macros dédiés :

- **`microdata.product(entity, seo, media)`** → `Product` avec `brand`, `sku`
  (référence), `image`, et `offers` construits à partir des **lots** :
  - un seul lot tarifé → `Offer` (prix, `availability` selon le statut vendu) ;
  - plusieurs lots tarifés → `AggregateOffer` (`lowPrice`/`highPrice`/`offerCount`).
  - Devise : `EUR` (cohérent avec l'affichage des prix du catalogue).
- **`microdata.jobPosting(entity, seo, media)`** → `JobPosting` (`title`,
  `description`, `datePosted`, `hiringOrganization`, et `jobLocation` /
  `validThrough` quand disponibles).

> Les macros reçoivent l'entité réelle (`entity.entity`) car le ViewModel
> n'expose pas tous les champs (lots, code postal, fin de publication).
> Toutes les chaînes passent par `json_encode` pour garantir un JSON valide.

Fichiers : `templates/front/default/include/macros/microdata.html.twig`
(+ branchement dans `actions/catalog/view.html.twig` et
`actions/recruitment/view.html.twig`).

---

## 4.bis Internationalisation

- **`hreflang="x-default"`** : ajouté dans les liens alternates
  (`include/seo.html.twig`), pointant vers la version de la **langue par défaut**
  du site (`configuration.locale`). Recommandé par Google pour les sites
  multilingues (rendu uniquement si plusieurs langues).

---

## 5. Préconisations NON implémentées

Recommandations volontairement écartées de ce lot, avec la raison et ce qu'il
faut pour les réaliser. Aucune n'a été faite « à moitié » : on préfère ne rien
émettre plutôt qu'un balisage faux qui serait pénalisé.

| Préconisation | Valeur SEO | Pourquoi non faite | Prérequis pour la faire |
|---|---|---|---|
| **`SearchAction`** (*sitelinks search box*) | Moyenne | La recherche s'appuie sur une page de résultats **configurable par moteur** (param `?search=`) : pas d'URL de recherche globale fiable à exposer en `target`. Un `target` erroné est signalé par Google. `WebSite` est déclaré sans `potentialAction`. | Définir/exposer une URL de résultats canonique site-wide (ex. `/recherche?search={q}`). |
| **`Event` (agenda)** | Élevée (résultats Événements) | Les seules dates disponibles (`Period.publicationStart/End`) sont des dates de **publication**, ambiguës vis-à-vis des dates d'**événement**. Un `Event` incorrect induit Google en erreur. | Clarifier le modèle agenda (date de début/fin réelle, lieu) puis macro `event()`. |
| **`NewsArticle` (actu unitaire)** | Moyenne | Pas de template de vue unitaire newscast identifié (rendu via mécanisme générique). La **liste** d'actus émet déjà `ItemList`/`Article`. | Localiser le point de rendu de l'actu unitaire et y brancher un macro `article()`. |
| **`BreadcrumbList` en JSON-LD** | Faible | Le fil d'Ariane fournit **déjà** des microdata inline valides (`itemscope`/`itemprop`) ; un JSON-LD ferait doublon. | Aucun (optionnel : migrer l'inline vers JSON-LD si on veut homogénéiser). |
| **`og:image:width` / `height`** | Faible/Moyenne | L'image OG est une URL au niveau du template ; les dimensions ne sont pas disponibles sans l'objet média. | Exposer les dimensions du média OG dans le service SEO. |
| **Core Web Vitals — `fetchpriority="high"` sur l'image LCP** | Élevée (signal de classement) | Le rendu d'images passe par un helper global `|file` ; ajouter l'attribut à la seule image hero est trop invasif/risqué sans tests. | Threader une option `fetchpriority`/`loading=eager` dans le helper d'image, ciblée hero. |
| **Préchargement des polices critiques** (`preload` woff2) | Moyenne | Les `preload` de polices sont présents mais **commentés** dans `base.html.twig` ; activer sans connaître les polices réellement critiques peut nuire (préchargements inutiles). | Identifier les 1–2 polices du *above-the-fold* puis décommenter/adapter. |
| **Sitemap — `lastmod`, hreflang, images** | Moyenne | Sitemap généré dynamiquement, non audité dans ce lot. | Vérifier `SitemapService` : `lastmod`, `xhtml:link` (alternates) et `image:image`. |

> **À noter :** `preconnect`/`dns-prefetch` vers Matomo et Axeptio sont **déjà**
> en place dans `base.html.twig` (rien à faire).

---

## 6. À valider

> ⚠️ Les templates Twig et le service PHP **n'ont pas pu être lintés/exécutés**
> dans l'environnement de développement (la console Symfony exige PHP ≥ 8.5,
> 8.4 ici ; `php -l` OK sur le PHP, équilibre des balises Twig vérifié à la revue).
> Une **validation humaine** est requise avant diffusion.

**Build / rendu (après `cache:clear`)**
- [ ] `lint:twig` passe sur `seo.html.twig`, `microdata.html.twig`,
      `macros/microdata.html.twig` (à lancer dans un environnement PHP ≥ 8.5).
- [ ] Les pages se rendent sans erreur (head complet, pas de JSON-LD cassé).

**Données structurées (test des résultats enrichis Google)**
- [ ] URL **produit** : `Product` + `offers` valides (prix EUR, `availability`).
- [ ] URL **emploi** : `JobPosting` valide (champs requis présents).
- [ ] Page courante : `Organization` + `WebSite` sans erreur, pas de doublon.
- Outil : [test des résultats enrichis Google](https://search.google.com/test/rich-results).

**Open Graph / Twitter**
- [ ] Aperçus corrects via les *debuggers* X et LinkedIn (image, titre, description).
- [ ] `og:type=article` + `article:*` uniquement sur les actualités.
- [ ] `twitter:site`/`creator` : champ par page sinon repli auto sur le réseau
      social X/Twitter du site (vérifier la normalisation `@handle`).

**Internationalisation (si multilingue)**
- [ ] `hreflang` par langue + un seul `x-default` pointant vers la langue par défaut.

**robots / Core Web Vitals**
- [ ] `robots` enrichi sur pages indexables, `noindex` préservé ailleurs.
- [ ] Core Web Vitals (PageSpeed Insights / Search Console) — référence avant/après.

---

## 7. Recherche IA (GEO / AEO) — à creuser

Visibilité dans les moteurs génératifs (ChatGPT, Claude, Perplexity, Google AI
Overviews, Copilot). Domaine émergent : **aucun fournisseur ne publie de
« classement »**, on raisonne en leviers, pas en garanties.

### Comment ces outils accèdent au contenu
- **Données d'entraînement** (figées) : non optimisables a posteriori → présence
  durable uniquement.
- **Recherche web en direct** : c'est là que le SEO agit. À noter : **ChatGPT et
  Copilot s'appuient sur l'index Bing** → l'indexation **Bing** compte autant que
  Google.

### Leviers actionnables (établis)
- [ ] **Politique des robots IA** dans `RobotsService` (robots.txt dynamique) :
      décider d'autoriser/bloquer les user-agents — `GPTBot`, `OAI-SearchBot`,
      `ChatGPT-User`, `ClaudeBot`, `anthropic-ai`, `Claude-Web`, `PerplexityBot`,
      `Google-Extended`, `CCBot`. **Arbitrage métier/juridique** (visibilité vs
      usage du contenu), pas seulement technique.
- [ ] **Indexation Bing** active (Bing Webmaster Tools) — souvent négligée.
- [ ] **Contenu citable** : format question/réponse, définitions, chiffres
      **sourcés et datés** (la FAQ / `FAQPage` est idéale ; à étendre).
- [ ] **Autorité d'entité** : cohérence nom/coordonnées sur tout le web, `sameAs`,
      présence Wikidata/annuaires, mentions tierces (presse, avis).
- [ ] **Fraîcheur** : dates de publication/mise à jour exposées
      (déjà en place via `article:modified_time`).
- [x] **Données structurées** : socle déjà posé (`Organization`, `WebSite`,
      `Product`, `JobPosting`, `FAQPage`).

### Hypothèses / émergent (prudence)
- **`llms.txt`** : standard *proposé* pour guider les LLM ; **peu/pas honoré** à
  ce jour par les grands acteurs → pari, pas garantie.
- Le **poids de chaque facteur** n'est documenté nulle part : tout chiffrage de
  « ranking IA » relève de l'estimation.

### Mesure (indirecte)
Pas de KPI direct côté IA. Suivre dans Matomo le **trafic référent** depuis
`chat.openai.com`, `perplexity.ai`, `gemini.google.com`, etc.

### Priorisation suggérée
1. Décider la politique robots IA (`RobotsService`).
2. Activer/soigner Bing.
3. Étendre le contenu en Q/R citables (FAQ).
4. Renforcer la cohérence d'entité (`sameAs`, annuaires).
