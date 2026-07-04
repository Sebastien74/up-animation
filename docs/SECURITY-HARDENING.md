# Durcissement sécurité — infrastructure

Procédure pour appliquer les recommandations d'audit **hors code applicatif**
(le code — cookies `Secure`, Trusted Types — est déjà en place). À exécuter sur
**chaque serveur**, avec des valeurs **distinctes par environnement** (preprod ≠ prod).

Les fichiers `.env.*` ne sont pas versionnés : on édite le `.env.prod` réel du
serveur (gabarit : `.env.prod.dist`). **Ne jamais committer de secret réel.**

---

## 1. Secrets distincts par environnement

Aujourd'hui `.env.prod` ne surcharge ni `APP_SECRET` ni `SECURITY_TOKEN` : ils
retombent sur la valeur de base, donc partagée entre environnements. À corriger.

1. Générer des valeurs uniques (sur le serveur ou en local) :
   ```bash
   php -r "echo 'APP_SECRET='.bin2hex(random_bytes(16)).PHP_EOL;"
   php -r "echo 'SECURITY_TOKEN='.bin2hex(random_bytes(20)).PHP_EOL;"
   ```
2. Renseigner ces valeurs dans le `.env.prod` **du serveur** (voir `.env.prod.dist`).
3. Purger le cache : `php bin/console app:cache:clear`.

**Impacts à anticiper :**
- Changer `SECURITY_TOKEN` **modifie l'URL d'admin** (`/admin-<token>`) → prévenir
  les administrateurs et mettre à jour les favoris.
- Changer `APP_SECRET` **invalide** les sessions, cookies « remember-me » et jetons
  CSRF en cours → une reconnexion sera nécessaire.
- Refaire l'opération pour `preprod` avec des valeurs **encore différentes**.

---

## 2. Utilisateur MySQL dédié (ne plus utiliser `root`)

`.env.prod` pointe actuellement sur `root` sans mot de passe. Créer un compte
dédié, limité à la base du site :

```sql
CREATE USER '__UTILISATEUR_DB_DEDIE__'@'localhost' IDENTIFIED BY '__MOT_DE_PASSE_FORT__';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES,
      CREATE TEMPORARY TABLES, LOCK TABLES
  ON `__DB_NAME__`.* TO '__UTILISATEUR_DB_DEDIE__'@'localhost';
FLUSH PRIVILEGES;
```

- `CREATE/ALTER/DROP/INDEX/REFERENCES` ne sont nécessaires que si les **migrations
  Doctrine** sont lancées avec ce compte. Sinon, ne garder que
  `SELECT, INSERT, UPDATE, DELETE, CREATE TEMPORARY TABLES, LOCK TABLES` et exécuter
  les migrations avec un compte d'administration ponctuel.
- Mot de passe fort : `php -r "echo bin2hex(random_bytes(18)).PHP_EOL;"`.
- Renseigner `DB_USER` / `DB_PASSWORD` dans `.env.prod`, puis vérifier :
  ```bash
  php bin/console dbal:run-sql "SELECT 1"
  ```
- Sur **o2switch** (cPanel) : créer l'utilisateur et l'associer à la base via
  « Bases de données MySQL », en cochant les privilèges ci-dessus.

---

## 3. Masquer la version de PHP (`expose_php`)

Retire l'en-tête `X-Powered-By: PHP/x.y.z` (fuite de version). Symfony ne peut pas
le supprimer car il est ajouté par le SAPI.

- `php.ini` de l'environnement d'exécution (FPM/FCGID, pas seulement CLI) :
  ```ini
  expose_php = Off
  ```
- Sur **o2switch** : « MultiPHP INI Editor » (option `expose_php`) ou un
  `.user.ini` à la racine web contenant `expose_php = Off`.
- Recharger PHP, puis vérifier l'**absence** de `X-Powered-By` :
  ```bash
  curl -sI https://<domaine>/ | grep -i x-powered-by
  ```

---

## 4. Masquer la bannière Apache (`ServerTokens` / `ServerSignature`)

Réduit `Server: Apache/2.4.x (Win64) OpenSSL/... PHP/...` à `Server: Apache`.

- `httpd.conf` (configuration globale, **pas** `.htaccess`) :
  ```apache
  ServerTokens Prod
  ServerSignature Off
  ```
- Recharger Apache, puis vérifier :
  ```bash
  curl -sI https://<domaine>/ | grep -i ^server
  ```
- **o2switch** : `ServerTokens` n'est pas modifiable en `.htaccess` (directive de
  niveau serveur). `ServerSignature Off` l'est. Si la bannière complète persiste,
  demander l'ajustement au support (ou via WHM si accès reseller).

---

## 5. Vérification finale

```bash
curl -sI https://<domaine>/ | grep -iE "^server|x-powered-by|content-security-policy|strict-transport-security"
```

Attendu : pas de `X-Powered-By`, `Server` minimal, `Content-Security-Policy` et
`Strict-Transport-Security` présents.

---

## Rappel — Trusted Types (déjà activé côté code)

La CSP applique désormais `require-trusted-types-for 'script'`. **Valider sur
preprod** (console du navigateur) avant la prod : traquer les violations Trusted
Types, en particulier sur les scripts tiers qui manipulent le DOM (Axeptio, Google
Tag Manager). En cas de blocage, re-commenter la directive dans
`src/EventSubscriber/SecurityPolicySubscriber.php` (réversible).
