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

## 4. Pistes SEO restantes (non couvertes ici)

À traiter dans un lot dédié — apportent surtout des **résultats enrichis** Google :

- **JSON-LD par type de contenu** : `NewsArticle` (newscast — aujourd'hui en
  microdata uniquement), `Product`/`Offer` (catalogue), `Event` (agenda),
  `FAQPage` (faq), `JobPosting` (recrutement).
- **`WebSite` + `SearchAction`** : éligibilité à la *sitelinks search box*
  (la recherche interne existe déjà).
- **`og:image:width`/`height`** : nécessite les dimensions du média (objet média,
  non disponible au niveau actuel du template).
- **Core Web Vitals** : `fetchpriority="high"` sur l'image hero, `preload` des
  polices critiques, `preconnect` vers les domaines tiers (Matomo, Axeptio).
- **Sitemap** : vérifier `lastmod`, alternates hreflang et images.
