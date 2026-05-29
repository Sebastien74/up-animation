# Guide d'intégration du feed Instagram

Procédure pour brancher un compte Instagram sur le site et afficher son flux d'images et de vidéos.

---

> **API utilisée : Instagram API with Instagram Login** (scope `instagram_business_basic`).
> L'ancienne *Instagram Basic Display* a été coupée par Meta le 04/12/2024 ; le service Symfony a été migré vers la nouvelle API.

---

## 1. Prérequis

| Élément | Détail |
|---|---|
| Compte Instagram | Type **Professionnel** (Creator ou Business). Les comptes personnels ne sont pas éligibles. |
| Compte Meta Developer | https://developers.facebook.com/ |
| URL du site | Hébergé en **HTTPS** valide (obligatoire pour la redirection OAuth). |
| Accès admin Symfony | Rôle permettant d'éditer la configuration du site. |

---

## 2. Création de l'application Meta

1. Aller sur [Meta for Developers](https://developers.facebook.com/) → **Mes applications** → **Créer une application**.
2. **Détails de l'application** : nom de l'app + email de contact.
3. **Cas d'utilisation** : sélectionner **« Gérer les messages et les contenus sur Instagram »** (icône Instagram, filtre *Tout* ou *Gestion du contenu*).

   > ⚠️ Le libellé est trompeur — il évoque la publication et les DM. C'est pourtant **ce cas exact** qui débloque l'**Instagram API with Instagram Login** et le scope `instagram_business_basic` utilisé par `InstagramService`. Meta a regroupé "lire ses propres posts" sous "gérer le contenu" car c'est le même flux OAuth (avec moins de scopes activés).
   >
   > À **ne pas** prendre par erreur :
   > - *Intégrer du contenu Facebook, Instagram et Threads sur d'autres sites Web* → oEmbed (flux différent, intégration par URL publique, incompatible avec le code).
   > - *Accéder à l'API Threads* → autre produit Meta.
   > - *Authentifier et demander les données des utilisateur(ice)s avec Facebook Login* → flux FB, pas Instagram.

4. **Entreprise** : associer un *Business Portfolio* Meta si demandé (créer un Business Portfolio si nécessaire — gratuit, ~2 min).
5. **Conditions requises** + **Vue d'ensemble** : valider.
6. Dans le tableau de bord de l'app créée, ouvrir **Instagram → API setup with Instagram login** et lier le compte Instagram **professionnel** (Creator ou Business) à tester.

   > Le produit *Instagram Basic Display* n'existe plus dans la console — coupé par Meta le 04/12/2024. Si tu le cherches, c'est normal de ne pas le trouver.

---

## 3. Paramétrage OAuth

Dans le panneau **Instagram → API setup with Instagram login** :

| Champ                      | Valeur                                                              |
|----------------------------|---------------------------------------------------------------------|
| OAuth Redirect URIs        | `https://[votre-domaine.com]/instagram/callback`                    |
| Deauthorize Callback URL   | `https://[votre-domaine.com]/instagram/callback` (ou URL dédiée)    |
| Data Deletion Request URL  | URL d'une page expliquant la suppression des données                |

> La route `/instagram/callback` est définie dans `App\Controller\Front\Action\Feed\InstagramAuthController` (route nommée `instagram_auth_callback`).

Récupérer ensuite :

- **Instagram App ID**
- **Instagram App Secret**

---

## 4. Ajout d'un compte testeur (mode développement)

Tant que l'app est en mode *Développement*, seuls les comptes ajoutés comme testeurs peuvent s'authentifier.

1. Dans la console Meta : **App roles** → **Roles** → **Instagram Testers** → *Add people*.
2. Saisir le **nom d'utilisateur Instagram** (sans `@`).
3. Sur Instagram (app mobile ou web), sur le compte concerné :
   - **Paramètres** → **Espace Comptes** → **Applications et sites web** → onglet **Invitations de testeurs**.
   - Accepter l'invitation.

---

## 5. Configuration côté admin Symfony

1. Se connecter à l'administration.
2. **Configuration du site** → onglet **Instagram**.
3. Renseigner :
   - **App ID** : Instagram App ID copié à l'étape 3.
   - **App Secret** : Instagram App Secret.
   - **Nombre de posts** : 7 par défaut (`InstagramType` form, champ `nbrItems`).
4. **Enregistrer** la configuration. Le bouton de connexion n'apparaît qu'après cet enregistrement (il est généré côté `InstagramType::buildView` à partir de l'App ID persisté).

---

## 6. Récupération du token — deux options

### Option A — Flux OAuth depuis l'admin (recommandé)

1. Sur l'onglet Instagram en admin, cliquer sur **Connecter mon compte Instagram**.
2. Le site redirige vers `https://www.instagram.com/oauth/authorize` avec les paramètres :
   - `client_id` = App ID
   - `redirect_uri` = `https://[domaine]/instagram/callback`
   - `scope` = `instagram_business_basic`
   - `response_type` = `code`
   - `enable_fb_login=0` et `force_authentication=1` (recommandés Meta)
3. Après autorisation, Meta renvoie sur `/instagram/callback` avec un `code`.
4. `InstagramAuthController::callback()` :
   - échange le `code` contre un **short-lived token** (`POST https://api.instagram.com/oauth/access_token`),
   - échange ce token contre un **long-lived token** valable **60 jours** (`GET https://graph.instagram.com/access_token?grant_type=ig_exchange_token`),
   - persiste le token sur `Instagram::accessToken` via `EntityManager::flush()`,
   - redirige vers `admin_website_edit` avec un flash message.

### Option B — Saisie manuelle d'un token existant

Si un token longue-durée est déjà disponible : le coller directement dans le champ **API token (Manuel)** du formulaire, puis enregistrer.

---

## 7. Architecture — persistance locale du feed

> **Le rendu front ne fait plus aucun appel à l'API Instagram.**
> Les posts et leurs médias sont persistés en base (`api_feed_post`) + sur disque (`/public/feed/medias/instagram/{externalId}/`) par la commande `app:feed:sync`. Si le token expire ou si l'API tombe, le feed reste **inchangé** côté visiteur.

### 7.1 Flux

```
[cron] → app:feed:sync --provider=instagram
            │
            ├── InstagramFeedFetcher → InstagramService::getFeed() → API Meta
            ├── FeedSyncService :
            │     ├── upsert FeedPost (provider=instagram, externalId=…)
            │     ├── FeedMediaDownloader → /public/feed/medias/instagram/{externalId}/{media|thumbnail}.{ext}
            │     └── posts en DB absents de la réponse API → removed_at = NOW()
            └── flush

[visiteur] → render(controller('…InstagramController::index'))
              └── FeedPostRepository::findActiveByProvider('instagram', nbrItems) → DB
                    └── rendu Twig avec asset(post.mediaLocalPath)
```

### 7.2 Intégration dans un template Twig

```twig
{{ render(controller('App\\Controller\\Front\\Action\\Feed\\InstagramController::index')) }}
```

Template rendu : `templates/front/[template]/actions/feed/instagram/html.twig` (par défaut `templates/front/default/actions/feed/instagram/html.twig`).

Variables exposées :
- `instagram` : `InstagramModel` (config admin — App ID, nbrItems…).
- `feed` : `FeedPost[]` (entités, triées par `publishedAt DESC`, limitées à `nbrItems`).

Propriétés FeedPost utiles côté Twig :
- `post.permalink` → URL Instagram du post (lien sortant).
- `post.mediaType` → `IMAGE`, `VIDEO`, `CAROUSEL_ALBUM`.
- `post.mediaLocalPath` → chemin relatif depuis `/public`, à passer à `asset()`.
- `post.thumbnailLocalPath` → idem pour la miniature (VIDEO).
- `post.caption` → texte du post.
- `post.publishedAt` → `DateTimeImmutable`.

### 7.3 Synchronisation — déclencheurs

Quatre manières de déclencher un sync :

1. **Auto-sync au chargement d'une page** (par défaut). Quand `InstagramController::index` est rendu, `FeedAutoSyncService::scheduleIfStale('instagram')` est appelé :
   - si le verrou cache `feed_sync_lock_instagram` est encore actif (TTL 12 h), rien ne se passe ;
   - sinon le provider est mis en file et `FeedSyncService::sync('instagram')` s'exécute dans `kernel.terminate` (après envoi de la réponse au navigateur, **zéro impact sur le TTFB**).
   - Résultat : **2 syncs/jour max par provider**, automatiques, sans cron à configurer.
2. **Bouton "Synchroniser maintenant"** sur le dashboard admin. Force un sync immédiat synchrone via `FeedSyncController::sync()` (route `admin_feed_sync`, méthode POST, CSRF protégé). Vide le verrou cache avant d'appeler `FeedSyncService::sync()`.
3. **Commande CLI** pour les ops manuelles, debug, ou tâches planifiées éventuelles :
   ```bash
   php bin/console app:feed:sync --provider=instagram
   php bin/console app:feed:sync                     # tous les providers
   php bin/console app:feed:sync --force             # re-télécharger les médias déjà présents
   ```
4. **Tâche planifiée via le planificateur intégré** (méthode simple, recommandée si l'auto-sync ne suffit pas). Le projet embarque un scheduler maison (`scheduler:execute` -> `CronSchedulerService`, table `core_scheduled_command`) piloté depuis l'admin **Développement -> Tâches planifiées** (`admin_command_index`, `ROLE_INTERNAL` requis). Plutôt que d'ajouter une ligne au crontab système, on enregistre la commande une seule fois en base :
   - **Commande** : `app:feed:sync`
   - **Arguments** : laisser vide
   - **Expression cron** : `0 * * * *` (toutes les heures ; ajuster selon le rythme de publication)
   - **Actif** : oui
   
   > Le planificateur **ne transmet ni le champ `arguments` ni d'options** à la commande (il n'injecte que `cronLogger`/`commandLogger`). Impossible donc de cibler `--provider=instagram` via une `ScheduledCommand` : la tâche tourne avec le défaut `--provider=all` et synchronise **tous** les providers du pipeline `app:feed:sync` (Instagram + TikTok). Pour cibler un seul provider, utiliser la CLI manuelle (point 3) ou un crontab système avec la commande complète.

   Déclenchement : le planificateur est **web-natif**, aucun crontab système requis. À chaque requête, `ScheduledCommandTerminateSubscriber` (sur `kernel.terminate`) délègue à `CronHeartbeatService`, throttlé à 1 run / 60 s, qui exécute `scheduler:execute` in-process (compatible hébergement mutualisé). Les `ScheduledCommand` dues sont alors lancées ; sur un site peu visité, la cadence suit le trafic. Coupure via `SCHEDULER_WEB_CRON_ENABLED=false`. Un crontab système ou `public/cron.php` ne sont que des filets externes optionnels. Détails dans le `CLAUDE.md`, section « Tâches planifiées (Cron natif) ».

> Le verrou cache 12 h vit dans le cache applicatif standard. Pour le purger sans passer par le bouton admin : `bin/console cache:clear` (le verrou est perdu, prochain page-load déclenche un sync).

---

## 8. Maintenance du token

| Élément | Comportement |
|---------|--------------|
| Token longue durée | Validité **60 jours** à compter de l'émission. |
| Refresh | Commande `app:instagram:refresh-token` → `InstagramService::refreshToken()` → `GET /refresh_access_token?grant_type=ig_refresh_token`. Possible après **24 h** de vie du token, à planifier en hebdomadaire (cf. ci-dessous). |
| Si le token expire | Le feed continue de s'afficher côté visiteur (DB inchangée), mais `app:feed:sync` cessera de récupérer de nouveaux posts → relancer le flux OAuth dans l'admin. |

> **Synchro du feed** : automatisable immédiatement via le planificateur intégré (cf. § 7.3, point 4). Enregistrer `app:feed:sync --provider=instagram` comme tâche planifiée suffit à maintenir le feed à jour tant que le token est valide.
>
> **Refresh du token** : automatisé par la commande **`app:instagram:refresh-token`** (`App\Command\InstagramRefreshTokenCommand`, logique dans `App\Service\Content\Feed\InstagramTokenRefresher`). Elle parcourt chaque entité `Instagram` porteuse d'un token et renouvelle, via `InstagramService::refreshToken()`, ceux qui arrivent à expiration (fenêtre de 10 jours avant échéance, ou token dont `tokenExpiresAt` est inconnu). Chaque refresh réussi remet le compteur à 60 jours et met à jour `Instagram::tokenExpiresAt`.
>
> Planification recommandée : enregistrer une `ScheduledCommand` (admin **Développement -> Tâches planifiées**) avec la commande `app:instagram:refresh-token` et une expression cron hebdomadaire `0 4 * * 1`. Une cadence hebdomadaire suffit largement : la fenêtre utile de refresh est d'environ 50 jours.
>
> ```bash
> php bin/console app:instagram:refresh-token          # refresh des tokens proches de l'expiration
> php bin/console app:instagram:refresh-token --force  # force le refresh de tous les tokens (ops/debug)
> ```
>
> Limite : si un token a déjà expiré (plus de 60 jours sans refresh) ou date de moins de 24 h, Meta refuse le renouvellement. La commande comptabilise alors l'échec (log critique du planificateur) et il faut relancer le flux OAuth dans l'admin. La connexion OAuth initiale amorce `tokenExpiresAt` à +60 jours.

---

## 9. Référence API

Documentation Meta : https://developers.facebook.com/docs/instagram-platform/instagram-api-with-instagram-login

Scopes supplémentaires si besoins étendus (à ajouter dans `InstagramService::SCOPE`) :
- `instagram_business_content_publish` — publication de posts
- `instagram_business_manage_comments` — gestion des commentaires
- `instagram_business_manage_messages` — DM

---

## 10. Sécurité — point d'attention

`appId` et `appSecret` sont lus depuis la base (table `api_instagram`) via `InstagramModel` ; aucun identifiant n'est codé en dur dans le code. Points de vigilance :

- Ces secrets sont stockés en clair dans `api_instagram` (VARCHAR 255, pas de chiffrement applicatif) : durcir l'accès admin et ne jamais diffuser de dump SQL sans nettoyer ces colonnes.
- `accessToken` / `tokenExpiresAt` ne doivent pas apparaître dans les logs (vérifier que `HttpClient` ne logge pas les payloads et qu'aucun niveau `debug` n'est actif en production).
- Ne jamais committer de fixtures contenant de vraies clés. En cas de fuite d'un App Secret : le régénérer dans la console Meta, puis le re-saisir en admin.

---

## 11. Dépannage

### "Invalid OAuth access token" / `code` invalide
- Le `code` n'est valide qu'une fois et expire en ~10 min. Recommencer le flux.
- Vérifier que l'URL de redirection enregistrée dans Meta est **strictement identique** à celle générée par Symfony (schéma HTTPS, casse, slash final).

### "Logged-in user not supported" en cliquant sur Connecter
- App en mode développement + compte non ajouté en **Instagram Testers**. Vérifier l'invitation et son acceptation.
- Se déconnecter de tous les comptes Instagram/Facebook puis réessayer en **navigation privée**.

### `400 Bad Request` côté `InstagramService::getLongLivedToken`
- App Secret incorrect.
- `redirect_uri` divergeant entre la demande d'autorisation et l'échange du code.
- Compte Instagram personnel (non-pro) sur l'API actuelle.

### Le feed reste vide en production
- Vérifier qu'il existe des `FeedPost` actifs : `SELECT count(*) FROM api_feed_post WHERE provider='instagram' AND removed_at IS NULL;`. Si 0, lancer `php bin/console app:feed:sync --provider=instagram` et regarder la sortie.
- Vérifier `accessToken` non nul en base (table `api_instagram`).
- Tester l'endpoint manuellement : `curl "https://graph.instagram.com/me/media?fields=id,caption,media_type,media_url,permalink,thumbnail_url,timestamp&access_token=<TOKEN>"`.
- Token expiré (> 60 j sans refresh) → recommencer le flux OAuth.

### Médias 404 sur le front
- Vérifier la présence des fichiers sous `/public/feed/medias/instagram/{externalId}/`.
- Si absents : relancer `app:feed:sync --force` pour forcer le re-téléchargement.
- Vérifier les permissions du dossier `/public/feed/medias` (write côté process Symfony).

---

## 12. Récapitulatif des points techniques

| Élément                   | Emplacement                                                  |
|---------------------------|--------------------------------------------------------------|
| Entité config             | `src/Entity/Api/Instagram.php` (table `api_instagram`)       |
| Entité posts persistés    | `src/Entity/Api/FeedPost.php` (table `api_feed_post`)        |
| Repo posts persistés      | `src/Repository/Api/FeedPostRepository.php`                  |
| Model config              | `src/Model/Api/InstagramModel.php`                           |
| Service API live          | `src/Service/Content/Feed/InstagramService.php`              |
| Fetcher (sync only)       | `src/Service/Content/Feed/InstagramFeedFetcher.php`          |
| Orchestrateur sync        | `src/Service/Content/Feed/FeedSyncService.php`               |
| Auto-sync paresseuse      | `src/Service/Content/Feed/FeedAutoSyncService.php`           |
| Listener kernel.terminate | `src/EventSubscriber/FeedAutoSyncTerminateSubscriber.php`    |
| Téléchargement médias     | `src/Service/Content/Feed/FeedMediaDownloader.php`           |
| Commande de sync          | `src/Command/FeedSyncCommand.php` (`app:feed:sync`)          |
| Commande refresh token    | `src/Command/InstagramRefreshTokenCommand.php` (`app:instagram:refresh-token`) |
| Service refresh token     | `src/Service/Content/Feed/InstagramTokenRefresher.php`      |
| Sync admin (bouton)       | `src/Controller/Admin/Core/FeedSyncController.php`           |
| Controller rendu          | `src/Controller/Front/Action/Feed/InstagramController.php`   |
| Controller OAuth callback | `src/Controller/Front/Action/Feed/InstagramAuthController.php` |
| Form admin                | `src/Form/Type/Core/Website/InstagramType.php`               |
| Form manager              | `src/Form/Manager/Api/InstagramManager.php`                  |
| Template feed             | `templates/front/default/actions/feed/instagram/html.twig`   |
| Stockage médias           | `/public/feed/medias/instagram/{externalId}/` (gitignored)   |
| Route callback            | `instagram_auth_callback` → `/instagram/callback`            |
| Route rendu               | `front_instagram_index` → `/instagram/index` (sous-requête)  |
