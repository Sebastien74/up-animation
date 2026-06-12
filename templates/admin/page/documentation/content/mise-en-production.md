# Mise en production

Checklist maître à dérouler pour mettre le site en ligne. Elle consolide les étapes
clés et renvoie aux pages détaillées : **Déploiement OVH** (procédure complète),
**Checklist Scheduler** (tâches planifiées), **Gestion du cache** (invalidations).

> Sujet réglementé / sensible -> validation humaine. Une mise en prod n'est jamais
> automatique : on passe **toujours par la préprod d'abord**, puis `main`.

---

## A. Pré-requis (une seule fois par hébergement)

- [ ] **SSH activé** sur l'hébergement OVH + clé de déploiement dédiée autorisée
      (jamais la clé perso). Cf. *Déploiement OVH §1*.
- [ ] **PHP 8.5** servi côté OVH (interface + `.ovhconfig`), vérifié en SSH (`php -v`).
      Le projet ne démarre pas en deçà. Cf. *Déploiement OVH §2*.
- [ ] **Docroot = `public/`** (Multisite OVH) pour prod ET préprod. Vérifier qu'aucune URL
      `/.env.local`, `/composer.json` n'est servie (doit donner 404). **Sécurité critique.**
      Cf. *Déploiement OVH §3*.
- [ ] **GitHub Environments** `prod` et `preprod` créés, secrets renseignés
      (`OVH_SSH_*`, `OVH_DEPLOY_PATH`). *Required reviewers* recommandé sur `prod`.
      Cf. *Déploiement OVH §4*.
- [ ] **`.env.local` déposé manuellement** sur chaque cible (jamais commité, exclu du rsync) :
      `APP_ENV=prod`, `APP_DEBUG=0`, `APP_SECRET` unique, `DATABASE_URL` (base distincte
      prod/préprod), `MAILER_DSN`, `CRON_SECRET`. `chmod 600`. Cf. *Déploiement OVH §5*.
- [ ] **Base de données** déjà créée côté OVH (une par environnement).

---

## B. À chaque mise en production

- [ ] **Build vert en local** avant de pousser : `composer install`, `yarn build`,
      `php bin/console cache:clear`. Pas de `dd()`/`dump()` résiduel.
- [ ] **Déployer la préprod d'abord** : push sur `preprod` (ou *Run workflow* manuel).
      Suivre le run dans **Actions**, vérifier que toutes les étapes passent.
- [ ] **Migrations** (manuelles, jamais auto) si le déploiement en contient :
      sauvegarde SQL -> `doctrine:migrations:migrate --no-interaction --env=prod`,
      **sur préprod d'abord**. Cf. *Déploiement OVH §6*.
- [ ] **Contrôler la préprod** : home, back-office, login, assets `public/build` chargés,
      pages clés. Tester les parcours impactés par le déploiement.
- [ ] **Passer en prod** : merge `preprod` -> `main` en fast-forward, puis push de `main`
      (le CI redéploie). Valider l'environnement `prod` si *Required reviewers* est actif.
- [ ] **Rejouer les migrations en prod** (sauvegarde SQL préalable) si nécessaire.

---

## C. Post-déploiement (caches à invalider)

Le workflow lance déjà `cache:clear`, `assets:install`, `fos:js-routing:dump --env=prod`.
À faire **selon ce qui a changé** (cf. *Gestion du cache* et `CLAUDE.md` « Systèmes de cache ») :

- [ ] Données derrière les fragments `{% cache %}` / result-cache modifiées -> bumper
      `website.cacheClearDate` (admin « Pools de cache ») ou `WebsiteCacheInvalidator` /
      `CachePoolManager`.
- [ ] Médias Liip (`public/medias/webp`), traductions (BDD + `var/cache`) si concernés.
- [ ] **OPcache** : `cache:clear` ne le purge pas sur mutualisé ; OVH recycle les workers,
      ou toucher `.ovhconfig` pour relancer le pool.
- [ ] **Préchauffage** : laisser `app:cache:warmup` (tâche planifiée active) reconstruire les
      caches front, ou la lancer à la main. Cf. *Préchauffage du cache des pages*.

---

## D. Tâches planifiées (scheduler)

- [ ] Le scheduler natif se déclenche au trafic (`kernel.terminate`, throttle 1 run/60 s) :
      **rien de spécifique** au déploiement standard.
- [ ] Nouveau site / commandes manquantes : `app:scheduler:install --env=prod` (idempotent),
      ou attendre l'auto-provisionnement au prochain passage du web-cron.
- [ ] Trancher l'état des tâches sensibles en admin (RGPD `gdpr:remove`, etc.).
      Cadence stricte indépendante du trafic -> cron HTTP sur `cron.php` (`CRON_SECRET`).
      Cf. *Checklist Scheduler*.
- [ ] **Keep-warm** (cold-start OPcache) : ping externe régulier sur `cron.php` si la
      première page après inactivité est trop lente.

---

## E. En cas d'incident (rollback)

- [ ] **Code** : `git revert` du/des commit(s) puis push (le CI redéploie l'état précédent),
      ou *Re-run* du dernier run sain depuis **Actions**.
- [ ] **Migrations** : restaurer le dump SQL pris en section B.
- [ ] **`.env.local`** n'est jamais touché par le déploiement : aucun risque d'écrasement.
