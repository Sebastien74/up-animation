# Préchauffage du cache des pages (warmup)

Tâche planifiée qui **rejoue les URLs du site en HTTP** pour reconstruire les caches
front avant qu'une visite réelle ne paie le coût « à froid ». Évite la première page
lente après quelques jours d'inactivité, quand les caches applicatifs ont expiré.

Commande : `app:cache:warmup` - `src/Command/CacheWarmupCommand.php`.

> À ne pas confondre avec le **keep-warm OPcache** (ping externe sur `cron.php` toutes
> les 1-2 min, cf. déploiement OVH). Le keep-warm réchauffe l'OPcache du pool FPM recyclé
> après quelques **minutes** d'inactivité ; le warmup reconstruit les **caches applicatifs**
> (result-cache Doctrine, fragments Twig) qui expirent après quelques **jours**.

---

## Ce qu'elle réchauffe

Une requête HTTP réelle sur une URL front traverse tout le stack et repeuple :

- le **result-cache Doctrine** (`page-*`, `pages_action_*`, `layout_*`) ;
- les **fragments Twig `{% cache %}`** des blocs `core-action`.

C'est le seul moyen fidèle de reconstruire ces caches tels qu'un visiteur les remplit :
on rejoue donc de vraies requêtes, on ne réimplémente pas le rendu côté CLI.

---

## Fonctionnement

1. **Itère les sites.** Le scheduler n'injecte pas de contexte de site, la commande les
   résout elle-même (`Website::findAll()`). Option `--website=ID` pour en cibler un.
2. **URL de base** : `APP_PROTOCOL` + domaine `asDefault` du site (même résolution que
   `CronSchedulerService`). Site sans domaine par défaut -> ignoré (warning).
3. **URLs à la volée** : `GET {base}/sitemap.xml`, puis extraction des `<loc>` (URLs
   canoniques uniquement ; les alternates hreflang `<xhtml:link>` sont ignorés, pas de
   doublon). Aucune hydratation d'entité dans la commande : le sitemap fait le travail.
4. **Warmup** : GET concurrent (multiplexing `HttpClient`). Dès la réception des en-têtes,
   le serveur a **déjà rendu la page** (caches peuplés) ; le corps est annulé pour ne pas
   télécharger le HTML inutilement.

---

## Options et garde-fous

| Option          | Défaut | Rôle                                                         |
|-----------------|--------|--------------------------------------------------------------|
| `--website=ID`  | tous   | Restreint à un site                                          |
| `--max-urls`    | 300    | Nombre max d'URLs réchauffées par site                       |
| `--max-seconds` | 50     | Budget temps par site (arrêt gracieux, compatible mutualisé) |
| `--timeout`     | 30     | Timeout HTTP par requête (une page froide peut être lente)   |
| `--user-agent`  | `CacheWarmup/1.0` | En-tête User-Agent                                |

Le budget temps borne la durée d'exécution : essentiel en mutualisé où la commande tourne
**in-process** sur `kernel.terminate` (pas de dépassement de `max_execution_time`).
Un site injoignable (domaine non résolu, TLS local) -> log et on continue, retour `SUCCESS`.

```bash
php bin/console app:cache:warmup --max-urls=300 --max-seconds=50
php bin/console app:cache:warmup --website=1 --max-urls=50
```

---

## Planification

- Définie dans `ScheduledCommandCatalog` (source unique), cron `0 5 * * *`, **active**.
- **Nouveaux sites** : posée par les fixtures (`CommandFixtures` consomme le catalogue).
- **Sites existants** : créée automatiquement au prochain passage du web-cron via
  `CronSchedulerService::provisionMissingCommands()` (idempotent), ou immédiatement avec
  `app:scheduler:install`.
- Cadence réelle : sur site peu visité, le web-cron suit le trafic (au plus 1 run/min) ;
  le cron `0 5 * * *` s'exécute donc « dès qu'il y a du trafic après 5H ».
