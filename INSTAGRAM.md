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

Vous avez deux options pour générer le token :

### Option A : Connexion automatique (Recommandée)

1. Dans l'administration du site, allez dans **Configuration du site** > onglet **Instagram**.
2. Saisissez votre **App ID** et votre **App Secret** récupérés sur Meta for Developers.
3. Enregistrez la configuration.
4. Un bouton **Connecter mon compte Instagram** apparaît. Cliquez dessus.
5. Autorisez l'accès sur la page Instagram qui s'ouvre.
6. Le token sera automatiquement récupéré et enregistré.

### Option B : Génération manuelle

1. Retournez dans le tableau de bord Meta for Developers.
2. Allez dans **Instagram Basic Display** > **Basic Display**.
3. Faites défiler jusqu'à **User Token Generator**.
4. Cliquez sur le bouton **Generate Token** en face du compte Instagram de test.
5. Connectez-vous si nécessaire et autorisez l'accès.
6. Copiez le token généré et collez-le dans le champ **API token (Manuel)** de l'administration.

## 5. Configuration de l'URL de retour (Callback)

Pour l'Option A, vous devez configurer l'URL de redirection dans Meta for Developers :
1. Dans **Instagram Basic Display** > **Basic Display**.
2. Dans le champ **Valid OAuth Redirect URIs**, ajoutez : `https://[votre-domaine.com]/instagram/callback`
3. Faites de même pour **Deauthorize Callback URL** et **Data Deletion Request URL** (vous pouvez utiliser la même URL ou l'URL de base du site).

## 6. Configuration dans le CMS

1. Connectez-vous à l'administration du site.
2. Allez dans la section **Configuration du site** (via l'icône roue dentée).
3. Trouvez l'onglet **Instagram** (ou l'entrée API correspondante).
4. Configurez les champs suivants :
    - **App ID** : L'ID de votre application Instagram.
    - **App Secret** : Le secret de votre application Instagram.
    - **Nombre d'items** : Nombre de médias à afficher.
5. Enregistrez.

## 7. Utilisation technique

Le système utilise le contrôleur suivant pour le rendu :
- **Controller** : `App\Controller\Front\Action\Feed\InstagramController::index`
- **Template** : `templates/front/[template]/actions/feed/instagram/html.twig`

Pour appeler le feed dans un autre template Twig ou via une action :
```twig
{{ render(controller('App\\Controller\\Front\\Action\\Feed\\InstagramController::index')) }}
```

## 8. Maintenance des Tokens

Les tokens générés sont des tokens de "longue durée" (60 jours). Le service PHP inclus (`InstagramService`) possède une méthode `refreshToken` qui permet de renouveler automatiquement le token avant son expiration.

## 9. Dépannage (Troubleshooting)

### Erreur "Logged-in use not supported"
Si vous voyez ce message en cliquant sur le bouton **Connecter mon compte Instagram** :
1. Déconnectez-vous de votre compte Facebook et Instagram personnel sur votre navigateur.
2. Utilisez une fenêtre de **navigation privée**.
3. Assurez-vous d'avoir bien ajouté le compte Instagram souhaité dans la section **Instagram Testers** de votre application Meta for Developers et d'avoir accepté l'invitation sur le compte concerné.

### Erreur "400 Bad Request"
Cela arrive souvent si :
1. L'**App Secret** est incorrect.
2. Le code d'autorisation a expiré (vous avez attendu trop longtemps sur la page d'autorisation). Réessayez la procédure.
3. L'**URL de redirection** configurée dans Meta for Developers ne correspond pas exactement à celle générée par le site (incluant le `https://` et le `/instagram/callback`).
