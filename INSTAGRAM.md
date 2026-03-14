# Guide de Connexion Instagram Feed

Ce document détaille la procédure pour connecter un compte Instagram au site et afficher le flux (feed) d'images et de vidéos.

## 1. Création de l'application Facebook pour Développeurs

Pour utiliser l'API Instagram Basic Display, vous devez posséder un compte Facebook pour développeurs.

1. Allez sur [Meta for Developers](https://developers.facebook.com/) et connectez-vous.
2. Cliquez sur **Mes applications** puis sur **Créer une application**.
3. Choisissez le type d'application **Autre** ou **Consommateur**.
4. Donnez un nom à votre application et validez.

## 2. Configuration de l'API Instagram Basic Display

1. Dans le tableau de bord de votre application, cherchez le produit **Instagram Basic Display** et cliquez sur **Configurer**.
2. Cliquez sur le bouton **Create New App** en bas de la page.
3. Remplissez les paramètres de l'application :
    - **Valid OAuth Redirect URIs** : `https://[votre-domaine.com]/`
    - **Deauthorize Callback URL** : `https://[votre-domaine.com]/`
    - **Data Deletion Request URL** : `https://[votre-domaine.com]/`
4. Enregistrez les modifications.

## 3. Ajout d'un compte Instagram de Test

Pendant que l'application est en mode "Développement", vous devez ajouter manuellement les comptes Instagram autorisés.

1. Allez dans **App Roles** > **Roles** (ou **Rôles** dans le menu de gauche).
2. Faites défiler jusqu'à **Instagram Testers**.
3. Cliquez sur **Add Instagram Testers** et saisissez le nom d'utilisateur du compte Instagram à connecter.
4. Sur le compte Instagram (sur mobile ou web) :
    - Allez dans **Paramètres** > **Sécurité et confidentialité** (ou **Espace Comptes**).
    - Cherchez **Applications et sites web**.
    - Allez dans l'onglet **Invitations de testeurs** et acceptez l'invitation de votre application.

## 4. Génération du Token d'accès (Access Token)

1. Retournez dans le tableau de bord Meta for Developers.
2. Allez dans **Instagram Basic Display** > **Basic Display**.
3. Faites défiler jusqu'à **User Token Generator**.
4. Cliquez sur le bouton **Generate Token** en face du compte Instagram de test.
5. Connectez-vous si nécessaire et autorisez l'accès.
6. **Copiez le token généré.**

## 5. Configuration dans le CMS

1. Connectez-vous à l'administration du site.
2. Allez dans la section **API** ou **Configuration du site**.
3. Trouvez l'onglet **Instagram**.
4. Collez le **Token d'accès** dans le champ prévu à cet effet.
5. Indiquez le **Nombre d'items** à afficher (par défaut 7).
6. Enregistrez.

## 6. Utilisation technique

Le système utilise le contrôleur suivant pour le rendu :
- **Controller** : `App\Controller\Front\Action\Feed\InstagramController::index`
- **Template** : `templates/front/[template]/actions/feed/instagram/html.twig`

Pour appeler le feed dans un autre template Twig ou via une action :
```twig
{{ render(controller('App\\Controller\\Front\\Action\\Feed\\InstagramController::index')) }}
```

## Maintenance des Tokens

Les tokens générés sont des tokens de "longue durée" (60 jours). Le service PHP inclus (`InstagramService`) possède une méthode `refreshToken` qui permet de renouveler automatiquement le token avant son expiration.
