# Guide de Connexion TikTok Feed

Ce document détaille la procédure pour connecter un compte TikTok au site et afficher son flux (feed).

## 1. Création de l'application TikTok pour Développeurs

1. Allez sur le [TikTok for Developers](https://developers.tiktok.com/) et connectez-vous.
2. Allez dans **Manage apps** et cliquez sur **Connect an app**.
3. Remplissez les informations de l'application.
4. Dans la section **Products**, ajoutez **Video Kit** et **Display API**.
5. Notez votre **Client Key** et **Client Secret**.

## 2. Génération du Token d'accès (Access Token)

Vous avez deux options pour générer le token :

### Option A : Connexion automatique (Recommandée)

1. Dans l'administration du site, allez dans **Configuration du site** > onglet **TikTok**.
2. Saisissez votre **Client Key** et **Client Secret**.
3. Enregistrez la configuration.
4. Un bouton **Connecter mon compte TikTok** apparaît. Cliquez dessus.
5. Autorisez l'accès sur la page TikTok qui s'ouvre.
6. Le token sera automatiquement récupéré et enregistré.

### Option B : Génération manuelle

Si vous avez déjà un token valide, vous pouvez le saisir directement dans le champ **API token (Manuel)**.

## 3. Configuration de l'URL de retour (Callback)

Pour l'Option A, vous devez configurer l'URL de redirection dans TikTok for Developers :
1. Dans votre application TikTok > **App Settings** > **Redirect URI**.
2. Ajoutez : `https://[votre-domaine.com]/tiktok/callback`

## 4. Configuration dans le CMS

1. Connectez-vous à l'administration du site.
2. Allez dans la section **Configuration du site** > onglet **TikTok**.
3. Remplissez les champs suivants :
    - **Client Key** : La clé client de votre application TikTok.
    - **Client Secret** : Le secret client de votre application TikTok.
    - **Nombre de vidéos** : Nombre de vidéos à afficher (par défaut 7).
4. Enregistrez.

## 5. Utilisation technique

Le système utilise le contrôleur suivant pour le rendu :
- **Controller** : `App\Controller\Front\Action\Feed\TikTokController::index`
- **Template** : `templates/front/[template]/actions/feed/tiktok/html.twig`

Pour appeler le feed dans un template Twig :
```twig
{{ render(controller('App\\Controller\\Front\\Action\\Feed\\TikTokController::index')) }}
```
