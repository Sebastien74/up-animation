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

Pour un accès permanent au flux d'une page, il est recommandé d'utiliser un **Page Access Token** de longue durée.

1. Allez dans l'outil [Graph API Explorer](https://developers.facebook.com/tools/explorer/).
2. Sélectionnez votre application.
3. Dans **Permissions**, ajoutez `pages_read_engagement` et `pages_show_list`.
4. Cliquez sur **Generate Access Token**.
5. Autorisez l'accès à votre page.
6. Une fois le token utilisateur généré, cliquez sur l'ID utilisateur dans le menu déroulant pour sélectionner votre **Page**.
7. Le token affiché est maintenant un token de page.
8. Pour le rendre permanent, utilisez l'outil [Access Token Tool](https://developers.facebook.com/tools/accesstoken/) ou suivez la documentation Meta pour échanger le token contre un token "long-lived".

## 4. Configuration dans le CMS

1. Connectez-vous à l'administration du site.
2. Allez dans la section **API** > **Facebook**.
3. Remplissez les champs suivants :
    - **ID de la page** : L'identifiant récupéré à l'étape 2.
    - **Token d'accès** : Le token de page récupéré à l'étape 3.
    - **Nombre d'items** : Nombre de posts à afficher (par défaut 7).
4. Enregistrez.

## 5. Utilisation technique

Le système utilise le contrôleur suivant pour le rendu :
- **Controller** : `App\Controller\Front\Action\Feed\FacebookController::index`
- **Template** : `templates/front/[template]/actions/feed/facebook/html.twig`

Pour appeler le feed dans un template Twig :
```twig
{{ render(controller('App\\Controller\\Front\\Action\\Feed\\FacebookController::index')) }}
```
