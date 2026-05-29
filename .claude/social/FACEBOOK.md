# Guide de Connexion Facebook Feed

Ce document détaille la procédure pour connecter une page Facebook au site et afficher son flux (feed).

## 1. Création de l'application Facebook pour Développeurs

1. Allez sur [Meta for Developers](https://developers.facebook.com/) et connectez-vous.
2. Cliquez sur **Mes applications** puis sur **Créer une application**.
3. Choisissez le type d'application **Autre** ou **Consommateur**.
4. Donnez un nom à votre application et validez.

## 2. Récupération du Page ID

1. Allez sur votre page Facebook.
2. Cliquez sur l'onglet **À propos**.
3. Dans la section **Transparence de la page**, vous trouverez l'ID de la page (ou dans l'URL si elle n'est pas personnalisée).

## 3. Génération du Token d'accès (Access Token)

Vous avez deux options pour générer le token :

### Option A : Connexion automatique (Recommandée)

1. Dans l'administration du site, allez dans **Configuration du site** > onglet **Facebook**.
2. Saisissez votre **App ID**, **App Secret** et **Page ID**.
3. Enregistrez la configuration.
4. Un bouton **Connecter ma Page Facebook** apparaît. Cliquez dessus.
5. Autorisez l'accès et sélectionnez la page concernée.
6. Le token de page sera automatiquement récupéré et enregistré.

### Option B : Génération manuelle

1. Allez dans l'outil [Graph API Explorer](https://developers.facebook.com/tools/explorer/).
2. Sélectionnez votre application.
3. Dans **Permissions**, ajoutez `pages_read_engagement` et `pages_show_list`.
4. Cliquez sur **Generate Access Token**.
5. Autorisez l'accès à votre page.
6. Une fois le token utilisateur généré, cliquez sur l'ID utilisateur dans le menu déroulant pour sélectionner votre **Page**.
7. Le token affiché est maintenant un token de page.
8. Pour le rendre permanent, utilisez l'outil [Access Token Tool](https://developers.facebook.com/tools/accesstoken/) ou suivez la documentation Meta pour échanger le token contre un token "long-lived".
9. Copiez le token et collez-le dans le champ **API token (Manuel)** de l'administration.

## 4. Configuration de l'URL de retour (Callback)

Pour l'Option A, vous devez configurer l'URL de redirection dans Meta for Developers :
1. Dans votre application Facebook > **Produits** > **Facebook Login** > **Paramètres**.
2. Dans le champ **Valid OAuth Redirect URIs**, ajoutez : `https://[votre-domaine.com]/facebook/callback`

## 5. Configuration dans le CMS

1. Connectez-vous à l'administration du site.
2. Allez dans la section **Configuration du site** > onglet **Facebook**.
3. Remplissez les champs suivants :
    - **App ID** : L'ID de votre application Facebook.
    - **App Secret** : Le secret de votre application Facebook.
    - **Page ID** : L'identifiant de la page Facebook.
    - **Nombre d'items** : Nombre de posts à afficher (par défaut 7).
4. Enregistrez.

## 6. Utilisation technique

Le système utilise le contrôleur suivant pour le rendu :
- **Controller** : `App\Controller\Front\Action\Feed\FacebookController::index`
- **Template** : `templates/front/[template]/actions/feed/facebook/html.twig`

Pour appeler le feed dans un template Twig :
```twig
{{ render(controller('App\\Controller\\Front\\Action\\Feed\\FacebookController::index')) }}
```

## 7. Architecture & rafraîchissement

Facebook est désormais branché sur le **pipeline `app:feed:sync` / `FeedPost`**, comme Instagram et TikTok. Le rendu front **ne fait plus aucun appel à l'API** : les posts et leur image (`full_picture`, présente même pour les vidéos) sont persistés en base (`api_feed_post`, `provider=facebook`) et sur disque (`/public/feed/medias/facebook/{externalId}/`). Si le token expire ou que l'API tombe, le feed reste **inchangé** côté visiteur.

### 7.1 Flux

```
[sync] → app:feed:sync --provider=facebook
            └── FacebookFeedFetcher → FacebookService::getFeed() → Graph API
                  └── FeedSyncService : upsert FeedPost + téléchargement image + soft-delete des absents

[visiteur] → render(controller('…FacebookController::index'))
              └── FeedPostRepository::findActiveByProvider('facebook', nbrItems) → DB
```

### 7.2 Déclencheurs de sync

Identiques aux autres providers (cf. INSTAGRAM.md § 7.3) :
1. **Auto-sync** au chargement d'une page (verrou cache 12 h, exécuté en `kernel.terminate`).
2. **Bouton "Synchroniser maintenant"** du dashboard admin.
3. **CLI** : `php bin/console app:feed:sync --provider=facebook`.
4. **Tâche planifiée** : enregistrer `app:feed:sync` dans le planificateur intégré (sync tous providers).

> Le token de page Facebook n'expire pas (token de page longue durée). Aucune commande de refresh n'est donc nécessaire, contrairement à IG/TikTok.

### 7.3 Récapitulatif des fichiers

| Élément              | Emplacement                                                  |
|----------------------|--------------------------------------------------------------|
| Fetcher (sync only)  | `src/Service/Content/Feed/FacebookFeedFetcher.php`           |
| Service API live     | `src/Service/Content/Feed/FacebookService.php`               |
| Controller rendu     | `src/Controller/Front/Action/Feed/FacebookController.php`    |
| Template             | `templates/front/default/actions/feed/facebook/html.twig`    |
| Stockage médias      | `/public/feed/medias/facebook/{externalId}/` (gitignored)    |
| Provider constant    | `FeedPost::PROVIDER_FACEBOOK`                                 |
