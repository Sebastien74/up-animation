# PageSpeed Insights (analyse de page)

Le tableau de bord « Analyse des pages » (`admin_page_analysis_dashboard`) combine deux
mesures complémentaires par page :

- **l'indice statique** (`PageAnalyzer`) : analyse du HTML rendu en preview, instantanée,
  indicative, sans appel externe ;
- **PageSpeed Insights (PSI)** : une mesure **réelle Lighthouse** + les **données terrain
  CrUX** de Google, déclenchée à la demande sur la page **en ligne**.

PSI répond aussi au besoin « où ça coince dans le code » : chaque diagnostic Google est
relié à sa source dans le projet (entrypoint Webpack, média, tiers).

## Configuration

Variables d'environnement (le `.env` est gitignoré, géré sur le serveur) :

| Variable               | Rôle                                                        |
|------------------------|-------------------------------------------------------------|
| `PAGESPEED_ENABLED`    | Active la fonctionnalité (`true`/`false`).                  |
| `PAGESPEED_API_KEY`    | Clé Google Cloud avec l'API « PageSpeed Insights » activée. |
| `PAGESPEED_STRATEGIES` | Stratégies mesurées, séparées par virgule : `mobile,desktop`. |

La clé doit être **restreinte côté Google Cloud** (API PageSpeed Insights + restriction
par référent HTTP ou IP). Sans clé valide ou avec `PAGESPEED_ENABLED=false`, la colonne
et le panneau PSI disparaissent silencieusement (`PageSpeedClient::isEnabled()`).

## Contraintes

- **URL publique obligatoire** : Google crawle la vraie page en ligne. Le test ne
  fonctionne donc qu'en production (pas sur le rendu preview admin ni sur `localhost`).
  L'URL est résolue par `PublicPageUrlResolver` (domaine de la locale, sinon domaine par
  défaut), comme la commande de crawl `app:analysis-page:run`.
- **Lent et quota-limité** : un appel = un run Lighthouse réel chez Google (10 à 30 s).
  Le test est donc **à la demande uniquement** (bouton par page, « Tout PageSpeed »
  séquentiel), jamais au chargement. Chaque scan lance désormais plusieurs runs par
  stratégie (cf. « Fiabilité de la mesure ») : le quota interne reste à 1 mesure/scan,
  mais la consommation côté Google est de `SAMPLES` requêtes par stratégie.

## Architecture

| Composant                  | Rôle                                                                 |
|----------------------------|----------------------------------------------------------------------|
| `PageSpeedClient`          | Warm-up + plusieurs runs concurrents par stratégie, conserve le run de score médian, oriente la normalisation. |
| `PageSpeedResultParser`    | Normalise Lighthouse : scores, Core Web Vitals labo + terrain, audits actionnables. |
| `PageSpeedSourceMapper`    | Relie une ressource fautive au code : entrypoint Webpack, média, tiers, document. |
| `PageSpeedRecorder`        | Persiste le snapshot (`PageSpeedSnapshot`) et élague l'historique.   |
| `PublicPageUrlResolver`    | Construit l'URL publique de la page (par locale).                    |
| `PageSpeedSnapshot`        | Entité d'historique (`upa_seo_pagespeed_snapshot`), scalaires + JSON. |

La résolution « source » s'appuie sur les `public/build/*/entrypoints.json` : l'index
inverse « URL d'asset compilé → nom d'entrypoint » permet de pointer le fichier source
concerné. Penser à `yarn build` à jour pour que ce mapping soit fiable.

## Interprétation des résultats

Le panneau en tête de la page de détail affiche, par stratégie :

- les 4 scores Lighthouse (performance, accessibilité, bonnes pratiques, SEO),
- les Core Web Vitals **labo** (LCP, TBT, CLS, FCP) et **terrain** CrUX (LCP, INP, CLS)
  quand des données réelles existent,
- la liste **« Sources identifiées dans le code »** : chaque opportunité Google
  (ressources bloquantes, JS/CSS inutilisé, images non optimisées, JS hérité, tiers…)
  est dépliable et liste les ressources concernées avec leur origine projet.

Seuils de couleur (alignés sur Lighthouse) : vert ≥ 90, orange 50-89, rouge < 50.

## Fiabilité de la mesure (warm-up + médiane)

Le score Lighthouse est **non déterministe** : deux runs de la même page varient de
plusieurs points, et un run **à froid** (pool FPM recyclé, thumbnails Liip non encore
générés) est nettement sous-évalué. C'est aussi pourquoi le score du back-office peut
différer de `pagespeed.web.dev` : même moteur, mais run, stratégie (mobile/desktop) et
état de cache différents — rien à « corriger », c'est inhérent.

Pour stabiliser le chiffre affiché, `PageSpeedClient::measure()` :

1. **réchauffe** la page (un GET préalable) pour réveiller le pool FPM ;
2. lance **`SAMPLES` runs par stratégie en concurrence** (temps mural proche d'un seul
   run) et conserve le run de **score de performance médian** ;
3. retombe sur un run unique strict si tous les échantillons échouent (l'erreur API
   remonte alors normalement).

Pour comparer à `pagespeed.web.dev` : même URL, même stratégie, page déjà tiède. Le
**CLS desktop** notamment peut afficher un pic intermittent en labo (image hero dans la
fenêtre de paint ultra-rapide de Lighthouse) absent en navigateur réel — privilégier les
**données terrain CrUX** pour juger l'expérience réelle.

## Stockage et nettoyage

Chaque mesure crée un `PageSpeedSnapshot` (20 derniers conservés par page, comme
`PageAnalysis`). Les actions « Supprimer les données » et « Tout supprimer » du tableau
de bord purgent **les deux** historiques (statique + PSI).
