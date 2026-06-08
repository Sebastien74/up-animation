# Déploiement OVH mutualisé via GitHub Actions

Procédure de mise en ligne sur **hébergement OVH mutualisé (offre Performance)** par
**GitHub Actions**. Le CI build tout (PHP + assets), puis envoie le résultat en SSH/rsync.
Deux environnements pilotés par la branche : **`main` -> prod**, **`preprod` -> préprod**.

> Contexte : pas de daemon, pas de root, SSH bridé. Aucun build sur OVH (Node/yarn absents) :
> Composer **et** Webpack Encore tournent dans le CI, OVH ne fait que recevoir les fichiers
> et lancer quelques commandes Symfony.
>
> Choix structurants actés :
> - **Tout buildé dans le CI** (`composer install --no-dev` + `yarn build`), rsync complet (`vendor/` inclus).
> - **Migrations jamais automatiques** : jouées à la main en SSH quand nécessaire (section 6).
> - `.env.local` **déposé manuellement** sur le serveur, **jamais** dans le dépôt ni écrasé par rsync.
> - Base de données **déjà créée** côté OVH.

---

## 0. Vue d'ensemble

```
push main      ->  GitHub Actions (env: prod)     ->  rsync  ->  /home/abcvdcomsd/www/prod-up-animations
push preprod   ->  GitHub Actions (env: preprod)  ->  rsync  ->  /home/abcvdcomsd/www/preprod-up-animations
```

- Le workflow vit dans `.github/workflows/deploy.yml`.
- La branche détermine le **GitHub Environment** (`prod` ou `preprod`), donc le jeu de secrets utilisé.
- Le build est identique pour les deux ; seule la **cible** (host, chemin, base via `.env.local`) change.

---

## 1. Activer le SSH sur l'hébergement (une seule fois)

L'offre Performance fournit le SSH ; il faut l'activer.

