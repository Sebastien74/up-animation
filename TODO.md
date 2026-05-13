utf8mb4_0900_ai_ci > utf8mb4_unicode_ci
utf8mb4_unicode_ci
utf8mb4_unicode_ci
utf8mb4_unicode_ci
utf8mb4_unicode_ci

Pour le tiret sur des mots coupés
exemple h3.second {
font-size: 22px;
-webkit-hyphens: auto;
-ms-hyphens: auto;
hyphens: auto;
overflow-wrap: break-word;
word-wrap: break-word;
}

Dans les fiche pdf, prévoir de les faire uqqi par ville, checker l'URL. Et mettre l'url avec l'index, pas celle only. Récupérer aussi l'adresse

Sur les best-sellers mettre une étoile

Dropify bloc media page Qui sommes nous ne fonctinne pas

https://symfony.com/bundles/SchebTwoFactorBundle/current/api.html

Recherchez une animation en fonction de vos critères ! Page toutes 

Mettre le partage sur les fiches produits

SUr la fiche Agence revoir les paddings c'est pas aligné

Pour chaque produits faire une page par ville et passant le param city de l'agence

Breakpoint tablet 576, 768px à 991 (Faire le responsive et envisager de revoir les breakpoint du system de marges)

Au filtre des produits la pagination ne fonctionne pas https://up-animation.local/recherche-animations?catalogs=performances

A l'ajout d'une page configurer les marges mobiles correctement

A scroll menu mobile les item deviennent blanc quand on est survol une zone foncée

https://www.up-animations.abcvd.com/recherche-animations/fiche-produit/animation-seminaire : Contact url avec parma alors qu'il ne devrait pas

catalog
@!catalogue74000

Enregistrement des recherches

Faire la page de maintenance

Pour les fetch JS ajouter keepalive: true,

const response = await fetch(action, {
method: 'POST',
body: formData,
keepalive: true,
headers: {
'X-Requested-With': 'XMLHttpRequest'
}
})

Faire une page FAQ

Faire auto completion sur champs moteur de recherche

Désindexer la page 404, Merci form contact

Faire la page 404

Faire base email front

Faire la page merci form en responsive

