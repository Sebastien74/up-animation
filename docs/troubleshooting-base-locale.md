# Dépannage base locale — erreur FK 1452 / collation MyISAM

## Symptôme

À **chaque enregistrement** dans l'admin (création/édition), une erreur du type :

```
Une erreur est survenue : An exception occurred while executing a query:
SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row:
a foreign key constraint fails (`sydev`.`syd_layout`,
CONSTRAINT `FK_...` FOREIGN KEY (`updatedBy_id`) REFERENCES `syd_security_user` (`id`) ...)
```

Caractéristiques :
- Se produit **uniquement en local** (jamais en prod/préprod).
- L'erreur tombe **quelle que soit la valeur** écrite — même un `updatedBy_id` qui existe pourtant bien dans `syd_security_user` (ex. `id=1` / webmaster).
- Apparaît typiquement **après un réimport de la base locale**.

## Cause

Une **clé étrangère InnoDB ne peut pas être validée contre une table parente en MyISAM**.

Lors de l'import local, certaines tables ont été recréées en **MyISAM** (moteur par défaut du dump ou du serveur) au lieu d'**InnoDB**. Du coup :
- `syd_layout` (InnoDB) a une FK `updatedBy_id → syd_security_user.id`.
- `syd_security_user` est en MyISAM → InnoDB ne sait pas valider la FK → **erreur 1452 systématique**.

En prod tout est en InnoDB, d'où « ça ne marche qu'en local ».

En complément, ces mêmes tables peuvent avoir une **collation par défaut** restée en `latin1_swedish_ci` (alors que le reste de la base est en `utf8mb4_general_ci`), ce qui peut provoquer plus tard des erreurs *« Illegal mix of collations »* sur des JOINs.

## Client MySQL local

```
C:\wamp64\bin\mariadb\mariadb11.5.2\bin\mysql.exe
```
- Base : `sydev`
- Connexion : `-u root -P 3307 -h 127.0.0.1`

> Adapter le chemin si la version de MariaDB change (`C:\wamp64\bin\mariadb\<version>\bin\mysql.exe`).

## Diagnostic

### 1. Repérer les tables qui ne sont PAS en InnoDB

```sql
SELECT TABLE_NAME, ENGINE, TABLE_COLLATION
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'sydev' AND ENGINE <> 'InnoDB'
ORDER BY TABLE_NAME;
```

### 2. Repérer les tables dont la collation par défaut est restée en latin1

```sql
SELECT TABLE_NAME, TABLE_COLLATION
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'sydev' AND TABLE_COLLATION = 'latin1_swedish_ci'
ORDER BY TABLE_NAME;
```

### 3. (Important) Vérifier que les données ne sont PAS double-encodées

Avant de toucher aux collations, vérifier que les colonnes texte sont déjà en utf8mb4 et que les accents sont stockés en octets UTF‑8 corrects :

```sql
-- Le charset doit être 'utf8mb4' au niveau colonne
SELECT COLUMN_NAME, CHARACTER_SET_NAME, COLLATION_NAME
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'sydev' AND TABLE_NAME = 'syd_security_user'
  AND DATA_TYPE IN ('varchar','text','longtext','mediumtext','tinytext');

-- Un « é » correct = octets C3A9 ; CHAR_LENGTH < LENGTH sur un mot accentué
SELECT lastName, CHAR_LENGTH(lastName) AS cl, LENGTH(lastName) AS bytes, HEX(lastName) AS hx
FROM syd_security_user WHERE id = 1;
```

- Si les **colonnes sont déjà en `utf8mb4`** (cas rencontré le 2026‑05‑29) → seul le **défaut de table** est en latin1 → correctif simple (étape B ci‑dessous).
- Si une **colonne est en `latin1`** mais contient des octets UTF‑8 (`HEX` montre `C3A9` pour « é ») → **double‑encodage** : voir « Cas particulier » plus bas, NE PAS faire un `CONVERT TO` direct.

