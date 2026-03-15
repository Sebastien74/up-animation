# Configuration des avis Google

Pour afficher les avis Google sur votre site, vous devez configurer l'API Google Places.

## 1. Création de la clé API Google Maps

1. Rendez-vous sur la [Google Cloud Console](https://console.cloud.google.com/).
2. Créez un nouveau projet ou sélectionnez-en un existant.
3. Allez dans **API et services > Bibliothèque**.
4. Recherchez et activez l'API suivante :
   - **Places API**
5. Allez dans **API et services > Identifiants**.
6. Cliquez sur **Créer des identifiants > Clé API**.
7. Copiez la clé générée.

## 2. Récupération du Place ID

Vous avez besoin du "Place ID" de votre établissement :

1. Utilisez l'outil [Place ID Finder](https://developers.google.com/maps/documentation/places/web-service/place-id) de Google.
2. Recherchez votre établissement sur la carte.
3. Copiez le "Place ID" affiché sur la carte (ex: `ChIJN1t_tDeuEmsRUsoyG83frY4`).

## 3. Configuration dans le CMS

1. Connectez-vous à l'administration de votre site.
2. Allez dans la configuration des **API** (section Google).
3. Dans l'onglet correspondant à votre langue :
   - Collez votre clé API dans le champ **Clé Google Maps**.
   - Collez votre **Place ID** dans le champ correspondant.
4. (Optionnel) Ajustez le nombre d'avis à afficher dans le champ **Nombre d'avis Google** (par défaut 5).

## 4. Affichage sur le site

Le flux sera accessible via le contrôleur :
`App\Controller\Front\Action\Feed\GoogleReviewController::index`

Vous pouvez l'appeler dans vos pages via une action de bloc ou directement dans un template Twig si le système le permet.

> **Note :** L'API Places de Google ne permet de récupérer nativement que les **5 avis les plus pertinents ou récents**. Les données sont mises en cache pendant 24 heures pour optimiser les performances et limiter les appels API.
