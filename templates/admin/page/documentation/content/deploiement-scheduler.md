# Checklist de déploiement preprod / prod - Scheduler natif

Déploiement des deux features scheduler (auto-déverrouillage + installeur généralisé)
et de la mécanique cron native déclenchée au trafic.

Commits concernés sur `main` :
- `b92fc93f` auto-déverrouillage des verrous orphelins
- `c520f464` installeur généralisé `app:scheduler:install` + catalogue source unique

> Rappel : hébergement mutualisé, pas de daemon. Le cron est déclenché par le trafic
> via `kernel.terminate` (throttle 1 run / 60 s). `public/cron.php` reste un filet externe.
> Base réelle = **`up_animations`** (pluriel).
> Adapter `--env=prod` au nom d'environnement réel de chaque cible.

---

## 0. Avant de commencer (les deux environnements)

- [ ] Travailler **d'abord sur preprod**, valider, **puis** prod.
- [ ] Sauvegarde base de données de l'environnement cible (dump SQL) avant migrations.
- [ ] Confirmer que `main` est poussé sur `origin` et que la cible va bien tirer ce commit.
- [ ] Vérifier la fenêtre de déploiement (les commandes destructives `gdpr:remove`
      tournent à 1h du matin - éviter de déployer juste avant si doute).

---

## 1. Déploiement code + dépendances (preprod puis prod)

- [ ] Récupérer le code : `git pull` (ou procédure de déploiement habituelle).
- [ ] Dépendances PHP : `composer install --no-dev --optimize-autoloader`
      (vérifie au passage les extensions Twig mail : `cssinliner-extra`, `inky-extra`,
      `markdown-extra`).
- [ ] Assets (si le front a changé) : `yarn install && yarn build`.
- [ ] Vider le cache : `php bin/console cache:clear --env=prod`.
- [ ] Routing JS si besoin : `php bin/console fos:js-routing:dump --env=prod`.

## 2. Migrations base de données

- [ ] Vérifier l'état : `php bin/console doctrine:migrations:list --env=prod`.
- [ ] Jouer les migrations : `php bin/console doctrine:migrations:migrate --no-interaction --env=prod`.
      En attente sur preprod/prod :
  - `Version20260529064634` - `tokenExpiresAt` sur `api_instagram`
  - `Version20260529065925` - `refreshToken` + expiries sur `api_tiktok`
- [ ] Confirmer que les deux apparaissent en `migrated`.

## 3. Configuration environnement

- [ ] **Web-cron actif par défaut** : `SCHEDULER_WEB_CRON_ENABLED` a un défaut conteneur
      `true` (`config/services.yaml`), donc **rien à faire pour activer**.
- [ ] **Pour couper** le web-cron sur un env précis uniquement : ajouter
      `SCHEDULER_WEB_CRON_ENABLED=false` dans le `.env.local` (ou `.env`) de cet env.
- [ ] **Fuseau horaire** : l'app force `Europe/Paris` (`App\Kernel`). Vérifier qu'aucun
      `php.ini` / variable d'env de la cible ne réintroduit un décalage UTC.
      Ne pas toucher à la pipeline analytics (UTC volontaire).

## 4. Installer les tâches planifiées sur les sites existants

- [ ] **Sites existants** (n'ont pas les tâches IG/TikTok ni la rotation cache en base) :
      `php bin/console app:scheduler:install --env=prod`
      Pose les **5 voulues** actives (`app:analytics:rollup`, `app:analytics:purge`,
      `cache:pool:prune`, `app:instagram:refresh-token`, `app:tiktok:refresh-token`) sur
      **tous** les sites. Idempotent (skip ce qui existe déjà).
- [ ] Variantes utiles :
  - Un seul site : `app:scheduler:install --website=ID --env=prod`
  - Toutes les définitions (10, avec leur état d'origine) : `--all`
  - Créées inactives (activation manuelle en admin ensuite) : `--disabled`
- [ ] **`app:cache:reclaim` (grand ménage hebdomadaire)** : inactive par défaut, donc
      **absente** de l'install standard. Pour qu'elle **existe en base et soit activable
      en admin** sur un site existant, l'installer une fois avec `--all` (elle est posée
      **inactive**, son état d'origine) : `app:scheduler:install --all --env=prod`
      (ou `--website=ID --all`). Ne l'activer ensuite en admin que si la pression disque
      le justifie (vague de cache-miss assumée, cf. section 5).
- [ ] **Nouveaux sites** : rien à faire, les fixtures (`CommandFixtures` via
      `ScheduledCommandCatalog`) posent **toutes** les définitions, dont `app:cache:reclaim`
      (inactive) ; les 5 voulues sont déjà actives.

## 5. Décisions de contenu à trancher AVANT prod

- [ ] **`gdpr:remove`** (destructive, `00 1 * * *`) : confirmer qu'elle doit être active
      en prod, sinon la laisser inactive en admin. Sujet réglementé (RGPD) -> validation
      humaine recommandée.
- [ ] **`security:reset:token`** (`* * * * *`) : confirmer l'activation (tourne au fil
      du trafic).
- [ ] `security:password:expire` et `app:feed:sync` (social wall) : inactives par défaut,
      activer en admin seulement si besoin sur l'env.
- [ ] **`cache:pool:prune`** (`45 3 * * *`, active) : rien à trancher, **non destructive**.
      C'est la « rotation » du cache : supprime uniquement les entrées **expirées** (TTL
      dépassé) des pools filesystem (`cache.app`, `doctrine.result_cache_pool`, ...). Ne
      vide jamais d'entrée valide, in-process (pas de `shell_exec`), donc sûre en mutualisé.
      Limite : les clés **versionnées sans TTL** (fragments `{% cache %}`, result-cache
      `page-*`/`layout-*` indexés par `cacheClearDate`) ne sont pas expirées au sens TTL ;
      leur reclaim reste géré par les invalidations existantes (`WebsiteCacheInvalidator`,
      `CachePoolManager`, bump `cacheClearDate`).