## Correctif

### A. Reconvertir toutes les tables en InnoDB (corrige l'erreur 1452)

Générer puis exécuter les `ALTER`. On désactive temporairement les vérifications FK pour éviter les problèmes d'ordre :

```sql
-- Générer la liste des ALTER
SELECT CONCAT('ALTER TABLE `', TABLE_NAME, '` ENGINE=InnoDB;')
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'sydev' AND ENGINE = 'MyISAM'
ORDER BY TABLE_NAME;
```

Puis, en encadrant l'exécution :

```sql
SET FOREIGN_KEY_CHECKS = 0;
-- ... coller ici tous les ALTER TABLE ... ENGINE=InnoDB; ...
SET FOREIGN_KEY_CHECKS = 1;
```

### B. Aligner la collation par défaut des tables en utf8mb4 (métadonnée seule)

> ⚠️ Utiliser `DEFAULT CHARACTER SET`, **PAS** `CONVERT TO CHARACTER SET`.
> `CONVERT TO` réécrirait toutes les colonnes en `utf8mb4_general_ci` et **écraserait les colonnes volontairement en `utf8mb4_bin`** (tokens, clés secrètes). `DEFAULT CHARACTER SET` ne change que le défaut de la table, sans toucher aux colonnes ni aux données.

```sql
-- Générer la liste
SELECT CONCAT('ALTER TABLE `', TABLE_NAME, '` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;')
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'sydev' AND TABLE_COLLATION = 'latin1_swedish_ci'
ORDER BY TABLE_NAME;
```

Puis exécuter les `ALTER` générés.

## Vérification

```sql
-- Doit renvoyer 0 et 0
SELECT COUNT(*) AS myisam_restantes
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'sydev' AND ENGINE <> 'InnoDB' AND TABLE_TYPE = 'BASE TABLE';

SELECT COUNT(*) AS latin1_restantes
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'sydev' AND TABLE_COLLATION = 'latin1_swedish_ci';

-- Données intactes : 'Agence Félix', hx inchangé (…C3A9…)
SELECT lastName, CHAR_LENGTH(lastName) AS cl, HEX(lastName) AS hx
FROM syd_security_user WHERE id = 1;
```

Enfin, refaire un enregistrement dans l'admin : il doit aboutir et mettre à jour `updatedBy_id` + `updatedAt` sur la ligne concernée.

## Cas particulier : colonnes réellement en latin1 contenant de l'UTF‑8

Si l'étape de diagnostic #3 montre une **colonne en `latin1`** avec des octets UTF‑8 (`HEX` = `C3A9` pour « é »), un `CONVERT TO CHARACTER SET utf8mb4` direct **corromprait** les accents (« é » → « Ã© »). Il faut préserver les octets via un intermédiaire binaire :

```sql
-- Par table concernée :
ALTER TABLE <table> CONVERT TO CHARACTER SET binary;                                 -- VARCHAR/TEXT -> VARBINARY/BLOB, octets préservés
ALTER TABLE <table> CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;     -- réinterprète les octets comme utf8mb4
```

> À ne faire que si les colonnes sont effectivement en latin1 (et après sauvegarde). Ce n'était PAS le cas le 2026‑05‑29 (colonnes déjà utf8mb4) — l'étape B a suffi.

## Prévention (éviter la récidive au prochain import)

- Forcer le moteur et le charset au dump/réimport, par ex. :
  ```
  mysqldump --default-character-set=utf8mb4 ... > dump.sql
  ```
- S'assurer que le serveur MariaDB local utilise InnoDB par défaut
  (`default-storage-engine = InnoDB` dans `my.ini`) et `utf8mb4`.
- Après chaque réimport, relancer le diagnostic #1 / #2 pour contrôle.

---

_Référence : incident résolu le 2026‑05‑29 (28 tables passées de MyISAM/latin1 à InnoDB/utf8mb4)._
