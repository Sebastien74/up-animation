# API PageSpeed Insights

Référence de l'intégration de l'API Google PageSpeed Insights (PSI) v5 dans l'outil
d'analyse de page (`/admin/{website}/analysis-page`). Décrit la requête envoyée, la
réponse renvoyée par Google, ce que le projet en exploite, et ce qui reste disponible.

Services concernés :

- `App\Service\Seo\PageSpeed\PageSpeedClient` — appel HTTP de l'API.
- `App\Service\Seo\PageSpeed\PageSpeedResultParser` — normalisation de la réponse.
- `App\Service\Seo\PageSpeed\PageSpeedSourceMapper` — mappe une ressource fautive au code.
- `App\Service\Seo\PageSpeed\PublicPageUrlResolver` — URL publique de la page testée.
- `App\Service\Seo\PageSpeed\QuotaGuard` — garde-fou de quota quotidien.

---

## 1. La requête

Endpoint : `GET https://www.googleapis.com/pagespeedonline/v5/runPagespeed`

| Paramètre   | Valeur envoyée                                              | Note |
|-------------|------------------------------------------------------------|------|
| `url`       | URL publique absolue (via `PublicPageUrlResolver`)         | Doit être joignable par Google (prod) |
| `key`       | `PAGESPEED_API_KEY`                                         | Clé Google Cloud restreinte |
| `strategy`  | `mobile` puis `desktop` (un appel par stratégie)           | `PAGESPEED_STRATEGIES` |
| `locale`    | locale de la page                                          | Localise titres/descriptions des audits |
| `category`  | `PERFORMANCE`, `ACCESSIBILITY`, `BEST_PRACTICES`, `SEO` (répété) | Sans ça, seul PERFORMANCE est renvoyé |

Timeout HTTP : 70 s par stratégie (un run Lighthouse réel chez Google).

Notes :

- La catégorie **PWA** n'est plus demandée : elle a été retirée de Lighthouse (≥ v12),
  PSI ne la renvoie plus.
- Quota : protégé par `QuotaGuard` (`PAGESPEED_DAILY_QUOTA`). Mesure à la demande
  uniquement (lente), jamais au chargement.

---

## 2. La réponse (structure PSI v5)

```
{
  "captchaResult": "...",
  "id": "https://.../",                 // URL finale
  "loadingExperience": { ... },         // CrUX terrain, niveau URL
  "originLoadingExperience": { ... },   // CrUX terrain, niveau origine
  "lighthouseResult": {
    "requestedUrl", "finalDisplayedUrl", "lighthouseVersion",
    "userAgent", "fetchTime",
    "environment": { hostUserAgent, networkUserAgent, benchmarkIndex },
    "runWarnings": [ ... ],
    "configSettings": { formFactor, locale, ... },
    "categories": {
      "performance": { id, title, score, auditRefs: [ {id, weight, group} ] },
      "accessibility": { ... }, "best-practices": { ... }, "seo": { ... }
    },
    "categoryGroups": { ... },
    "audits": {
      "<auditId>": {
        id, title, description, score, scoreDisplayMode, displayValue,
        numericValue, numericUnit, weight,
        "details": {
          type,                          // table | opportunity | criticalrequestchain | filmstrip | debugdata | screenshot
          overallSavingsMs, overallSavingsBytes,
          headings: [ ... ],
          items: [ { url, node, source, entity, wastedBytes, totalBytes, wastedMs, blockingTime, mainThreadTime, ... } ],
          debugData: { ... }
        }
      }
    },
    "fullPageScreenshot": { screenshot, nodes },
    "stackPacks": [ { id, title, descriptions: { auditId: advice } } ],
    "timing": { total },
    "i18n": { ... },
    "entities": [ ... ]
  },
  "analysisUTCTimestamp": "..."
}
```

CrUX (`loadingExperience` / `originLoadingExperience`) :

```
{
  "overall_category": "FAST|AVERAGE|SLOW",
  "metrics": {
    "LARGEST_CONTENTFUL_PAINT_MS":      { percentile, category, distributions: [ {min,max,proportion} ] },
    "INTERACTION_TO_NEXT_PAINT":        { ... },
    "CUMULATIVE_LAYOUT_SHIFT_SCORE":    { ... },
    "FIRST_CONTENTFUL_PAINT_MS":        { ... },
    "EXPERIMENTAL_TIME_TO_FIRST_BYTE":  { ... }
  }
}
```