Voir ->findFullEntity createQueryBuilder * 2 dans if ($hasLayout && $layout = $entity->getLayout()) {

Fakefiller form validation pas de message d'erreur

Formulaire se faire rappeler

Utiliser font-size: clamp(2rem, calc(1.25rem + 1.5vw), 3rem);

Finir le col padding qui ne fonctionne pas (Peut-etre génrer dans dans LayoutRuntime plus propre dans le html)

{%- set layoutRelations = layout|layoutRelations -%} Voir pour faire autrement

Axonaut API

Actus en en multi cat

Update MediaUpdateInfoCommand et ajouter a upload les dimensions et autres infos.

FAQ IMPORTANT : Et faire filtre si possible comme sur Sydev

Les paginations dasn les index doir en haut en en bas

Quand HUNI faire une function pour géré les paralaxx et virer le truc de Flo

FAIRE LE CACHE COMME PAGEREPOSITORY

        $result = $this->cacheInterface->get($cacheKey, function () use ($website, $urlCode, $locale, $preview) {
            $page = $this->optimizedQueryBuilder($website, $locale, $preview)
                ->andWhere('u.code = :code')
                ->andWhere('u.archived = :archived')
                ->setParameter('code', $urlCode)
                ->setParameter('archived', false)
                ->getQuery()
                ->enableResultCache(3600, 'page-'.$website->id.'-'.$urlCode.'-'.$locale)
                ->getOneOrNullResult();
            if ($page instanceof Page && $page->isInFill() && $page->getPages()->count() > 0) {
                foreach ($page->getPages() as $page) {
                    foreach ($page->getUrls() as $url) {
                        if ($url->getLocale() === $locale && $url->isOnline()) {
                            return ['redirection' => $url->getCode()];
                        }
                    }
                }
            }
            return $page;
        });

#[Route('/admin-%security_token%/module/catalogs/booking', name: 'admin_catalogproductbooking')]
#[IsGranted('ROLE_CATALOG')]

Retirer les args non utilisés public function indexesPages(
mixed $entity,
string $locale,
string $listingClassname,
string $classname,
array $entities = [],
array $interface = [],
bool $all = false,
bool $asIndexView = false,
): array {

Revoir les boutons open-modal-medias plusieurs la class open-modal-medias

Faire une sauvegarde de la DB et supprimer tout ce qui n'est pas 'FR'

A l'edition d'une col, de zone... A l'enregistrement la page se recharge, il faut supprimer la modal en ajax juste et mettre à jour les elements

Revoir pour faire un service pour CacheCommand / AppCacheClearCommand

Nettoyer CacheInvalidationSubscriber

Récupérer IA sur Isacar

Ajouter un Typedeblock Tel inicatifs a récupérer sur sydev

Pour l'edition zone col, block... faire une sidebar plutot qu'une modal

body .layout-block-content .body *:not(h2, h3, h4, h5, h6):not([style*="font-size"]):not([style*="font-size"] *) {
font-size: 1rem !important;
line-height: 24px !important;
}

Faire ube fiche PDF activités

Delete media dns block ne fonctionne pas erreur JS

A l'uploas d'un media faire en sorte de lui changer la taille par celles max et reduire le poids

Dans entité Media remplacer filename par originalname et dans la mécanique de taille il faut récupérer l'info size si elle existe

Faire un Générateur d’invitation entreprise
Le client génère un PDF.
Invitation
Team building entreprise
Date : 12 juin
Lieu : Annecy

Contenus viraux
Classement des meilleures activités
Articles type :
Top 20 des activités de team building
Top 10 des activités fun pour entreprise
Top 15 des activités à faire à Annecy


Pages SEO locales
Créer beaucoup de pages ciblées.
Exemples :
team building annecy
team building lyon
team building grenoble
team building geneve
Google adore ce type de pages.
Structure :
Activités disponibles
Photos
Lieux partenaires
Idées d’événements

Finir le feed instagram etc .... Commit Feed 

Mettre ces balises trans pour les long textes

{% trans with {'%agency_list%': agency_list|raw} %}
    Découvrez <strong class="text-white-50 fw-600">Up Animations</strong> expert en
    animations événementielles dans les villes d’%agency_list%
    . Nous saurons rendre vos événements originaux et conviviales. Magiciens professionnels,
    nous nous déplaçons pour vos spectacles de magie et animations en tout genre !
{% endtrans %}

Slider marquee ajouter dans back bannière petite images, moyennes images et grandes images et faire le CSS pour les 3 tailles
Et ajouter aussi data-speed="20" data-speed-mobile="20" etc

//        dd('Ajouter dans website un etag global et le persister dans Doctrine listener');

Sur page actus mettre le carousel en sticky scroll

Récupérer les boutons glasses

Dans mixin before retirer :before et :after

Faire comme dans sweetalert pour les JS

    import('../lib/sweetalert/sweetalert.min').then(() => {

        import('../../../scss/admin/lib/sweetalert.scss');

Styliser la page 500 twig logo RS etc

tester les shadows

        $sides = [
            'top' => $this->translator->trans('En haut', [], 'admin'),
            'bottom' => $this->translator->trans('En bas', [], 'admin'),
            'left' => $this->translator->trans('À gauche', [], 'admin'),
            'right' => $this->translator->trans('À droite', [], 'admin'),
            'around' => $this->translator->trans('Autour', [], 'admin'),
        ];

https://github.com/jackocnr/intl-tel-input/tree/master

Retirer tous les use non utilisés

mudole js voir pour ajouter async : export default async function

Dans actus mdias la modal d'ajout de media ne fonctionne pas

Récupérer les groupe d'onglets de sydev

Pour les erreurs de form invisible faire :
            dump($form->isSynchronized());
            dump((string) $form->getErrors(true, true));
            dd($form->getErrors()->count());

pour les ul remplacer par et virer la longue chaine :not pas bon pour la vitesses

            ul {

                padding-left: 0 !important;
                margin-left: 0 !important;
                list-style-type: none !important;

                ul {
                    list-style-type: none !important;
                    padding-left: 0 !important;
                    margin-left: 0 !important;
                    margin-top: .25rem !important;
                }

                li, ul li {

                    list-style-type: none !important;
                    position: relative;
                    padding-left: 1.25rem;
                    margin-bottom: .25rem;

                    &:before {
                        font-family: 'icomoon', serif !important;
                        content: "\e906";
                        position: absolute !important;
                        left: 0;
                        top: 5px;
                        font-size: 9px;
                        color: $primary;
                        background: none !important;
                        width: auto !important;
                        height: auto !important;
                        border-radius: 0 !important;
                        margin-left: 0 !important;
                    }
                }
            }

Sur le favicon générator faire les favicon et arrondi 

Js Front faire en sorte que les modules JS ne soient appelés que quand il y en a besoin set que les CSSS associés ne chargent que si les modules sont utilisés

Faire par page un fichier scss pour les premiere lames du site. Y mettre le menu et les primere lame visble et charges les autre assets en onload rel

Vickupload supprimer la mécanique cms de la suppression des medias
            delete_on_update: true
            delete_on_remove: true

Pour |file ne plus utiliser maxHeight maxWidth with height mais :
screensSizes: {
mobile: {width: null, height: 26},
tablet: {width: null, height: 26},
desktop: {width: null, height: 26}
},

Ajouter au blocs form le SIRET et récupérer le SIRET validator de rezup

Une fois le refactor JS fait vire les plugins non utilisés comme data-datables par exemple

Dans le uplaoder media il faut faire la compression des medias. Utiliser Imagick si existant sinon par ordre de performance de libraises existantes

Dans le phone validator faire un test pour la longueur du numéro (par langue)

cursor: pointer les passer en min-lg

pour les ... pour text trop long utiliser
overflow: hidden;
text-overflow: ellipsis;

Dans JS back retirer les message += '<svg xmlns="http://www.w3.org/2000/svg" et refaire l'alerte HTML

DANS GUIDE.MD pour cms non versionné ajouter pour JUni "Retirer La présence des **BOM (Byte Order Mark) UTF-8** "

La 404 bacck n'est pas OUF et il n'y aps le menu /admin-cd6058befb1ccb7910cca88a541ad3d85e07f179/1/information/edit/1 

Revoir toutes les requêtes depuis le debut

Message d'erreur LOgin sont en anglais

Reprendre le JS catalog front search

// webpack.config.js

const ImageMinimizerPlugin = require('image-minimizer-webpack-plugin');

Encore
// ...
.addPlugin(new ImageMinimizerPlugin({
minimizer: {
implementation: ImageMinimizerPlugin.imageminMinify,
options: {
plugins: [
['optipng', { optimizationLevel: 5 }],
],
},
},
}));

Remettre dans boostrap form exemple dans blac média édtion
{#    {%- if 'no-alt' == dataHelp -%}#}
{#        <small class="mt-1 form-text px-3 py-2 d-inline-flex align-items-center text-white w-100 bg-warning fw-600 radius">#}
{#            <span class="flex-grow-1">{{ 'far exclamation-triangle'|icon(null, 12, 'white me-2') }}</span>#}
{#            {{ "Ajoutez un titre pour optimser votre référencement. Par défault le nom du fichier sera pris en compte."|trans([], 'admin')|raw }}#}
{#        </small>#}
{#    {%- endif -%}#}

websiteAlert en TwigComponent

Juni lui faire implementer les test unitaires de tout le CMS

Juni m'a réexecuter un moule JS sans avoir besoin de le rappeler apès Ajax, voir si faisable sur les forms et autres

Faire faire le responsive des FormType a Juni

Ajouter dans les variables getsion de fonts pour mobile max sm

Demander à Juni de revoir le mix qui gere la duplication et noter le soucis quand bg-primary dans bg-white par exemple

Faire un sélécteur icon pour backoffice

A une nouvelle connexion back faire le code chiffré par email

Dépréciation 8.5

Faire le popupWithoutBox comme isacar

SUPPRIMER LES IPS AUTRE QUE LES DEV ET MIENNES

Faire un truc comme hx pour les background et img path:true

Faire une search Console Google pour l'indexation

Dans la gestion des indexes actues il manque la gestion du label pour les boutons.

Faire des macros cards par type de contenu, news product, etc

Demander à Juni de renforcer la sécurité dans le subscriber

Demander à Juni d'optimiser le .htaccess

Demander à Juni de revoir le media Service 

Demander à Juni d'alleger lehtml front, nottament les zones.html.twig

Demander à Juni si renderBlock c'est bien pour le rendu ?

Demander à Juni de factoriser les scss back

Demander à Juni de factoriser les scss front

Demander à Juni de faire en sorte que les webp ou autre soient plis léger que l'image originale ex background-security sur la page de login:

Demander de faire remonter les erreurs de login quand aucun champs n'est rempli

Dans édition site principale, styliser les Themes

Revoir toutes les microdonnées

Faire la modal déplacer les fiches medias dans bibliothèque

Dans backoffice supprimer les variables sass qui ne servent pas
Dans backoffice demander à Juni de supprimer les variables sass qui ne servent pas
Dans backoffice demander à Juni de supprimer les class  info-darken
Dans backoffice demander à Juni de nettorer les elements sass qui ne servent pas

Des element de site mettre les favions du générateur

Sur la page ci dessous revoir l'affichage des blocs en utilisant flex
https://up-animation.local/admin-b2cba79269c9e51dfb69f1eedf4732f6d47e7ec5/1/module/catalogs/catalogs/layout/1

Dans l'edition site principale, si je soumet le form avec durée du cache vide je n'ai pas de message d'erreur

JS Natif Layout back
Mettre un moteur de recherche sur le site et laisser faire Juni pour le script

Faire un tour global sur chrome

Faire un tour sur tout les actives sidebar

Les active opposite sidebar ne fonctionne pas 

Faire le style langues du back

Connecter GOOGLE TRADS

https://www.svgbackgrounds.com/search/backgrounds

Virer les  data-toggle="preloader"

Pou le selecteur de picto pour les icon actions et modules et autres récupérer les icones i

Virer un max |icon dans zone Layout back pas dit

changer favicon back

Theme light back

Mettre les validations sur le formulaire de login

Dans le tpl header include back les icon sont e svg
Remplacer les svg par des i {{ 'fa

Dans js back virer vendor clouds, dark etc... CSS pareil

Le https://up-animation.local/robots.txt?preview=true devrait afficher ce qui est vraiment dans le robots.txt

Alléger le css security en retirant le boostrap inutile
Voir pour ne pas mettre le JS bootstrap dans sécurity
Ajouter le bg sécurity dans CSS et le retirer des medias configuration 

Récupérer le tpl du back API et cleaner le remplacer le css existant

SUr la page sécurity Total time de 1300ms c'est bizarre

Changer l'intitulé du bouton produits index pour quelque chose de plus générique ChaGPT 

PAge 404 back

A la fin du refector CSS faire une passe Juni pour regrouper les classes css

icon copyright pied de page back

Créer une API Instagram feed

Validation des formulaires securite reset password le border invalid est pété

Finir LastNews Component

TRaduire les mail sécurity

Changer le favicon security pour un violet

Refaire les favicons (PSD dans le dossier sur bureau) front au nouveau format générator et retirer ceux de la DB qui ne servent plus

Dépréciations

Remplacer les app.request.get

Dans les trads back edition mettre la pagination classique

Au update vider les caches concerné par les models, pages, logos...  dans removeCacheFiles() DoctrineLIstners

Dans les controller vois pour enlever $entity->setUpdatedAt($tab->getUpdatedAt());

Back en dark, tableau de bord moche actuellement, reprendre new API

Mettre l'alerte website en mode défilement

CategoryType Newscast retourner à la liste save ne fonctionne pas

// Ajouter des exceptions sur le update par exemple les contacts, l'ajax etc
dd('Ajouter dans website un etag global et le persister dans Doctrine listener');

Dans le footer changer l'icône Utiliser notre formulaire
Dans le footer faire macro pour les liens du footer 
Dans le footer faire macro pour les liens des réseaux sociaux

Dans l'édition Information mettre les textarea en col-lg-4 là en col-12 ça fait trop long au scroll.

Mettre des grosses icônes comme Isacar en fons de zone avec du parallax

Carousel focus center comme Isacar

Sur les boutons mettre le meme hover que les badges du footer et pareil sur les boutons réseaux sociaux du pied de page

https://themeforest.net/category/site-templates/entertainment/events?gad_source=1&gad_campaignid=20946799167&gclid=EAIaIQobChMI9uaJh7edkgMVg8t5BB1mOAFbEAAYAiAAEgKm1fD_BwE

Verifier que tous les hover soient en min-lg

Pour le carrousel thumbnailer en mobile faire comme isacar

Page components 

Revoir le hover bouton avec fond blanc et autre j'aime pas peut-etre le retirer

Dans les adresses fiche agence mettre le pays

UTILISER 'onlyForUrl' => true
        foreach ($agenciesBd as $agency) {
            $agencies[] = ProductModel::fromEntity($agency, $this->coreLocator, [
//                'onlyForUrl' => true,
'disabledProducts' => true,
'disabledLayout' => true,
'disabledMedias' => true,
'disabledCategories' => true,
'disabledCategory' => true
]);
}

Finir ListingService 

Le fw-900 ne fonctionne pas : voir footer title danas le pied page

Nettoyer footer.scss

retirer $this->cache( dans les controllers

gitlab

Dans Block Model Faire un cache en récupérant d'abord tous les intls, medias... du Layout

Rotation de l'image

Faire la page de maintenance

Faire un model Features

https://up-animation.local/sitemap.xml TROP LENT

11 en France & Suisse et je n'en ai que 8

Progressive Web App affichée alors que que désactivé

Faire un commun controller pour
$agenciesCatalog = $em->getRepository(Catalog::class)->findOneBy(['website' => $website->entity, 'slug' => 'agencies']);

Virer CacheController et Services associés et refaire le systeme avec Juni

Supprimer le script dans CatalogController

Faire un sélecteur de menu dans les produits

Mettre icône tel dans le menu mais peut-être qu'en mobile

supprimer ->findByOldUrl() Page repo

Supprimer les update created partout et faire en sorte que ca se fasse dans Doctrine Listner ou autre

Lame moteur & footer : https://jthemes.net/themes/html/harmony-event/event-1.html

Retirer les Agence Félix

Manque animations : https://up-animations.fr/close-up/, https://up-animations.fr/graf/

PAS mal : https://preview.themeforest.net/item/eventiva-music-bands-bootstrap-5-html-templates/full_screen_preview/48533121
https://themeperch.net/html/eventiva/home-1.html

https://www.madebydesignesia.com/themes/exhibiz/index-new.html
https://preview.themeforest.net/item/harmoni-event-management-html-template/full_screen_preview/21975440
https://preview.themeforest.net/item/myticket-event-ticket-hall-reservation-html5-template/full_screen_preview/19779762
https://preview.themeforest.net/item/exhibiz-event-conference-and-meetup/full_screen_preview/28663470
https://html.iwthemes.com/allEvents/Conference/index-video.html
http://preview.themeforest.net/item/events-conference-tourism-music-sport-all-events-theme/full_screen_preview/9573526

https://freefrontend.com/bootstrap-cards/

Faire un tour pour ->formatDirname

Mettre le LastNaws si une actus à moins de un mois

Finir ViewController

Faire un catalogue agence

Virer les cachvePool : Comparer sur CMS7

Faire mail tester

'/fiche-produit/{url}' a la place de fiche-produit essayer le slug du catalog

SUr les pages légales passer les liens en couleur

Mettre copyright dans le footer

Faire des ULRLS avec l'arbo

LA mise à jour des poistions des medias dans prodiots est trop lente

Dans navigation mettre Nos agence en active si une agence est sélectionnée, vrai pour tous les sous menus

Logiquement je devrais avoir 111 index et ce n'est pas le cas

Ajouter aux medias mini-pc comme AL

Uns fois le site terminé supprimer les objets d'import
Uns fois le site terminé supprimer noSeo property

Mettre les boutons gradients

Dans l'édition ACTU à la sauvegarde le toast ne s'affiche pas

Les produits ne doivent pas avoir dans l'url de l'index. Dans le breadcrumb mettre l'url de l'index ou il y a tout et dans la fiche récupérer previous s'il existe 

Faire toutes les redirections 

Layout catalogue ne fonctionne plus

Faire une entité agence

Refaire le placeholder

Refaire tous le security login, mdp, emails... mettre le logo up animations dans les mails Et ajouter les trads

Dans index configuration et teaser tester les différentes possibilités.. ex : juste sous catégories. Plutôt que de faire des boucles peut etre faire des requetes ?

Enregister toutes les ancienne url dans l'url pour faire les redirections

UNe fois le dev terminer passer toutes les URLS product à offline

Mettre une video home come isacar

Pour les produit et actus verifier si un model est possible ou si actuellement sur le site c'est unique pour chaque fiche

Pour les menus pieds de pages récupérer l'abo plutôt que de faire un menu classique et ajouter aux entités concernées un champ "Intitulé du menu"

Mettre les Crawler URLS et Meta dans CMS


    ERREUR !!!!!!!!!!!!!!
    /**
     * To resolve thumbnail.
     */
    public function resolve(Website $website, ThumbConfiguration $thumbConfiguration, string $dirname): void
    {
        $dirname = urldecode($dirname);
        $dirname = str_replace('/', '\\', $dirname);
        $matches = explode('\\', $dirname);
        $filename = end($matches);
        $media = $this->entityManager->getRepository(Media::class)->findOneBy(['website' => $website, 'originalName' => $filename]);
        if ($media instanceof Media) {
            $thumbConfiguration = $this->thumbnailRuntime->thumbConfiguration($media, $thumbConfiguration);
            try {
                $this->thumbnailRuntime->thumb($media, $thumbConfiguration, ['execute' => true, 'path' => true, 'generator' => true]);
            } catch (LoaderError|RuntimeError|SyntaxError|NonUniqueResultException $e) {
            }
        }
    }

Voir pour voir la meme chose que AL 

            $asMediaScreen = in_array($screen, ['mini-pc', 'tablet', 'mobile']);
            $methodWidth = 'desktop' === $screen ? 'getMaxWidth' : 'get'.ucfirst($screenMethodSizes).'MaxWidth';
            $methodHeight = 'desktop' === $screen ? 'getMaxHeight' : 'get'.ucfirst($screenMethodSizes).'MaxHeight';
            $width = $mediaRelation->$methodWidth() ? $mediaRelation->$methodWidth() : ($mediaRelation->getMaxWidth() ? $mediaRelation->getMaxWidth() : null);
            $height = $mediaRelation->$methodHeight() ? $mediaRelation->$methodHeight() : ($mediaRelation->getMaxHeight() ? $mediaRelation->getMaxHeight() : null);
            $width = $thumbConfiguration->getScreen() === $screen && !$asMediaScreen ? $thumbConfiguration->getWidth() : ($width && $height ? $width : $originalWidth);
            $height = $thumbConfiguration->getScreen() === $screen && !$asMediaScreen ? $thumbConfiguration->getHeight() : ($width && $height ? $height : $originalHeight);

Corrigé les request get dans breadcrumb back

Faire la configrations des vignettes oar résos (Teaser, produi ou news associé, fiche produit ou actu) etc...

https://up-animation.local/admin-b2cba79269c9e51dfb69f1eedf4732f6d47e7ec5/1/module/catalogs/listings/index C'est écrit liste de produits

Retirer query public static function fromEntity(mixed $entity, CoreLocatorInterface $coreLocator, ?string $locale = null, ?bool $query = true, array $options = []): self


Dans la liste des pages, produits, actus mettre une icône pas de seo

Regarder todo CMS

Dans le URLS Crawler récupérer les métas title, description, script/json ...

Mettre le site sur api.abcd.com

DANS BASE 

{#        <link rel="preconnect" href="https://fonts.googleapis.com">#}
{#        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>#}
{#        <link rel="dns-prefetch" href="https://fonts.googleapis.com">#}
{#        <link rel="dns-prefetch" href="https://fonts.gstatic.com">#}
{#        <link rel="dns-prefetch" href="https://www.googletagmanager.com">#}
{#        <link rel="dns-prefetch" href="https://www.google-analytics.com">#}
{#        <link rel="dns-prefetch" href="https://connect.facebook.net">#}

Social wall Insta, facebook

Regarder toutes les metas du head et autres

Faire le tour des radius au loader images

<script type="application/ld+json" class="yoast-schema-graph">{"@context":"https://schema.org","@graph":[{"@type":"WebPage","@id":"https://up-animations.fr/","url":"https://up-animations.fr/","name":"Up Animations! Les meilleurs Activités et Animations pour vos évènements","isPartOf":{"@id":"https://up-animations.fr/#website"},"primaryImageOfPage":{"@id":"https://up-animations.fr/#primaryimage"},"image":{"@id":"https://up-animations.fr/#primaryimage"},"thumbnailUrl":"http://up-animations.fr/wp-content/uploads/2017/06/separateur-up-animation-1.png","datePublished":"2017-06-13T06:12:45+00:00","dateModified":"2023-02-17T14:26:35+00:00","description":"Animation & activité originale ? Teambuilding, séminaire, soirée d'entreprise, magie & spéctacles à Annecy, Grenoble, Chambéry, Gap, Bourg-en-Bresse, Paris, Bordeaux, Montpellier, Lyon & Genève.","breadcrumb":{"@id":"https://up-animations.fr/#breadcrumb"},"inLanguage":"fr-FR","potentialAction":[{"@type":"ReadAction","target":["https://up-animations.fr/"]}]},{"@type":"ImageObject","inLanguage":"fr-FR","@id":"https://up-animations.fr/#primaryimage","url":"http://up-animations.fr/wp-content/uploads/2017/06/separateur-up-animation-1.png","contentUrl":"http://up-animations.fr/wp-content/uploads/2017/06/separateur-up-animation-1.png"},{"@type":"BreadcrumbList","@id":"https://up-animations.fr/#breadcrumb","itemListElement":[{"@type":"ListItem","position":1,"name":"Accueil"}]},{"@type":"WebSite","@id":"https://up-animations.fr/#website","url":"https://up-animations.fr/","name":"Up-Animations","description":"Animations évènementielles | Event l Team Building","potentialAction":[{"@type":"SearchAction","target":{"@type":"EntryPoint","urlTemplate":"https://up-animations.fr/?s={search_term_string}"},"query-input":{"@type":"PropertyValueSpecification","valueRequired":true,"valueName":"search_term_string"}}],"inLanguage":"fr-FR"}]}</script>

GENERER LES TRADS

--- SECURITE / Credentials Instagram sortis de src/Model/Api/InstagramModel.php ---
App ID    : 1227922292865765
App Secret: 7e4fd55b09b2b2bb623b3ee1c96a7c77
Action :
 - REVOQUER cet App Secret dans la console Meta (developers.facebook.com), il a été versionné en clair → considérer comme compromis.
 - Régénérer un nouveau couple App ID / App Secret.
 - Saisir les nouvelles valeurs UNIQUEMENT via l'admin (Configuration du site > onglet Instagram) → persisté en base table api_instagram, lu via $data->appId / $data->appSecret dans InstagramModel::modelCache().
 - Vérifier aucun autre fichier ne contient ces valeurs (grep "1227922292865765" + "7e4fd55b09b2b2bb623b3ee1c96a7c77" repo + historique git).