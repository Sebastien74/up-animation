# Guide de Connexion TikTok Feed

Ce document détaille la procédure pour connecter un compte TikTok au site et afficher son flux (feed).

## 1. Création de l'application TikTok pour Développeurs

1. Allez sur le [TikTok for Developers](https://developers.tiktok.com/) et connectez-vous.
2. Allez dans **Manage apps** et cliquez sur **Connect an app**.
3. Remplissez les informations de l'application.
4. Dans la section **Products**, ajoutez **Video Kit** et **Display API**.
5. Notez votre **Client Key** et **Client Secret**.

## 2. Génération du Token d'accès (Access Token)

Pour obtenir un token d'accès, TikTok utilise le flux OAuth 2.0.

1. Configurez votre **Redirect URI** dans les paramètres de l'application TikTok.
2. Dirigez l'utilisateur vers l'URL d'autorisation de TikTok.
3. Après autorisation, TikTok redirige vers votre URI avec un code `code`.
4. Échangez ce code contre un `access_token` via l'API TikTok (`/v2/oauth/token/`).

*Note : Pour une installation simplifiée, vous pouvez utiliser des outils tiers ou des scripts de génération de token si vous n'avez pas encore implémenté le flux OAuth complet dans le CMS.*

## 3. Configuration dans le CMS

1. Connectez-vous à l'administration du site.
2. Allez dans la section **API** > **TikTok**.
3. Remplissez les champs suivants :
    - **Token d'accès** : Le token récupéré à l'étape 2.
    - **Nombre d'items** : Nombre de vidéos à afficher (par défaut 7).
4. Enregistrez.

## 4. Utilisation technique

Le système utilise le contrôleur suivant pour le rendu :
- **Controller** : `App\Controller\Front\Action\Feed\TikTokController::index`
- **Template** : `templates/front/[template]/actions/feed/tiktok/html.twig`

Pour appeler le feed dans un template Twig :
```twig
{{ render(controller('App\\Controller\\Front\\Action\\Feed\\TikTokController::index')) }}
```