---

## 3. Ce que le projet exploite

`PageSpeedResultParser::parse()` produit, par stratégie :

| Donnée | Source PSI | Statut |
|--------|------------|--------|
| Scores des 4 catégories | `categories.*.score` | ✅ |
| Core Web Vitals **labo** : LCP, TBT, CLS, FCP, Speed Index, TTI | `audits.*` (`numericValue`, `displayValue`) | ✅ |
| Core Web Vitals **terrain** : LCP, INP, CLS, FCP, TTFB | `loadingExperience` sinon `originLoadingExperience` | ✅ (page **ou** origine) |
| Audits par catégorie (via `auditRefs`), groupés par sévérité | `categories.*.auditRefs` + `audits` | ✅ |
| Par audit : titre, description, score, `displayValue`, poids, économies | `audits.*` | ✅ |
| Lien « Learn more » | extrait du markdown de `description` | ✅ |
| Ressources fautives mappées au code (URL/nœud DOM/tiers) | `audits.*.details.items` | ✅ (plafonné, voir §4) |
| Conseils spécifiques techno (WordPress, React…) | `stackPacks.descriptions` | ✅ |
| Avertissements d'exécution | `lighthouseResult.runWarnings` | ✅ |

Sévérités dérivées (logique PSI) : `fail` (< 0.5), `average` (< 0.9), `pass` (≥ 0.9),
`diagnostic` (informatif / score nul), `manual`, `na` (non applicable).

---

## 4. Ce qui n'est PAS exploité (et l'impact)

| Donnée API | Statut | Impact |
|------------|--------|--------|
| CrUX **distributions** (% bon / à améliorer / mauvais) | ❌ | Moyen — barres de répartition de pagespeed.web.dev |
| CrUX **page ET origine** simultanément | ❌ (fallback sur l'un) | Moyen — perte de la vue « origine » |
| **Captures** : `fullPageScreenshot`, filmstrip (`screenshot-thumbnails`), `final-screenshot` | ❌ | Moyen — pas d'aperçu visuel ni de surbrillance d'élément |
| **Plafond de 12 ressources/audit** (`MAX_ITEMS_PER_AUDIT`) | ⚠️ tronqué en silence | Réel — au-delà de 12 ressources, le reste n'est pas affiché, sans indicateur « +N » |
| Détails riches : `criticalrequestchain`, `debugData`, `headings` | ❌ | Faible/Moyen selon l'audit |
| Métadonnées : `lighthouseVersion`, `environment`, `timing`, `analysisUTCTimestamp`, `configSettings` | ❌ | Faible — traçabilité |
| `entities` (classification fine des tiers) | ❌ (on lit `details.items[].entity`) | Faible |

---

## 5. Recommandations (par priorité)

1. **Plafond des ressources** : lever ou, au minimum, signaler la troncature
   (« +N autres ») pour ne pas masquer des ressources fautives (règle projet : pas de
   troncage silencieux).
2. **CrUX complet** : conserver **page + origine** et exposer les **distributions**.
3. **Aperçu visuel** : filmstrip et/ou capture finale (`fullPageScreenshot`).
4. À laisser de côté (peu de valeur ici) : `debugData`, métadonnées d'environnement.

---

## 6. Configuration

| Variable                | Rôle                                                |
|-------------------------|-----------------------------------------------------|
| `PAGESPEED_ENABLED`     | Active la fonctionnalité.                            |
| `PAGESPEED_API_KEY`     | Clé Google Cloud (API PageSpeed Insights activée).  |
| `PAGESPEED_STRATEGIES`  | `mobile`, `desktop` ou les deux.                    |
| `PAGESPEED_DAILY_QUOTA` | Plafond de mesures/jour (`QuotaGuard`).             |

Le `.env` est gitignoré (géré sur le serveur) ; la clé doit être restreinte côté Google
Cloud (référent HTTP ou IP). Voir aussi la rubrique « PageSpeed Insights » du portail de
documentation back-office.
