# Guide de Connexion TikTok Feed

Ce document détaille la procédure pour connecter un compte TikTok au site et afficher son flux (feed).

## 1. Création de l'application TikTok pour Développeurs

1. Aller sur [TikTok for Developers](https://developers.tiktok.com/) et se connecter avec un compte TikTok ayant accès au compte officiel de la marque/agence (idéalement un compte dédié, pas un compte personnel).
2. Accepter la **TikTok Developer Terms of Service** si elle est demandée à la première connexion.
3. Dans la barre supérieure, cliquer sur **Manage apps**.
4. Cliquer sur **Connect an app** (en haut à droite) pour ouvrir le formulaire de création.
5. Remplir les informations obligatoires :
   - **App name** : nom commercial visible des utilisateurs sur l'écran d'autorisation OAuth.
   - **App icon** : carré ≥ 50×50 px, PNG ou JPG.
   - **Category** : choisir la catégorie correspondant à l'usage (Events, Marketing, Media, etc.).
   - **App description** : description courte (visible côté utilisateur final lors de l'autorisation).
   - **Terms of Service URL** et **Privacy Policy URL** : URLs publiques **HTTPS** accessibles sur le site cible.
   - **Platform** : sélectionner **Web** et renseigner l'URL du site (`https://[votre-domaine.com]`).
6. Valider la création. L'application passe en statut **Staging** (sandbox) : elle est utilisable immédiatement, mais uniquement avec les comptes ajoutés comme **Targeted users** dans la console.
7. Dans le tableau de bord de l'app, ouvrir la section **Add products** et ajouter :
   - **Login Kit** — indispensable pour le flux OAuth (récupération du token utilisateur).
   - **Display API** — indispensable pour lire la liste des vidéos publiées par l'utilisateur.

   > Sur les anciennes versions de la console, ces deux produits étaient regroupés sous le nom **Video Kit**. TikTok les a scindés ; si la console n'affiche plus **Video Kit**, c'est normal — utiliser **Login Kit + Display API**.
8. Ouvrir **Login Kit → Scopes** et cocher au minimum :
   - `user.info.basic` — lecture du profil utilisateur (avatar, pseudo).
   - `video.list` — lecture des vidéos publiées (nécessaire pour le feed).
9. Récupérer le **Client Key** et le **Client Secret** dans le bloc **Basic information** du tableau de bord (procédure détaillée au § 2 — Option A, point 2).
10. **Mode Staging vs Production** : tant que l'app reste en **Staging**, seuls les comptes ajoutés dans **Manage apps → [App] → Targeted users** peuvent se connecter. Pour ouvrir l'app à tous les utilisateurs publics, cliquer sur **Apply** et soumettre l'app à la **Review TikTok** :
    - Délai : compter **1 à 2 semaines** de validation.
    - Pièces à fournir : démonstration vidéo du parcours OAuth complet, justification des scopes demandés, URL des CGU et politique de confidentialité.

## 2. Génération du Token d'accès (Access Token)

Vous avez deux options pour générer le token :

### Option A : Connexion automatique (Recommandée)

1. Dans l'administration du site, allez dans **Configuration du site** > onglet **TikTok**.
2. Récupérez votre **Client Key** et votre **Client Secret** sur TikTok for Developers, puis saisissez-les dans le formulaire admin :
   1. Connectez-vous sur [https://developers.tiktok.com/](https://developers.tiktok.com/) avec le compte TikTok propriétaire de l'application.
   2. Dans la barre supérieure, cliquez sur **Manage apps**.
   3. Cliquez sur le nom de l'application créée à l'étape 1 pour ouvrir son tableau de bord.
   4. Faites défiler jusqu'au bloc **Basic information** (ou **App information**) :
      - **Client Key** : identifiant public de l'app, affiché en clair. Cliquez sur l'icône de copie à droite de la valeur.
      - **Client Secret** : valeur confidentielle, masquée par défaut. Cliquez sur **Show** (ou l'icône œil) pour la révéler, puis copiez-la.
   5. Si le Client Secret a été perdu, utilisez le bouton **Reset** à côté du champ : un nouveau secret est généré et l'ancien est invalidé immédiatement (toutes les intégrations qui l'utilisent doivent être mises à jour).
   6. Collez les deux valeurs dans les champs correspondants de l'onglet TikTok (admin Symfony).

   > Le Client Secret ne doit jamais être committé en clair ni partagé par email/Slack. Il n'est lu qu'au moment du flux OAuth puis stocké en base via le formulaire d'administration.
3. Enregistrez la configuration.
4. Un bouton **Connecter mon compte TikTok** apparaît. Cliquez dessus.
5. Autorisez l'accès sur la page TikTok qui s'ouvre.
6. Le token sera automatiquement récupéré et enregistré.

### Option B : Génération manuelle

Si vous avez déjà un token valide, vous pouvez le saisir directement dans le champ **API token (Manuel)**.

## 3. Configuration de l'URL de retour (Callback)

Pour l'**Option A**, TikTok n'accepte une demande d'autorisation que si l'URL de redirection est **déclarée à l'identique près** dans la console développeur.

1. Ouvrir la console : [https://developers.tiktok.com/](https://developers.tiktok.com/) → **Manage apps** → cliquer sur le nom de l'application créée à l'étape 1.
2. Dans le menu latéral gauche, ouvrir **Login Kit** (produit ajouté à l'étape 1.7).
3. Localiser le bloc **Redirect URI** (selon la version de la console, l'intitulé peut être **Web → Redirect URI** ou **Platforms → Web → Redirect URI**).
4. Cliquer sur **Add URI** (ou **+ Add**).
5. Saisir l'URL **exacte** : `https://[votre-domaine.com]/tiktok/callback`
   - Schéma **HTTPS obligatoire** (TikTok refuse HTTP, y compris en Staging sauf pour `localhost`).
   - **Pas de slash final** (`/tiktok/callback`, jamais `/tiktok/callback/`).
   - La **casse compte** : utiliser exactement l'URL générée par le contrôleur Symfony (route nommée `tiktok_auth_callback` dans `src/Controller/Front/Action/Feed/TikTokAuthController.php`).
6. Cliquer sur **Save** en bas de la page.

   > La modification peut prendre quelques secondes à se propager côté TikTok. En cas d'erreur `redirect_uri_mismatch` au moment de l'autorisation, vérifier que l'URL enregistrée est **strictement identique** à celle envoyée par le site (espaces, casse, slash final, encodage des paramètres).

7. **(Optionnel — environnements multiples)** Ajouter autant d'URIs supplémentaires que nécessaire pour vos environnements :
   - Recette / staging : `https://[domaine-staging]/tiktok/callback`
   - Local (uniquement si le compte TikTok est en Targeted users + app en Staging) : `https://localhost/tiktok/callback` ou via un tunnel HTTPS type ngrok.

   TikTok accepte plusieurs Redirect URIs simultanément ; la sélection se fait via le paramètre `redirect_uri` envoyé lors de la requête OAuth.

## 4. Configuration dans le CMS

### 4.1 Accès au formulaire

1. Se connecter à l'administration du site.
2. Ouvrir **Configuration du site** (icône engrenage de la sidebar).
3. Cliquer sur l'onglet **TikTok** (groupe API).

   > L'onglet n'apparaît que si la relation API ↔ TikTok est initialisée en base. Vérification rapide :
   > ```sql
   > SELECT id FROM api WHERE tiktok_id IS NOT NULL;
   > ```

### 4.2 Champs du formulaire

Le formulaire est défini par `App\Form\Type\Core\Website\TikTokType` (block prefix `tiktok_api`).

| Champ         | Type   | Label admin                    | Obligatoire      | Colonne `api_tiktok` | Largeur grille |
|---------------|--------|--------------------------------|------------------|----------------------|----------------|
| `appId`       | Texte  | **Client Key / App ID**        | Non*             | `app_id`             | `col-lg-6`     |
| `appSecret`   | Texte  | **Client Secret / App Secret** | Non*             | `app_secret`         | `col-lg-6`     |
| `accessToken` | Texte  | **API token (Manuel)**         | Non              | `access_token`       | `col-lg-8`     |
| `nbrItems`    | Entier | **Nombre de vidéos**           | Oui (`NotBlank`) | `nbr_items` (déf. 7) | `col-lg-4`     |

> *`appId` et `appSecret` sont marqués `required: false` côté form, mais ils sont **indispensables** pour que le bouton **Connecter mon compte TikTok** apparaisse (cf. `TikTokType::buildView()`) et pour que l'échange OAuth aboutisse.

### 4.3 Comportement du formulaire

- **Bouton "Connecter mon compte TikTok"** : généré uniquement si `appId` est déjà persisté en base. Workflow standard : remplir `appId` + `appSecret` → **enregistrer une première fois** → le bouton apparaît au rechargement → cliquer pour lancer l'OAuth (§ 2 Option A).
- **Champ "API token (Manuel)"** : à utiliser uniquement si un token a été généré par un autre canal (Option B). Sinon laisser vide ; le callback OAuth (`TikTokAuthController`) écrasera la valeur automatiquement.
- **Nombre de vidéos** : transmis tel quel à l'API TikTok via le paramètre `max_count` de `video/list/` (cf. `TikTokService::getFeed()`). Plafond TikTok côté API = **20** par requête ; au-delà, prévoir une pagination via `cursor`.
- **Stockage** : tous les champs sont stockés en clair dans la table `api_tiktok` (VARCHAR 255). L'accès admin doit être protégé en conséquence — pas de chiffrement applicatif sur le Client Secret.

### 4.4 Enregistrement

1. Cliquer sur **Enregistrer** en bas de page.
2. Le form manager standard persiste l'entité `TikTok` (relation `OneToOne` avec `Api`, cascade `persist`/`remove`).
3. Le rendu front n'utilise **plus** d'appel API au moment de la requête. Le sync DB est géré par `app:feed:sync` (cf. § 5).

## 5. Utilisation technique

> **Le rendu front ne fait plus aucun appel à l'API TikTok.**
> Les vidéos et leurs cover images sont persistées en base (`api_feed_post`) + sur disque (`/public/feed/medias/tiktok/{externalId}/thumbnail.{ext}`) par la commande `app:feed:sync`. Si le token expire ou si l'API tombe, le feed reste **inchangé** côté visiteur.

### 5.1 Flux

```
[cron] → app:feed:sync --provider=tiktok
            │
            ├── TikTokFeedFetcher → TikTokService::getFeed() → API TikTok
            ├── FeedSyncService :
            │     ├── upsert FeedPost (provider=tiktok, externalId=…)
            │     ├── FeedMediaDownloader → /public/feed/medias/tiktok/{externalId}/thumbnail.{ext}
            │     └── posts en DB absents de la réponse API → removed_at = NOW()
            └── flush

[visiteur] → render(controller('…TikTokController::index'))
              └── FeedPostRepository::findActiveByProvider('tiktok', nbrItems) → DB
                    └── rendu Twig avec asset(post.thumbnailLocalPath)
```

> ⚠️ La Display API TikTok n'expose **pas** la vidéo brute, seulement la cover image et l'URL de partage (`share_url`). Le rendu local est donc une miniature cliquable qui ouvre TikTok en lecture native via `post.permalink`. Aucun `mediaLocalPath` côté TikTok.

### 5.2 Intégration dans un template Twig

```twig
{{ render(controller('App\\Controller\\Front\\Action\\Feed\\TikTokController::index')) }}
```

À placer dans n'importe quel template (page Twig, fragment, layout). Si aucun `FeedPost` actif n'existe pour le provider `tiktok`, le contrôleur retourne une `Response` vide — aucun bloc n'est affiché et aucune erreur n'est levée.

Variables exposées au template :
- `tiktok` : `TikTokModel` (config admin — Client Key, nbrItems…).
- `feed` : `FeedPost[]` (entités, triées par `publishedAt DESC`, limitées à `nbrItems`).

Propriétés FeedPost utiles côté Twig :
- `post.permalink` → `share_url` TikTok (lien sortant).
- `post.thumbnailLocalPath` → chemin relatif depuis `/public`, à passer à `asset()`.
- `post.caption` → `video_description` (ou `title` en fallback).
- `post.publishedAt` → `DateTimeImmutable` (depuis `create_time`).
- `post.duration` → durée en secondes.

### 5.3 Synchronisation — déclencheurs

Trois manières de déclencher un sync :

1. **Auto-sync au chargement d'une page** (par défaut). Quand `TikTokController::index` est rendu, `FeedAutoSyncService::scheduleIfStale('tiktok')` est appelé :
   - si le verrou cache `feed_sync_lock_tiktok` est encore actif (TTL 12 h), rien ne se passe ;
   - sinon le provider est mis en file et `FeedSyncService::sync('tiktok')` s'exécute dans `kernel.terminate` (après envoi de la réponse au navigateur, **zéro impact sur le TTFB**).
   - Résultat : **2 syncs/jour max par provider**, automatiques, sans cron à configurer.
2. **Bouton "Synchroniser maintenant"** sur le dashboard admin. Force un sync immédiat synchrone via `FeedSyncController::sync()` (route `admin_feed_sync`, méthode POST, CSRF protégé). Vide le verrou cache avant d'appeler `FeedSyncService::sync()`.
3. **Commande CLI** pour les ops manuelles, debug, ou tâches planifiées éventuelles :
   ```bash
   php bin/console app:feed:sync --provider=tiktok
   php bin/console app:feed:sync                  # tous les providers
   php bin/console app:feed:sync --force          # re-télécharger les cover images déjà présentes
   ```

> Le verrou cache 12 h vit dans le cache applicatif standard. Pour le purger sans passer par le bouton admin : `bin/console cache:clear` (le verrou est perdu, prochain page-load déclenche un sync).

### 5.4 Endpoints TikTok utilisés (par la commande de sync uniquement)

| Étape                | Endpoint                                      | Méthode                        |
|----------------------|-----------------------------------------------|--------------------------------|
| Autorisation         | `https://www.tiktok.com/v2/auth/authorize/`   | `GET` (redirection navigateur) |
| Échange code → token | `https://open.tiktokapis.com/v2/oauth/token/` | `POST`                         |
| Récupération vidéos  | `https://open.tiktokapis.com/v2/video/list/`  | `POST` (Bearer token)          |

Scopes demandés à l'autorisation : `user.info.basic`, `video.list` (`TikTokService::getAuthUrl()`).

Champs récupérés par `video/list/` : `id`, `create_time`, `cover_image_url`, `share_url`, `video_description`, `duration`, `title`.

### 5.5 Personnalisation du rendu

Le template chargé est résolu dynamiquement à partir de la configuration du site :

```
templates/front/{template}/actions/feed/tiktok/html.twig
```

où `{template}` est la valeur de `website.configuration.template` (par défaut : `default`).

Pour créer un rendu spécifique à un template métier (ex. `events`), copier `templates/front/default/actions/feed/tiktok/html.twig` vers `templates/front/events/actions/feed/tiktok/html.twig` et adapter.

Le rendu par défaut s'appuie sur **Bootstrap Grid** (`row g-3`, `col-12 col-md-4 col-lg-3`) et le composant **card** Bootstrap — pas de CSS custom requis.

### 5.6 Récapitulatif des fichiers

| Élément                   | Emplacement                                                 |
|---------------------------|-------------------------------------------------------------|
| Entité config             | `src/Entity/Api/TikTok.php` (table `api_tiktok`)            |
| Entité posts persistés    | `src/Entity/Api/FeedPost.php` (table `api_feed_post`)       |
| Repo posts persistés      | `src/Repository/Api/FeedPostRepository.php`                 |
| Model config              | `src/Model/Api/TikTokModel.php`                             |
| Service API live          | `src/Service/Content/TikTokService.php`                     |
| Fetcher (sync only)       | `src/Service/Content/Feed/TikTokFeedFetcher.php`            |
| Orchestrateur sync        | `src/Service/Content/Feed/FeedSyncService.php`              |
| Auto-sync paresseuse      | `src/Service/Content/Feed/FeedAutoSyncService.php`          |
| Listener kernel.terminate | `src/EventSubscriber/FeedAutoSyncTerminateSubscriber.php`   |
| Téléchargement médias     | `src/Service/Content/Feed/FeedMediaDownloader.php`          |
| Commande de sync          | `src/Command/FeedSyncCommand.php` (`app:feed:sync`)         |
| Sync admin (bouton)       | `src/Controller/Admin/Core/FeedSyncController.php`          |
| Controller rendu          | `src/Controller/Front/Action/Feed/TikTokController.php`     |
| Controller OAuth callback | `src/Controller/Front/Action/Feed/TikTokAuthController.php` |
| Form admin                | `src/Form/Type/Core/Website/TikTokType.php`                 |
| Template par défaut       | `templates/front/default/actions/feed/tiktok/html.twig`     |
| Stockage médias           | `/public/feed/medias/tiktok/{externalId}/` (gitignored)     |
| Route callback            | `tiktok_auth_callback` → `/tiktok/callback`                 |

### 5.7 Tests rapides

1. **Vérifier les posts persistés** :
   ```sql
   SELECT COUNT(*) AS active FROM api_feed_post WHERE provider='tiktok' AND removed_at IS NULL;
   ```
2. **Lancer un sync manuel et inspecter la sortie** :
   ```bash
   php bin/console app:feed:sync --provider=tiktok -v
   ```
3. **Tester l'API en direct** (debug, avec un token valide) :
   ```bash
   curl -X POST 'https://open.tiktokapis.com/v2/video/list/' \
        -H 'Authorization: Bearer <ACCESS_TOKEN>' \
        -H 'Content-Type: application/json' \
        -d '{"fields":"id,create_time,cover_image_url,share_url,video_description,duration,title","max_count":5}'
   ```
4. **Vérifier les fichiers téléchargés** : `ls /public/feed/medias/tiktok/`.

## 6. Maintenance du token

### 6.1 Durées de vie TikTok

| Token           | Durée de validité | Renouvellement                                           |
|-----------------|-------------------|----------------------------------------------------------|
| `access_token`  | **24 heures**     | Via `refresh_token` (endpoint `oauth/token/`)            |
| `refresh_token` | **365 jours**     | Renouvelé à chaque appel de refresh (nouvelle valeur)    |

### 6.2 État actuel du code

⚠️ **Le service Symfony actuel ne gère pas le refresh.**

`TikTokService::getAccessToken()` ne persiste que le champ `access_token` reçu lors de l'échange du `code` OAuth. Le `refresh_token` retourné par TikTok dans la même réponse est **ignoré** et l'entité `TikTok` (`src/Entity/Api/TikTok.php`) ne possède pas de colonne dédiée.

Conséquence : **24 h après la connexion**, le `access_token` expire, `TikTokService::getFeed()` reçoit un `401` de TikTok et retourne `[]`, le contrôleur retourne une `Response` vide → le feed disparaît silencieusement.

### 6.3 Adaptations à prévoir

Pour pérenniser le feed sans intervention manuelle :

1. Étendre l'entité `TikTok` (`api_tiktok`) avec deux colonnes :
   - `refresh_token` (`VARCHAR 255`, nullable)
   - `expires_at` (`DATETIME`, nullable)
2. Modifier `TikTokService::getAccessToken()` pour retourner et persister `refresh_token`, `expires_in` et calculer `expires_at`.
3. Implémenter `TikTokService::refreshToken(string $refreshToken)` :
   ```text
   POST https://open.tiktokapis.com/v2/oauth/token/
   Content-Type: application/x-www-form-urlencoded
   client_key=<key>&client_secret=<secret>&grant_type=refresh_token&refresh_token=<token>
   ```
   Réponse : nouvel `access_token` + nouveau `refresh_token`. Persister les deux.
4. Créer une commande Symfony `app:tiktok:refresh-tokens` planifiée en cron (toutes les heures), qui appelle `refreshToken()` pour toutes les entités `TikTok` dont `expires_at < NOW() + INTERVAL 2 HOUR`.

Ces points sont à reporter dans `TODO.md` pour suivi.

## 7. Dépannage

### `redirect_uri_mismatch`
URL de redirection saisie en console TikTok ≠ URL envoyée par Symfony.
- Vérifier la valeur exacte (casse, slash final, HTTPS) dans **Login Kit → Redirect URI** (§ 3).
- En staging/recette, s'assurer que la configuration Symfony génère bien la même URL absolue que celle déclarée côté TikTok (parfois lié à `trusted_hosts`, `framework.router.default_uri` ou `.env`).

### `invalid_client`
- `client_key` ou `client_secret` incorrects côté admin.
- Vérifier dans la console : **Manage apps → app → Basic information** ; recopier les valeurs.
- Si le secret a été **Reset** récemment, mettre à jour la valeur dans l'admin Symfony.

### `access_token_invalid` ou feed vide après 24 h
- Token utilisateur expiré (cf. § 6.2 — refresh non implémenté).
- Solution immédiate : relancer le flux OAuth depuis l'admin (Option A).
- Solution durable : implémenter le refresh (cf. § 6.3).

### Feed vide alors que le token est valide
- Compte TikTok sans vidéo **publique** (la Display API ne renvoie que les publications publiques).
- App en mode **Staging** + utilisateur non ajouté en **Targeted users** → l'OAuth réussit mais la réponse `video/list/` est vide.
- Cache obsolète : `bin/console cache:clear`.
- Tester l'endpoint en direct (cf. § 5.7) pour isoler le problème (cache vs API).

### `scope_not_authorized`
- Scope `video.list` non coché dans **Login Kit → Scopes** (§ 1.8).
- Cocher le scope manquant côté TikTok, puis **refaire le flux OAuth** complet (les scopes ne sont pas mis à jour rétroactivement sur un token déjà émis).

### Page TikTok blanche / boucle de redirection
- Cookies de session TikTok du navigateur en conflit.
- Tester en **navigation privée** ou avec un autre navigateur.
- Vérifier l'état des services TikTok (rare, mais une panne side TikTok rend l'OAuth indisponible).

## 8. Sécurité — points d'attention

- **Client Secret en clair en base** : stocké tel quel dans `api_tiktok.app_secret` (VARCHAR 255, aucun chiffrement applicatif). Implique :
  - Accès admin durci (rôles applicatifs, 2FA recommandée).
  - Pas de dump SQL diffusé sans nettoyage des colonnes sensibles.
  - Pas de fixtures contenant les vraies valeurs côté Git.
- **Pas de vérification de `state` au callback** : `TikTokService::getAuthUrl()` n'envoie pas de paramètre `state` aléatoire et `TikTokAuthController::callback()` ne le vérifie pas. Risque : CSRF sur le callback OAuth (un attaquant peut potentiellement forcer la liaison d'un compte TikTok tiers en piégeant un admin connecté). **Recommandation** :
  1. Générer un `state` cryptographiquement aléatoire dans `getAuthUrl()`, le stocker en session.
  2. Vérifier dans le callback que `$request->query->get('state')` correspond à la valeur en session, sinon rejeter.
- **Logs** : aucune trace de `access_token`, `refresh_token` ou `client_secret` ne doit apparaître dans les logs Symfony / serveur. Vérifier la configuration `monolog` (pas de niveau `debug` en production) et que `HttpClient` ne logge pas les payloads.
- **Token en URL** : ne jamais transmettre `access_token` en paramètre GET côté frontend — toujours utiliser l'en-tête `Authorization: Bearer` côté serveur (déjà le cas dans `TikTokService::getFeed()`).
- **Rotation** : si un Client Secret est suspecté compromis, **Reset** immédiat dans la console TikTok puis mise à jour de la valeur en admin (cela invalide instantanément tous les tokens existants — refaire le flux OAuth).

## 9. Référence API

### 9.1 Documentation officielle

- Login Kit (OAuth Web) : <https://developers.tiktok.com/doc/login-kit-web>
- Display API — `video/list/` : <https://developers.tiktok.com/doc/display-api-overview>
- Gestion des tokens (refresh, expiration) : <https://developers.tiktok.com/doc/oauth-user-access-token-management>
- Scopes disponibles : <https://developers.tiktok.com/doc/tiktok-api-scopes>

### 9.2 Scopes

| Scope                | Usage                                          | Activé dans `TikTokService` ? |
|----------------------|------------------------------------------------|-------------------------------|
| `user.info.basic`    | Profil minimal (avatar, pseudo)                | Oui                           |
| `video.list`         | Liste des vidéos publiées par l'utilisateur    | Oui                           |
| `user.info.profile`  | Profil étendu (bio, lien)                      | Non                           |
| `user.info.stats`    | Statistiques d'engagement (follower count…)    | Non                           |
| `video.upload`       | Upload de vidéos (Content Posting API)         | Non                           |
| `video.publish`      | Publication directe                            | Non                           |

Pour ajouter un scope : modifier la liste `scope` dans `TikTokService::getAuthUrl()`, refaire le flux OAuth (les scopes ne sont pas appliqués rétroactivement) et — si l'app est en production — soumettre une nouvelle **App Review** TikTok justifiant le scope demandé.

### 9.3 Limites de l'API

| Élément            | Limite                                                          |
|--------------------|-----------------------------------------------------------------|
| `max_count` par requête `video/list/` | **20** vidéos                                |
| Pagination         | Via `cursor` (renvoyé dans la réponse) et `has_more`            |
| Taux d'appel       | Quotas par app — voir doc TikTok ; non rate-limité côté Symfony |
| Durée `access_token`  | 24 h                                                         |
| Durée `refresh_token` | 365 j                                                        |
