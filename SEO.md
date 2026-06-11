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

## 6. Validation après déploiement

Après mise en service (cache vidé) :

- Tester une URL **produit** et une URL **emploi** via le
  [test des résultats enrichis Google](https://search.google.com/test/rich-results).
- Vérifier les balises **Open Graph / Twitter** via les *debuggers* X et LinkedIn.
- Contrôler les **Core Web Vitals** (PageSpeed Insights / Search Console).
