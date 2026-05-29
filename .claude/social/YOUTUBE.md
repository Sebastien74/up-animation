# Guide de Connexion YouTube Feed

Ce document détaille la procédure pour connecter une chaîne YouTube au site et afficher ses dernières vidéos.

## 1. Création d'un projet Google Cloud

1. Allez sur la [Console Google Cloud](https://console.cloud.google.com/).
2. Créez un nouveau projet (ou sélectionnez un projet existant).
3. Dans le menu de gauche, allez dans **APIs & Services** > **Library**.
4. Recherchez **YouTube Data API v3** et cliquez sur **Enable**.

## 2. Génération de la Clé API

1. Allez dans **APIs & Services** > **Credentials**.
2. Cliquez sur **Create Credentials** > **API key**.
3. Copiez la clé générée.
4. **Important** : Cliquez sur l'icône de modification de la clé pour restreindre son utilisation aux requêtes YouTube Data API v3 uniquement.

## 3. Récupération du Channel ID

1. Allez sur la chaîne YouTube que vous souhaitez afficher.
2. Allez dans l'onglet **À propos**.
3. Cliquez sur **Partager** puis sur **Copier l'identifiant de la chaîne** (ou récupérez-le dans l'URL de la chaîne).

## 4. Configuration dans le CMS

1. Connectez-vous à l'administration du site.
2. Allez dans la section **API** > **Google**.
3. Remplissez les champs YouTube :
    - **YouTube API Key** : La clé générée à l'étape 2.
    - **YouTube Channel ID** : L'identifiant récupéré à l'étape 3.
    - **Nombre d'items** : Nombre de vidéos à afficher (par défaut 7).
4. Enregistrez.

## 5. Utilisation technique

Le système utilise le contrôleur suivant pour le rendu :
- **Controller** : `App\Controller\Front\Action\Feed\YouTubeController::index`
- **Template** : `templates/front/[template]/actions/feed/youtube/html.twig`

Pour appeler le flux dans un template Twig :
```twig
{{ render(controller('App\\Controller\\Front\\Action\\Feed\\YouTubeController::index')) }}
```

## 6. Architecture & rafraîchissement

YouTube est désormais branché sur le **pipeline `app:feed:sync` / `FeedPost`**, comme Instagram et TikTok. Le rendu front **ne fait plus aucun appel à l'API** : les vidéos et leur miniature sont persistées en base (`api_feed_post`, `provider=youtube`) et sur disque (`/public/feed/medias/youtube/{externalId}/`). Comme TikTok, l'API ne fournit pas la vidéo brute : la miniature locale est cliquable et ouvre la lecture sur YouTube via le permalien.

### 6.1 Flux

```
[sync] → app:feed:sync --provider=youtube
            └── YouTubeFeedFetcher → YouTubeService::getVideos() → YouTube Data API v3
                  └── FeedSyncService : upsert FeedPost + téléchargement miniature + soft-delete des absents

[visiteur] → render(controller('…YouTubeController::index'))
              └── FeedPostRepository::findActiveByProvider('youtube', nbrItems) → DB
```

### 6.2 Déclencheurs de sync

Identiques aux autres providers (cf. INSTAGRAM.md § 7.3) : auto-sync au chargement (verrou 12 h, `kernel.terminate`), bouton dashboard, CLI `app:feed:sync --provider=youtube`, ou tâche planifiée `app:feed:sync`.

> La clé API YouTube Data n'expire pas (pas de flux OAuth ni de token à rafraîchir) : aucune commande de refresh nécessaire.
>
> **Quota** : chaque sync consomme ~100 unités (endpoint `search`). Le verrou 12 h plafonne à ~2 syncs/jour/provider, donc l'impact quota reste faible. Ne pas contourner ce verrou par un cron trop fréquent.

### 6.3 Récapitulatif des fichiers

| Élément              | Emplacement                                                 |
|----------------------|-------------------------------------------------------------|
| Fetcher (sync only)  | `src/Service/Content/Feed/YouTubeFeedFetcher.php`           |
| Service API live     | `src/Service/Content/Feed/YouTubeService.php`               |
| Controller rendu     | `src/Controller/Front/Action/Feed/YouTubeController.php`    |
| Template             | `templates/front/default/actions/feed/youtube/html.twig`    |
| Stockage médias      | `/public/feed/medias/youtube/{externalId}/` (gitignored)    |
| Provider constant    | `FeedPost::PROVIDER_YOUTUBE`                                 |
