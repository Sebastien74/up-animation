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

Contrairement à Instagram et TikTok, **YouTube n'est pas branché sur le pipeline `app:feed:sync` / `FeedPost`**. Les vidéos sont récupérées **en direct à chaque rendu** par `YouTubeService::getVideos()`, avec une mise en cache applicative de **1 heure** (`YouTubeService::CACHE_EXPIRE = 3600`). Conséquences :

- Aucune tâche planifiée n'est nécessaire ni applicable : le cache 1 h gère seul la fraîcheur, le scheduler `core_scheduled_command` ne concerne pas ce provider.
- La clé API YouTube Data n'expire pas (pas de flux OAuth ni de token à rafraîchir), contrairement aux tokens IG/TikTok.
- Attention au **quota journalier** de la YouTube Data API v3 : le cache 1 h limite les appels, ne pas le réduire sans vérifier la consommation de quota.
- Pour purger le cache manuellement : `php bin/console cache:clear`.
