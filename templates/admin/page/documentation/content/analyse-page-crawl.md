# Analyse de page : crawl planifié

L'analyse de page existe sous deux formes complémentaires :

- **en preview (à la demande)** : depuis le dashboard « Analyse des pages », le rendu est
  produit en interne (`forward()`), analysé puis historisé. Aucune URL publique n'est
  appelée.
- **en crawl planifié (cron)** : la commande `app:analysis-page:run` récupère les pages
  **en ligne** via HTTP et historise leur indice. C'est le pendant « live » de l'outil
  preview. Voir aussi la rubrique « PageSpeed Insights » pour la mesure Google.

## La commande

```
php bin/console app:analysis-page:run [--website=ID] [--max-urls=500] [--max-seconds=120] [--timeout=30] [--user-agent=...]
```

| Option          | Rôle                                                                 |
|-----------------|----------------------------------------------------------------------|
| `--website`     | Restreint à un site (id). Sinon, tous les sites.                     |
| `--max-urls`    | Plafond d'URLs analysées par site (défaut 500).                      |
| `--max-seconds` | Budget temps par site, arrêt gracieux (défaut 120 ; sûr en mutualisé). |
| `--timeout`     | Timeout HTTP par requête (défaut 30 s).                              |
| `--user-agent`  | En-tête User-Agent (défaut `PageAnalysis/1.0`).                      |

Source des snapshots enregistrés : `cron` (vs `manual` pour la preview).

## Construction des URLs (résolveur partagé)

Depuis la refonte, la commande **n'assemble plus l'URL elle-même** (`domaine + code`),
qui était faux pour la home et les contenus de module. Elle itère désormais par interface
(**Page**, **Newscast**, **Product**), charge les entités possédant une URL en ligne et
délègue la construction à `App\Service\Seo\PageSpeed\PublicPageUrlResolver` — la **même
logique que l'outil admin et PageSpeed**. Conséquences :

- **Page d'accueil** (`asIndex`) : crawlée à la **racine du domaine** (`https://domaine/`),
  plus de `/accueil` (qui renvoyait un 301 vers la racine).
- **Actualités / produits** : crawlés sur leur **vraie route de vue** localisée
  (ex. `/fiche-actualite/{code}`, `/fiche-produit/{code}`), construite via
  `SeoService::getAsCardUrl`, et non plus `/{code}` (qui ne résout pas la fiche).
- **Pages classiques** : `https://domaine/{code}`.

Le domaine est choisi selon la **locale** de l'URL (domaine de la locale, sinon domaine
par défaut du site).

## Déclenchement

La commande est une tâche planifiée standard (voir la rubrique « Tâches planifiées »).
Elle peut être posée sur les sites via `app:scheduler:install`. Le crawl suit le trafic
(web-cron) ou un cron externe.

## Logs

Canal d'analyse de page dans `/var/log` (comme les autres commandes planifiées). Une URL
en échec (HTTP ≥ 400, timeout, transport) est comptée en « failed » sans interrompre le
reste du crawl.

## Points d'attention

- Le crawl ne fonctionne que sur des **URLs publiques en ligne** : un environnement non
  joignable de l'extérieur ne renverra rien d'exploitable.
- Couverture : **Page, Newscast, Product** (les interfaces de l'outil d'analyse). Les
  autres types d'URL ne sont pas crawlés par cette commande.
- Budgets : `--max-urls` et `--max-seconds` protègent l'hébergement mutualisé ; sur gros
  volume, plusieurs passages se complètent au fil des exécutions.
