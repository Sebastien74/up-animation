https://themeforest.net/category/site-templates/entertainment/events?gad_source=1&gad_campaignid=20946799167&gclid=EAIaIQobChMI9uaJh7edkgMVg8t5BB1mOAFbEAAYAiAAEgKm1fD_BwE
https://preview.themeforest.net/item/harmoni-event-management-html-template/full_screen_preview/21975440
https://preview.themeforest.net/item/myticket-event-ticket-hall-reservation-html5-template/full_screen_preview/19779762
https://preview.themeforest.net/item/exhibiz-event-conference-and-meetup/full_screen_preview/28663470
https://html.iwthemes.com/allEvents/Conference/index-video.html
http://preview.themeforest.net/item/events-conference-tourism-music-sport-all-events-theme/full_screen_preview/9573526

Mettre les boutons gradients

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
        $media = $this->entityManager->getRepository(Media::class)->findOneBy(['website' => $website, 'filename' => $filename]);
        if ($media instanceof Media) {
            $thumbConfiguration = $this->thumbnailRuntime->thumbConfiguration($media, $thumbConfiguration);
            try {
                $this->thumbnailRuntime->thumb($media, $thumbConfiguration, ['execute' => true, 'path' => true, 'generator' => true]);
            } catch (LoaderError|RuntimeError|SyntaxError|NonUniqueResultException $e) {
            }
        }
    }

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