- [ ] Espace client OVH -> **Hébergements** -> ton hébergement -> onglet **FTP - SSH**.
- [ ] Vérifier (ou créer) un **utilisateur SSH** (souvent le login principal, ex. `abcvdcomsd`).
- [ ] Activer l'accès **SSH** pour cet utilisateur (et non FTP seul).
- [ ] Noter les infos de connexion :
  - **Hôte** : généralement `ssh.clusterXXX.hosting.ovh.net` (le cluster est indiqué dans l'onglet).
  - **Port** : `22`.
  - **Utilisateur** : le login SSH.
- [ ] Tester en local : `ssh abcvdcomsd@ssh.clusterXXX.hosting.ovh.net`.

### Clé SSH dédiée au déploiement

Le CI ne doit pas utiliser ta clé personnelle. Générer une paire dédiée :

```bash
ssh-keygen -t ed25519 -C "github-actions-deploy" -f deploy_key -N ""
```

- [ ] Ajouter la **clé publique** (`deploy_key.pub`) sur OVH : onglet **SSH** -> autoriser
      cette clé (ou via `~/.ssh/authorized_keys` du compte si l'interface ne le permet pas).
- [ ] Conserver la **clé privée** (`deploy_key`) pour la mettre dans les secrets GitHub (section 4).
- [ ] Vérifier la connexion par clé : `ssh -i deploy_key abcvdcomsd@ssh.clusterXXX.hosting.ovh.net`.

---

## 2. Vérifier la version de PHP (CRITIQUE)

Le projet exige **PHP 8.5** (`composer.json` : `"php": ">=8.5"`, platform pinné `8.5`).
L'hébergement doit servir PHP 8.5, sinon l'application ne démarrera pas.

- [ ] Espace client OVH -> hébergement -> **Informations générales** -> **Version PHP** :
      sélectionner **8.5** (si disponible sur le cluster).
- [ ] Forcer la version aussi au niveau du compte via un fichier `.ovhconfig` à la racine
      de l'hébergement (`/home/abcvdcomsd/.ovhconfig` ou dans le dossier du site selon la conf) :
      ```
      app.engine=php
      app.engine.version=8.5
      http.firewall=none
      environment=production
      ```
- [ ] Vérifier en SSH : `php -v` (le binaire CLI doit être en 8.5 ; sinon utiliser le chemin
      explicite fourni par OVH, ex. `/usr/local/php8.5/bin/php`).

> Si le binaire CLI par défaut n'est pas en 8.5, adapter le workflow : remplacer `php` par
> le chemin complet (ex. `/usr/local/php8.5/bin/php`) dans l'étape **Post-deploy commands**
> de `deploy.yml`.

---

## 3. Configurer le Multisite (docroot vers `public/`) - SÉCURITÉ

Symfony ne doit exposer **que** `public/`. Si le docroot pointe sur la racine du projet,
tout le code source, `.env.local`, `vendor/` deviennent accessibles : **faille critique**.

- [ ] Espace client OVH -> hébergement -> onglet **Multisite**.
- [ ] Pour le **domaine prod** : « Dossier racine » = `www/prod-up-animations/public`
      (chemin relatif à la racine FTP, soit `/home/abcvdcomsd/www/prod-up-animations/public`).
- [ ] Pour la **préprod** : créer un sous-domaine (ex. `preprod.mon-domaine.fr`) avec
      « Dossier racine » = `www/preprod-up-animations/public`.
- [ ] Cocher l'option de sécurité Multisite si proposée (empêche la remontée hors du dossier).
- [ ] Après le premier déploiement, vérifier qu'aucune URL du type
      `https://mon-domaine.fr/.env.local` ou `/composer.json` n'est servie (doit donner 404).

---

## 4. Secrets et variables GitHub (par environnement)

Le workflow utilise les **GitHub Environments** : un jeu de secrets par cible.

- [ ] GitHub -> **Settings** -> **Environments** -> créer **`prod`** et **`preprod`**.
- [ ] (Recommandé) Sur l'environnement `prod` : activer **Required reviewers** pour exiger
      une validation humaine avant chaque mise en prod.

Dans **chaque** environnement, définir ces **secrets** :

| Secret             | Exemple prod                          | Exemple préprod                              |
|--------------------|---------------------------------------|----------------------------------------------|
| `OVH_SSH_HOST`     | `ssh.clusterXXX.hosting.ovh.net`      | `ssh.clusterXXX.hosting.ovh.net`             |
| `OVH_SSH_PORT`     | `22`                                  | `22`                                         |
| `OVH_SSH_USER`     | `abcvdcomsd`                          | `abcvdcomsd`                                 |
| `OVH_SSH_KEY`      | contenu de `deploy_key` (clé privée)  | idem                                         |
| `OVH_DEPLOY_PATH`  | `/home/abcvdcomsd/www/prod-up-animations` | `/home/abcvdcomsd/www/preprod-up-animations` |

Variable optionnelle (onglet **Variables** de l'environnement) :

| Variable       | Valeur par défaut | Rôle                                                       |
|----------------|-------------------|------------------------------------------------------------|
| `SYMFONY_ENV`  | `prod`            | Env Symfony des commandes post-déploiement. Laisser `prod`. |

> `OVH_SSH_KEY` = **clé privée complète** (avec les lignes `-----BEGIN/END-----`).
> Ne jamais committer cette clé ni la mettre ailleurs que dans les secrets GitHub.

---

## 5. Déposer le `.env.local` sur le serveur (une fois par environnement)

Le rsync **exclut** `.env.local` : il faut le créer manuellement sur chaque cible et ne
plus y toucher (il survit aux déploiements).

- [ ] Se connecter en SSH, aller dans le dossier cible
      (`cd /home/abcvdcomsd/www/prod-up-animations` pour la prod).
- [ ] Créer `.env.local` avec au minimum :
      ```dotenv
      APP_ENV=prod
      APP_DEBUG=0
      APP_SECRET=<chaine_aleatoire_longue_et_unique_par_env>
      DATABASE_URL="mysql://<user>:<pass>@<host_bdd_ovh>:3306/<base>?serverVersion=..."
      MAILER_DSN=...
      CRON_SECRET=<secret_long_aleatoire>
      ```
- [ ] Adapter `DATABASE_URL` aux identifiants de la **base déjà créée** (espace client OVH ->
      **Bases de données**). La préprod doit pointer une **base distincte** de la prod.
- [ ] Droits : `chmod 600 .env.local`.

> Ne jamais réutiliser le même `APP_SECRET` ni la même base entre prod et préprod.

---

## 6. Migrations base de données (manuelles, jamais automatiques)

Le déploiement **ne joue pas** les migrations. Quand un déploiement en contient :

- [ ] **Sauvegarder la base** avant toute migration (dump SQL depuis phpMyAdmin OVH ou
      `mysqldump` si disponible).
- [ ] Se connecter en SSH dans le dossier cible.
- [ ] Vérifier l'état : `php bin/console doctrine:migrations:list --env=prod`.
- [ ] Jouer : `php bin/console doctrine:migrations:migrate --no-interaction --env=prod`.
- [ ] **Toujours valider sur préprod d'abord**, puis prod.

---

## 7. Premier déploiement

- [ ] Créer la branche `preprod` à partir de `main` et la pousser :
      `git checkout -b preprod && git push -u origin preprod`.
- [ ] **Tester d'abord la préprod** : pousser sur `preprod` (ou lancer le workflow manuellement
      via **Actions** -> **Deploy to OVH** -> **Run workflow** sur la branche `preprod`).
- [ ] Suivre le run dans l'onglet **Actions** ; vérifier que les 7 étapes passent.
- [ ] Contrôler le site préprod (page d'accueil, back-office, assets `public/build` chargés).
- [ ] Jouer les migrations préprod si besoin (section 6).
- [ ] Une fois validé, déployer la prod : merge `preprod` -> `main` (fast-forward) et push de `main`.

> Astuce premier run : si tu crains la suppression de fichiers côté serveur, tester d'abord
> le rsync en **`--dry-run`** depuis ta machine (mêmes excludes que le workflow) pour voir
> ce qui serait ajouté/supprimé, avant de laisser le CI faire le vrai transfert.

---

## 8. Caches à invalider après déploiement

Le workflow lance déjà, en SSH (étape **Post-deploy commands**) :

- `cache:clear --env=prod` (cache Symfony + warmup)
- `assets:install public --env=prod` (assets des bundles vers `public/bundles`)
- `fos:js-routing:dump --env=prod` (routes JS)

À faire **manuellement** selon ce qui a changé (cf. `CLAUDE.md` « Systèmes de cache ») :

- [ ] Si des données rendues derrière les fragments `{% cache %}` / result-cache ont changé :
      bumper `website.cacheClearDate` (page admin « Pools de cache ») ou réutiliser
      `WebsiteCacheInvalidator` / `CachePoolManager`.
- [ ] Médias Liip (`public/medias/webp`), traductions (BDD + `var/cache`) si concernés.
- [ ] **OPcache** : sur mutualisé OVH, `cache:clear` ne réinitialise pas OPcache du process web.
      En général OVH recycle les workers rapidement ; en cas de code « collé », attendre quelques
      minutes ou toucher le `.ovhconfig` (un changement de conf relance le pool).

---

## 9. Cron / tâches planifiées

Le scheduler natif se déclenche au trafic (`kernel.terminate`, throttle 1 run/60 s) :
**rien de spécifique au déploiement**. Pour une cadence stricte indépendante du trafic,
voir la doc « Checklist de déploiement Scheduler » (cron CLI/HTTP via le panel OVH et
`public/cron.php` protégé par `CRON_SECRET`).

---

## 10. Rollback (si incident)

- [ ] **Code** : `git revert` du/des commit(s) sur la branche concernée, puis push
      (le CI redéploie l'état précédent).
- [ ] Alternative rapide : depuis **Actions**, relancer (« Re-run ») le dernier run **sain**.
- [ ] **Migrations** : restaurer le dump SQL pris en section 6 si une migration a posé problème.
- [ ] **`.env.local`** n'est jamais touché par le déploiement : pas de risque de l'écraser.

---

## Annexe - Exclusions rsync

Le workflow envoie tout le dépôt **sauf** :

| Exclusion          | Pourquoi                                                        |
|--------------------|-----------------------------------------------------------------|
| `.git/`, `.github/`| Inutiles en prod.                                               |
| `node_modules/`    | Buildé dans le CI, jamais nécessaire sur OVH.                    |
| `tests/`           | Pas de tests en prod.                                           |
| `.env.local`, `.env.*.local` | Secrets locaux au serveur, déposés à la main.         |
| `var/`             | Cache et logs propres au serveur (ne pas écraser/supprimer).    |
| `public/medias/`   | Médias uploadés par les utilisateurs (ne pas écraser/supprimer).|

> `rsync --delete` supprime côté serveur les fichiers absents de la source, **sauf** ceux
> couverts par une exclusion : `var/`, `public/medias/` et `.env.local` sont donc protégés.
> `public/build` est lui synchronisé normalement (les anciens assets compilés sont nettoyés).