- [ ] **`app:cache:reclaim`** (`0 4 * * 0`, **inactive par défaut**) : grand ménage
      hebdomadaire qui vide `cache.app` pour récupérer ces orphelins versionnés sans TTL.
      Vide aussi les entrées valides -> **vague de cache-miss** (rebuild paresseux du
      result-cache et des fragments). Volontairement **inactive** : à activer en admin
      site par site **seulement si la pression disque le justifie**. Dimanche 4H (trafic
      le plus bas) et déclenchée sur `kernel.terminate` (après la réponse), donc la requête
      qui déclenche le cron n'est pas ralentie. Pas d'impact perf tant qu'elle reste inactive.

---

## 6. Vérifications post-déploiement

- [ ] **Dashboard admin** (`ROLE_ADMIN`) > section « Tâches planifiées » : statut des
      commandes, aucune verrouillée à tort, dates dernière/prochaine exécution cohérentes
      (heure Paris).
- [ ] **Générer du trafic** sur le site (ouvrir quelques pages) pour déclencher le
      heartbeat, puis vérifier que le web-cron tourne.
- [ ] **Logs** dans `var/log/` :
  - `scheduler.log` (heartbeat)
  - `cron-scheduler.log` (moteur) - chercher `[START]` / `[CLOSE]` et
    `[UNLOCK]` (relâche de verrou orphelin si applicable)
  - un log par commande (ex: `cron-app-analytics-rollup.log`)
- [ ] **Pas de token en clair** dans les logs d'échec de refresh (IG/TikTok/Facebook).
- [ ] **Alerte mail** : provoquer ou attendre un cas verrouillé et vérifier le rendu de
      `NotificationEmail` (un échec d'alerte ne bloque plus le cron : try/catch + log).
- [ ] Confirmer que `app:instagram:refresh-token` et `app:tiktok:refresh-token`
      s'exécutent sans erreur (codes retour `0` dans `lastReturnCode` au dashboard).

---

## 6 bis. Cron système (optionnel - précision à la minute)

Le web-cron dépend du trafic (une tâche `* * * * *` ne tourne que s'il y a des visites,
au plus 1x/min). Pour une exécution à heure stricte sur un site peu visité, ajouter un
cron système via le panel de l'hébergeur mutualisé. Deux cas selon l'offre :

- **Cron CLI** (recommandé si disponible) : exécute directement la commande, indépendant
  du trafic, aucun `shell_exec`.
  ```
  * * * * * /usr/bin/php /chemin/absolu/vers/bin/console scheduler:execute >/dev/null 2>&1
  ```
  Adapter le chemin PHP (souvent fourni par l'hébergeur, ex: `/usr/local/bin/php8.5`).

- **Cron HTTP** (si le panel ne fait que wget/curl sur une URL) : passe par `public/cron.php`,
  qui exécute `scheduler:execute` **in-process** (pas de `shell_exec`, compatible mutualisé)
  et est protégé par secret/IP.
  ```
  * * * * * curl -s "https://ton-site/cron.php?secret=LE_SECRET" >/dev/null 2>&1
  ```
  Pré-requis dans le `.env`/`.env.local` de l'env :
  - `CRON_SECRET=...` (secret long et aléatoire) - passé en `?secret=` ou header `X-Cron-Secret`.
  - `CRON_ALLOWED_IPS=...` (optionnel) - allowlist d'IP autorisées sans secret.
  - Sans secret ni IP autorisée -> réponse `403 Forbidden`.

> Coexistence sans risque avec le web-cron déclenché au trafic : les locks pessimistes
> empêchent toute double exécution d'une même commande.
> Note : en cron HTTP, la requête reste ouverte le temps des commandes dues ; pour des
> commandes longues, vérifier `max_execution_time` côté hébergeur.

---

## 7. Nettoyage (une fois preprod ET prod validées)

- [ ] **CronLab** (service externe) : peut être retiré de la config. `public/cron.php`
      reste comme filet externe optionnel (appelle `scheduler:execute` directement,
      coexistence sans risque grâce aux locks).
- [ ] Confirmer que l'ancienne base `up_animation` (singulier) est obsolète, puis la
      supprimer pour éviter toute confusion.

---

## 8. Rollback (si incident)

- [ ] Couper le web-cron en urgence : `SCHEDULER_WEB_CRON_ENABLED=false` dans `.env.local`
      de la cible + `cache:clear`. Coupe le déclenchement au trafic sans toucher au reste.
- [ ] Désactiver une tâche précise : passer `active=false` en admin (effet immédiat,
      pas de redéploiement).
- [ ] Verrou bloqué : l'auto-déverrouillage relâche tout verrou de plus de 1h au prochain
      cycle. Pour forcer immédiatement, repasser `locked=false` en admin sur la tâche.
- [ ] Code : `git revert` du ou des commits concernés, puis redéploiement.
- [ ] Migrations : restaurer le dump SQL pris en étape 0 si une migration a posé problème.
