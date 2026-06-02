# TODO — up-animation

> Document de suivi des tâches. Tout le contenu d'origine est préservé, regroupé par thématique.
> Les `- [x]` marquent ce qui a été livré (vérifié dans le code / les commits des 3 dernières semaines, voir « Livré récemment »).

---

## 🎯 Priorités (reste à faire)

### P0 — Critique / bloquant

- **Révoquer + régénérer l'App Secret Instagram dans la console Meta** (action manuelle, externe). Le code ne contient plus les clés en clair, mais la révocation côté Meta reste à faire.
- Supprimer les IPs autres que celles des devs et les miennes.
- Bugs admin bloquants : `CategoryType Newscast` save KO, layout catalogue KO, ajout redirection SEO KO, pagination filtres produits KO, modal ajout média (actus) KO, delete media (erreur JS), dropify bloc media « Qui sommes nous ».
- Perf bloquante : `sitemap.xml` trop lent, page security ~1300 ms, mise à jour positions médias trop lente.

### P1 — Important

- Pages erreur/maintenance : 404 front, page de maintenance, styliser le 500, désindexer 404 + « Merci form contact ».
- Login/security : traduire les mails security, validations sur le form de login, remonter les erreurs quand aucun champ rempli, border invalid reset password.
- SEO : faire toutes les redirections + enregistrer anciennes URLs, microdonnées Schema.org JSON-LD, URLs en arborescence.
- Doctrine cleanup : déplacer `setUpdatedAt`/`setCreatedAt` dans un listener, retirer args + `use` inutilisés, supprimer `findByOldUrl`.
- Médias : compression à l'upload (Imagick si dispo), `filename` → `originalName`.

### P2 — Polish / refactor / idées

- Refactor SCSS front + back (factorisation, footer, carousels, boutons gradients).
- Pages SEO locales (team building par ville) + contenus viraux.
- APIs externes (Axonaut, Google Trads, social wall).
- Idées & inspirations design.

---

## ✅ Livré récemment (3 dernières semaines)

- Cron natif déclenché au trafic + rapport/journal dashboard + auto-provisionnement catalogue + rotation cache (`app:cache:reclaim`).
- Cache : `PageRepository` en result-cache, gestion back-office des pools + invalidation par entité, cache de fragments home (~49 → ~20 requêtes anonyme).
- N+1 home : produit/catalogue 235 → ~49 requêtes, fetch-joins layout/navigation, index composites listings, collections LAZY/EXTRA_LAZY.
- Sécurité : 2FA back (code email + onboarding), gardes IDOR multi-website (Page/Product/Media + AJAX layout), CSRF systématique sur suppressions, blocage fichiers exécutables, tokens jamais loggés.
- Feed : pipeline `app:feed:sync` IG + Facebook + YouTube + TikTok.
- Admin : portail de documentation, page « Poids du projet », accès rapides dashboard, 10 formes de masque médias.
- Cleanup : `dd()` mort retiré, `strict_types` + commentaire mort retirés (passe partielle).

---

## Référence rapide — Collations DB à appliquer

```
utf8mb4_0900_ai_ci > utf8mb4_unicode_ci
utf8mb4_unicode_ci
utf8mb4_unicode_ci
utf8mb4_unicode_ci
utf8mb4_unicode_ci
```

---

## URGENT — Sécurité & credentials

- **Credentials Instagram compromis** (sortis de `src/Model/Api/InstagramModel.php`, versionnés en clair) :
  - App ID : `1227922292865765`
  - App Secret : `7e4fd55b09b2b2bb623b3ee1c96a7c77`
  - Actions à mener :
    - Révoquer cet App Secret dans la console Meta (developers.facebook.com) — considérer comme compromis.
    - Régénérer un nouveau couple App ID / App Secret.
    - [x] Saisir les nouvelles valeurs **uniquement via l'admin** (Configuration du site > onglet Instagram) → `InstagramModel` lit désormais `$data->appId` / `$data->appSecret` depuis la base.
    - [x] Vérifier qu'aucun autre fichier ne contient ces valeurs (clés absentes de `src/`, vérifié). Reste : vérifier l'historique git.
- Supprimer les IPs autres que celles des devs et les miennes.
- Demander à Juni de renforcer la sécurité dans le subscriber.
- Demander à Juni d'optimiser le `.htaccess`.

---

## Bugs identifiés

- `https://up-animation.local/admin-…/1/websites/edit/1` onglet dev : theme light KO.
- `https://up-animation.local/admin-…/1/seo/configuration/edit` : onglet langues Axeptio ne fonctionne pas.
- Ajout d'une redirection SEO ne fonctionne pas.
- Dropify bloc media page « Qui sommes nous » ne fonctionne pas.
- Pagination filtres produits KO : `https://up-animation.local/recherche-animations?catalogs=performances`.
- Scroll menu mobile : items deviennent blancs quand on survole une zone foncée.
- `https://www.up-animations.abcvd.com/recherche-animations/fiche-produit/animation-seminaire` : Contact URL avec param alors qu'il ne devrait pas.
- Delete media dans block : erreur JS.
- Dans actus médias, la modal d'ajout de média ne fonctionne pas.
- `CategoryType Newscast` : retour à la liste, save ne fonctionne pas.
- Layout catalogue ne fonctionne plus.
- À la sauvegarde d'une actu, le toast ne s'affiche pas.
- Erreur résolution thumbnail :
  ```php
  // ERREUR !!!!!!!!!!!!!!
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
  ```
- Messages d'erreur Login en anglais → à traduire.
- Faire remonter les erreurs de login quand aucun champ n'est rempli.
- Validation reset password : border invalid pété.
- Formulaire « se faire rappeler » : à finir.
- Fakefiller form validation : pas de message d'erreur.
- `col padding` à finir (peut-être générer dans `LayoutRuntime`, plus propre dans le HTML).
- 11 agences annoncées en France & Suisse mais 8 affichées.
- PWA (Progressive Web App) affichée alors que désactivée.
- `https://up-animation.local/robots.txt?preview=true` devrait afficher ce qui est vraiment dans le robots.txt.
- Page security : Total time 1300ms, c'est bizarre.
- `https://up-animation.local/sitemap.xml` TROP LENT.
- Mise à jour des positions des médias dans produits trop lente.
- Édition site principale : si je soumets le form avec durée de cache vide, pas de message d'erreur.
- Logiquement je devrais avoir 111 indexes et ce n'est pas le cas.
- `https://up-animation.local/admin-…/1/module/catalogs/listings/index` : c'est écrit « liste de produits ».

---

## Base de données — collations

Migrations à prévoir : `utf8mb4_0900_ai_ci` → `utf8mb4_unicode_ci`.

- Faire une sauvegarde de la DB et supprimer tout ce qui n'est pas `FR`.

---

## Performance & Cache

- [x] **Faire le cache comme `PageRepository`** : implémenté (`enableResultCache` clés `page-*`, `pages_action_*`, etc.).
  ```php
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
  ```
- Cache catalog product booking :
  ```php
  #[Route('/admin-%security_token%/module/catalogs/booking', name: 'admin_catalogproductbooking')]
  #[IsGranted('ROLE_CATALOG')]
  ```
- Revoir pour faire un service pour `CacheCommand` / `AppCacheClearCommand`.
- Nettoyer `CacheInvalidationSubscriber`.
- À l'update, vider les caches concernés par les models, pages, logos… dans `removeCacheFiles()` `DoctrineListeners`.
- Dans `BlockModel`, faire un cache en récupérant d'abord tous les intls, médias… du Layout.
- Retirer `$this->cache(` dans les controllers.
- Virer `CacheController` et services associés, refaire le système avec Juni.
- Supprimer le script dans `CatalogController`.
- Virer les `cachePool` : comparer sur CMS7.
- Ajouter dans `website` un etag global et le persister dans Doctrine listener (`dd('Ajouter dans website un etag global …');`). Ajouter des exceptions sur l'update : contacts, ajax, etc.
- Pour les fetch JS, ajouter `keepalive: true` :
  ```js
  const response = await fetch(action, {
    method: 'POST',
    body: formData,
    keepalive: true,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  ```
- JS Front : faire en sorte que les modules JS ne soient appelés que quand nécessaire, et que les CSS associés ne se chargent que si les modules sont utilisés.
- Faire par page un fichier SCSS pour les premières lames du site (menu + premières lames visibles), charger les autres assets en `onload rel`.
- Webpack : ajouter `ImageMinimizerPlugin`
  ```js
  // webpack.config.js
  const ImageMinimizerPlugin = require('image-minimizer-webpack-plugin');

  // Encore
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
  ```
- Demander à Juni : faire en sorte que les WebP (ou autres) soient plus légers que l'image originale (ex. `background-security` page login).
- DNS prefetch / preconnect dans `base.html.twig` :
  ```twig
  {#  <link rel="preconnect" href="https://fonts.googleapis.com">  #}
  {#  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>  #}
  {#  <link rel="dns-prefetch" href="https://fonts.googleapis.com">  #}
  {#  <link rel="dns-prefetch" href="https://fonts.gstatic.com">  #}
  {#  <link rel="dns-prefetch" href="https://www.googletagmanager.com">  #}
  {#  <link rel="dns-prefetch" href="https://www.google-analytics.com">  #}
  {#  <link rel="dns-prefetch" href="https://connect.facebook.net">  #}
  ```

---

## Sécurité applicative / 2FA / Login

- Doc SchebTwoFactorBundle : <https://symfony.com/bundles/SchebTwoFactorBundle/current/api.html>
- [x] À une nouvelle connexion back, code chiffré par email (2FA email + onboarding livrés).
- Mettre les validations sur le formulaire de login.
- Refaire tout le security login, mdp, emails… mettre le logo Up Animations dans les mails et ajouter les trads.
- Traduire les mails security.
- Changer le favicon security pour un violet.
- Page security :
  - Alléger le CSS en retirant le Bootstrap inutile.
  - Voir pour ne pas mettre le JS Bootstrap dans security.
  - Ajouter le bg security en CSS et le retirer des medias configuration.

---

## Pages d'erreur & maintenance

- Faire la page 404 front.
- Désindexer les pages 404 et « Merci form contact ».
- Faire la page de maintenance.
- Revoir la mécanique de la page de maintenance : pouvoir l'activer soit directement via l'`index` (`public/index.php`), soit (2e choix) via le back dans l'édition du website. Les deux doivent être possibles.
- Styliser la page 500 Twig (logo, RS, etc.).
- Page 404 back (n'est pas ouf, pas de menu sur `/admin-…/1/information/edit/1`).

---

## Doctrine / Repository

- Voir `->findFullEntity` `createQueryBuilder * 2` dans `if ($hasLayout && $layout = $entity->getLayout()) { … }`.
- Retirer les args non utilisés :
  ```php
  public function indexesPages(
      mixed $entity,
      string $locale,
      string $listingClassname,
      string $classname,
      array $entities = [],
      array $interface = [],
      bool $all = false,
      bool $asIndexView = false,
  ): array { … }
  ```
- Retirer query :
  ```php
  public static function fromEntity(mixed $entity, CoreLocatorInterface $coreLocator, ?string $locale = null, ?bool $query = true, array $options = []): self
  ```
- [x] Utiliser `'onlyForUrl' => true` : option implémentée dans `ProductModel`/`CatalogModel` et utilisée (controllers front, `IntlModel`, `MenuModel`, sitemap). N+1 home résolu (235 → ~49).
  ```php
  foreach ($agenciesBd as $agency) {
      $agencies[] = ProductModel::fromEntity($agency, $this->coreLocator, [
          // 'onlyForUrl' => true,
          'disabledProducts' => true,
          'disabledLayout' => true,
          'disabledMedias' => true,
          'disabledCategories' => true,
          'disabledCategory' => true,
      ]);
  }
  ```
- Supprimer `->findByOldUrl()` `PageRepository`.
- Supprimer les `setUpdatedAt` / `setCreatedAt` partout et faire en sorte que ça se fasse dans Doctrine Listener.
- Dans les controllers, voir pour enlever `$entity->setUpdatedAt($tab->getUpdatedAt());`.
- Faire un common controller pour :
  ```php
  $agenciesCatalog = $em->getRepository(Catalog::class)->findOneBy(['website' => $website->entity, 'slug' => 'agencies']);
  ```
- Revoir toutes les requêtes depuis le début.

---

## Back-office (admin)

- Theme light back à finir.
- Page 404 back.
- Changer favicon back.
- Tableau de bord moche actuellement, reprendre new API.
- Récupérer le tpl du back API et le cleaner pour remplacer le CSS existant.
- Faire le style langues du back.
- JS Natif Layout back.
- Moteur de recherche sur le site (laisser faire Juni pour le script).
- Faire un tour global sur Chrome.
- Faire un tour sur tous les actives sidebar.
- Les active opposite sidebar ne fonctionnent pas.
- Tester les shadows.
- Édition col/zone/block : à la sauvegarde la page recharge → supprimer la modal en Ajax et mettre à jour les éléments.
- Pour l'édition zone/col/block, faire une sidebar plutôt qu'une modal.
- Édition layouts blocs en flex : `https://up-animation.local/admin-…/1/module/catalogs/catalogs/layout/1`.
- Édition Information : passer les textarea en `col-lg-4` (col-12 trop long au scroll).
- Dans la gestion des indexes actus, il manque la gestion du label pour les boutons.
- Les paginations dans les indexes doivent être en haut **et** en bas.
- Trads back édition : mettre la pagination classique.
- Changer l'intitulé du bouton « produits » index pour quelque chose de plus générique (ChatGPT).
- Dans la liste des pages, produits, actus : mettre une icône « pas de SEO ».
- Dans les index d'action de type `form`, `faq`, `slider`, etc., afficher les pages front qui utilisent cette action (savoir où l'action est rattachée).
- Icon copyright pied de page back.
- Édition site principal : styliser les Themes.
- Faire un sélecteur d'icon pour backoffice.
- Pour le sélecteur de picto (icons actions, modules, autres), récupérer les icônes `i`.
- Header tpl back : les icônes sont en SVG → remplacer par des `i {{ 'fa…'|icon }}`.
- Dans JS back, virer vendor clouds, dark, etc. — pareil pour le CSS.
- Virer un max de `|icon` dans zone Layout back (pas dit).
- Virer les `data-toggle="preloader"`.
- Revoir les boutons `open-modal-medias` (plusieurs ont la classe `open-modal-medias`).
- Faire la modal « déplacer les fiches médias dans bibliothèque ».
- Sur le favicon generator : faire les favicons et arrondis.
- Mettre les favicons du générateur sur les éléments du site.
- Dans backoffice, supprimer les variables SASS qui ne servent pas.
- Demander à Juni de supprimer les variables SASS qui ne servent pas.
- Demander à Juni de supprimer les classes `info-darken`.
- Demander à Juni de nettoyer les éléments SASS qui ne servent pas.
- Alerte website en mode défilement.
- Composant `websiteAlert` en TwigComponent.

---

## Frontend — JavaScript

- Faire comme dans SweetAlert pour les JS :
  ```js
  import('../lib/sweetalert/sweetalert.min').then(() => {
    import('../../../scss/admin/lib/sweetalert.scss');
  });
  ```
- Module JS : voir pour ajouter `async` : `export default async function`.
- Juni m'a réexécuté un module JS sans avoir besoin de le rappeler après Ajax → voir si faisable sur les forms et autres.
- Pour les erreurs de form invisible :
  ```php
  dump($form->isSynchronized());
  dump((string) $form->getErrors(true, true));
  dd($form->getErrors()->count());
  ```
- Auto-complétion sur champ moteur de recherche.
- Reprendre le JS catalog front search.
- Une fois le refactor JS fait, virer les plugins non utilisés (ex. `data-datables`).
- Dans JS back, retirer le `message += '<svg xmlns="http://www.w3.org/2000/svg" …` et refaire l'alerte HTML.
- Phone validator : tester la longueur du numéro (par langue) — voir <https://github.com/jackocnr/intl-tel-input/tree/master>.
- Retirer tous les `use` non utilisés (PHP).

---

## Frontend — SCSS / CSS / Bootstrap

- Pour le tiret sur mots coupés :
  ```scss
  h3.second {
    font-size: 22px;
    -webkit-hyphens: auto;
    -ms-hyphens: auto;
    hyphens: auto;
    overflow-wrap: break-word;
    word-wrap: break-word;
  }
  ```
- Utiliser `font-size: clamp(2rem, calc(1.25rem + 1.5vw), 3rem);`.
- Pour les `...` sur textes trop longs :
  ```scss
  overflow: hidden;
  text-overflow: ellipsis;
  ```
- Dans mixin before, retirer `:before` et `:after`.
- Pour les `ul`, remplacer la longue chaîne `:not` (pas bon pour la vitesse) :
  ```scss
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
  ```
- Layout-block-content :
  ```scss
  body .layout-block-content .body *:not(h2, h3, h4, h5, h6):not([style*="font-size"]):not([style*="font-size"] *) {
    font-size: 1rem !important;
    line-height: 24px !important;
  }
  ```
- `cursor: pointer` : passer en `min-lg`.
- Vérifier que tous les hovers soient en `min-lg`.
- Sur les boutons : mettre le même hover que les badges du footer, pareil sur les boutons réseaux sociaux du pied de page.
- Revoir le hover bouton avec fond blanc — j'aime pas, peut-être le retirer.
- Mettre les boutons gradients.
- Récupérer les boutons « glasses ».
- Breakpoint tablet 576, 768px à 991 (faire le responsive et envisager de revoir les breakpoints du system de marges).
- Ajouter dans les variables une gestion de fonts pour mobile max sm.
- À l'ajout d'une page, configurer les marges mobiles correctement.
- `fw-900` ne fonctionne pas (voir footer title dans le pied de page).
- Nettoyer `footer.scss`.
- Sur les pages légales, passer les liens en couleur.
- Mettre copyright dans le footer.
- Dans le footer : changer l'icône « Utiliser notre formulaire ».
- Dans le footer : faire macro pour les liens du footer.
- Dans le footer : faire macro pour les liens des réseaux sociaux.
- Dans `navigation`, mettre « Nos agences » en `active` si une agence est sélectionnée (vrai pour tous les sous-menus).
- Carousel focus center comme Isacar.
- Pour le carrousel thumbnailer en mobile, faire comme Isacar.
- Sur page actus, mettre le carrousel en sticky scroll.
- Mettre de grosses icônes (comme Isacar) en fond de zone avec du parallax.
- Quand HUNI : faire une fonction pour gérer les parallax et virer le truc de Flo.
- Slider marquee : ajouter dans le back bannière petite/moyenne/grande, faire le CSS pour les 3 tailles. Ajouter aussi `data-speed="20" data-speed-mobile="20"` etc.
- Faire des macros cards par type de contenu (news, product, etc.).
- Mettre une vidéo home comme Isacar.
- Faire un truc comme `hx` pour les backgrounds et `img path:true`.
- Demander à Juni de factoriser les SCSS back.
- Demander à Juni de factoriser les SCSS front.
- À la fin du refactor CSS, faire une passe Juni pour regrouper les classes CSS.

---

## Templates Twig / Macros / Components

- Trans avec long texte :
  ```twig
  {% trans with {'%agency_list%': agency_list|raw} %}
      Découvrez <strong class="text-white-50 fw-600">Up Animations</strong> expert en
      animations événementielles dans les villes d'%agency_list%
      . Nous saurons rendre vos événements originaux et conviviales. Magiciens professionnels,
      nous nous déplaçons pour vos spectacles de magie et animations en tout genre !
  {% endtrans %}
  ```
- Bootstrap form helper, exemple block media édition :
  ```twig
  {#    {%- if 'no-alt' == dataHelp -%}#}
  {#        <small class="mt-1 form-text px-3 py-2 d-inline-flex align-items-center text-white w-100 bg-warning fw-600 radius">#}
  {#            <span class="flex-grow-1">{{ 'far exclamation-triangle'|icon(null, 12, 'white me-2') }}</span>#}
  {#            {{ "Ajoutez un titre pour optimser votre référencement. Par défault le nom du fichier sera pris en compte."|trans([], 'admin')|raw }}#}
  {#        </small>#}
  {#    {%- endif -%}#}
  ```
- `{%- set layoutRelations = layout|layoutRelations -%}` : voir pour faire autrement.
- Finir LastNews Component.
- Mettre le LastNews si une actu a moins d'un mois.
- Finir ViewController.
- `renderBlock` : c'est bien pour le rendu ? — à demander à Juni.
- Demander à Juni d'alléger le HTML front, notamment les `zones.html.twig`.

---

## Médias / Uploader / Images

- Dans entité Media : remplacer `filename` par `originalName` et, dans la mécanique de taille, récupérer l'info `size` si elle existe.
- Update `MediaUpdateInfoCommand` : ajouter à l'upload les dimensions et autres infos.
- À l'upload d'un média : changer la taille par celles max et réduire le poids.
- Compression médias dans l'uploader : utiliser Imagick si dispo, sinon la lib la plus performante.
- Pour `|file`, ne plus utiliser `maxHeight maxWidth with height` mais :
  ```js
  screensSizes: {
    mobile:  { width: null, height: 26 },
    tablet:  { width: null, height: 26 },
    desktop: { width: null, height: 26 }
  }
  ```
- Vichuploader : supprimer la mécanique cms de la suppression des médias.
  ```yaml
  delete_on_update: true
  delete_on_remove: true
  ```
- Ajouter aux médias `mini-pc` comme AL :
  ```php
  $asMediaScreen = in_array($screen, ['mini-pc', 'tablet', 'mobile']);
  $methodWidth = 'desktop' === $screen ? 'getMaxWidth' : 'get'.ucfirst($screenMethodSizes).'MaxWidth';
  $methodHeight = 'desktop' === $screen ? 'getMaxHeight' : 'get'.ucfirst($screenMethodSizes).'MaxHeight';
  $width = $mediaRelation->$methodWidth() ? $mediaRelation->$methodWidth() : ($mediaRelation->getMaxWidth() ? $mediaRelation->getMaxWidth() : null);
  $height = $mediaRelation->$methodHeight() ? $mediaRelation->$methodHeight() : ($mediaRelation->getMaxHeight() ? $mediaRelation->getMaxHeight() : null);
  $width = $thumbConfiguration->getScreen() === $screen && !$asMediaScreen ? $thumbConfiguration->getWidth() : ($width && $height ? $width : $originalWidth);
  $height = $thumbConfiguration->getScreen() === $screen && !$asMediaScreen ? $thumbConfiguration->getHeight() : ($width && $height ? $height : $originalHeight);
  ```
- Faire le tour des radius au loader images.
- Refaire le placeholder.
- Faire la modal « déplacer fiches médias dans bibliothèque ».
- Rotation de l'image.
- Demander à Juni de revoir le Media Service.

---

## SEO / URLs / Crawler

- Faire toutes les redirections.
- Enregistrer toutes les anciennes URLs pour faire les redirections.
- Mettre les Crawler URLs et Meta dans le CMS.
- Dans le Crawler URLs, récupérer les metas title, description, script/json, etc.
- Revoir toutes les microdonnées.
- Schema.org JSON-LD à intégrer :
  ```html
  <script type="application/ld+json" class="yoast-schema-graph">{"@context":"https://schema.org","@graph":[{"@type":"WebPage","@id":"https://up-animations.fr/","url":"https://up-animations.fr/","name":"Up Animations! Les meilleurs Activités et Animations pour vos évènements","isPartOf":{"@id":"https://up-animations.fr/#website"},"primaryImageOfPage":{"@id":"https://up-animations.fr/#primaryimage"},"image":{"@id":"https://up-animations.fr/#primaryimage"},"thumbnailUrl":"http://up-animations.fr/wp-content/uploads/2017/06/separateur-up-animation-1.png","datePublished":"2017-06-13T06:12:45+00:00","dateModified":"2023-02-17T14:26:35+00:00","description":"Animation & activité originale ? Teambuilding, séminaire, soirée d'entreprise, magie & spéctacles à Annecy, Grenoble, Chambéry, Gap, Bourg-en-Bresse, Paris, Bordeaux, Montpellier, Lyon & Genève.","breadcrumb":{"@id":"https://up-animations.fr/#breadcrumb"},"inLanguage":"fr-FR","potentialAction":[{"@type":"ReadAction","target":["https://up-animations.fr/"]}]},{"@type":"ImageObject","inLanguage":"fr-FR","@id":"https://up-animations.fr/#primaryimage","url":"http://up-animations.fr/wp-content/uploads/2017/06/separateur-up-animation-1.png","contentUrl":"http://up-animations.fr/wp-content/uploads/2017/06/separateur-up-animation-1.png"},{"@type":"BreadcrumbList","@id":"https://up-animations.fr/#breadcrumb","itemListElement":[{"@type":"ListItem","position":1,"name":"Accueil"}]},{"@type":"WebSite","@id":"https://up-animations.fr/#website","url":"https://up-animations.fr/","name":"Up-Animations","description":"Animations évènementielles | Event l Team Building","potentialAction":[{"@type":"SearchAction","target":{"@type":"EntryPoint","urlTemplate":"https://up-animations.fr/?s={search_term_string}"},"query-input":{"@type":"PropertyValueSpecification","valueRequired":true,"valueName":"search_term_string"}}],"inLanguage":"fr-FR"}]}</script>
  ```
- Regarder toutes les metas du head et autres.
- Faire une Search Console Google pour l'indexation.
- Faire des URLs avec l'arborescence.
- `/fiche-produit/{url}` : à la place de `fiche-produit`, essayer le slug du catalog.
- Les produits ne doivent pas avoir l'index dans l'URL. Dans le breadcrumb, mettre l'URL de l'index ou il y a tout, et dans la fiche récupérer `previous` s'il existe.
- Pour chaque produit, faire une page par ville en passant le param `city` de l'agence.
- Une fois le dev terminé, passer toutes les URLs product à offline.

### Pages SEO locales

Créer beaucoup de pages ciblées :
- `team building annecy`
- `team building lyon`
- `team building grenoble`
- `team building geneve`

Structure : Activités disponibles / Photos / Lieux partenaires / Idées d'événements.

### Contenus viraux

Classement des meilleures activités. Articles type :
- Top 20 des activités de team building
- Top 10 des activités fun pour entreprise
- Top 15 des activités à faire à Annecy

---

## Catalogues / Produits / Fiches

- Mettre une étoile sur les best-sellers.
- Mettre le partage sur les fiches produits.
- Sur la fiche Agence, revoir les paddings (pas aligné).
- Catalog `@!catalogue74000`.
- Faire un catalogue agence.
- Faire un sélecteur de menu dans les produits.
- Manque animations :
  - <https://up-animations.fr/close-up/>
  - <https://up-animations.fr/graf/>
- Dans les fiches PDF, prévoir de les faire **par ville** : checker l'URL, mettre l'URL avec l'index (pas celle only), récupérer aussi l'adresse.
- Faire une fiche PDF activités.
- Faire un Générateur d'invitation entreprise (le client génère un PDF) :
  ```
  Invitation
  Team building entreprise
  Date : 12 juin
  Lieu : Annecy
  ```
- Page « Recherchez une animation en fonction de vos critères ! » (toutes).
- Enregistrement des recherches.
- Tester le multi-vidéo YouTube.
- Pour les produits et actus, vérifier si un model est possible ou si c'est unique pour chaque fiche.
- Dans index configuration et teaser : tester les différentes possibilités (ex. juste sous-catégories). Plutôt que des boucles, faire des requêtes ?
- Faire la configuration des vignettes par réso (Teaser, produit ou news associé, fiche produit ou actu).
- Finir `ListingService`.
- Faire un modèle « Features ».
- Dans les adresses fiche agence, mettre le pays.
- Faire une entité Agence.
- Mettre l'icône tel dans le menu, mais peut-être qu'en mobile.

---

## Actualités / News

- Actus en multi-cat.
- Dans l'édition actu, à la sauvegarde le toast ne s'affiche pas (cf. Bugs).
- Mettre le LastNews si une actu a moins d'un mois.

---

## Pages front à finir

- Faire une page FAQ (avec filtre si possible, comme sur Sydev — **IMPORTANT**).
- Faire la base email front.
- Faire la page « merci form » en responsive.
- [x] Finir le feed Instagram, etc. → Commit Feed (pipeline `app:feed:sync` IG/FB/YT/TikTok livré).

---

## APIs externes

- Axonaut API.
- Récupérer les groupes d'onglets de Sydev.
- Récupérer IA sur Isacar.
- Ajouter un `TypeOfBlock` Tel indicatifs à récupérer sur Sydev.
- [x] Créer une API Instagram feed (intégrée au pipeline `app:feed:sync`).
- Social wall Insta, Facebook (affichage front à finir — la collecte est faite).
- Connecter Google Trads.
- GÉNÉRER LES TRADS.

---

## Refactor / Dépréciations / Cleanup

- Faire un tour complet de `src/` pour nettoyer et améliorer les commentaires : retirer les commentaires inutiles (paraphrase du code, debug résiduel), raccourcir ceux trop longs (1 ligne max, WHY non-évident), ajouter ceux qui manquent quand c'est pertinent. Commentaires en anglais (cf. CLAUDE.md).
- Dépréciations 8.5.
- Dépréciations (générales).
- Remplacer les `app.request.get`.
- Retirer tous les `use` non utilisés.
- Une fois le site terminé : supprimer les objets d'import.
- Une fois le site terminé : supprimer la propriété `noSeo`.
- Retirer les « Agence Félix ».
- Corriger les `request->get` dans breadcrumb back.
- Faire un tour pour `->formatDirname`.
- `dd('Ajouter dans website un etag global et le persister dans Doctrine listener');` — ajouter des exceptions sur l'update (contacts, ajax, etc.).
- Translator sides :
  ```php
  $sides = [
      'top'    => $this->translator->trans('En haut', [], 'admin'),
      'bottom' => $this->translator->trans('En bas', [], 'admin'),
      'left'   => $this->translator->trans('À gauche', [], 'admin'),
      'right'  => $this->translator->trans('À droite', [], 'admin'),
      'around' => $this->translator->trans('Autour', [], 'admin'),
  ];
  ```
- Dans `GUIDE.md`, pour CMS non versionné, ajouter pour Juni : « Retirer la présence des **BOM (Byte Order Mark) UTF-8** ».
- gitlab.
- Mettre le site sur `api.abcd.com`.
- Pour les menus pieds de pages, récupérer l'arbo plutôt que de faire un menu classique. Ajouter aux entités concernées un champ « Intitulé du menu ».

---

## Tâches déléguées à Juni

- Faire implémenter les tests unitaires de tout le CMS.
- Faire le responsive des FormTypes.
- Revoir le mix qui gère la duplication, noter le souci quand `bg-primary` dans `bg-white` par exemple.
- Renforcer la sécurité dans le subscriber.
- Optimiser le `.htaccess`.
- Revoir le Media Service.
- Alléger le HTML front, notamment les `zones.html.twig`.
- `renderBlock` : c'est bien pour le rendu ?
- Factoriser les SCSS back.
- Factoriser les SCSS front.
- WebP / autres : plus légers que l'image originale.
- Faire remonter les erreurs de login quand aucun champ n'est rempli.
- Supprimer les variables SASS qui ne servent pas.
- Supprimer les classes `info-darken`.
- Nettoyer les éléments SASS qui ne servent pas.
- Moteur de recherche sur le site (laisser faire Juni pour le script).
- Voir si JS auto-réexécution après Ajax est faisable sur les forms et autres.
- À la fin du refactor CSS, faire une passe pour regrouper les classes CSS.

---

## Idées & Inspirations

### Inspirations design / thèmes

- Lame moteur & footer : <https://jthemes.net/themes/html/harmony-event/event-1.html>
- <https://themeforest.net/category/site-templates/entertainment/events?gad_source=1&gad_campaignid=20946799167>
- PAS mal : <https://preview.themeforest.net/item/eventiva-music-bands-bootstrap-5-html-templates/full_screen_preview/48533121>
- <https://themeperch.net/html/eventiva/home-1.html>
- <https://www.madebydesignesia.com/themes/exhibiz/index-new.html>
- <https://preview.themeforest.net/item/harmoni-event-management-html-template/full_screen_preview/21975440>
- <https://preview.themeforest.net/item/myticket-event-ticket-hall-reservation-html5-template/full_screen_preview/19779762>
- <https://preview.themeforest.net/item/exhibiz-event-conference-and-meetup/full_screen_preview/28663470>
- <https://html.iwthemes.com/allEvents/Conference/index-video.html>
- <http://preview.themeforest.net/item/events-conference-tourism-music-sport-all-events-theme/full_screen_preview/9573526>
- Cards : <https://freefrontend.com/bootstrap-cards/>
- Backgrounds SVG : <https://www.svgbackgrounds.com/search/backgrounds>

### Idées UX

- Page components.
- Faire le `popupWithoutBox` comme Isacar.
- Carousel focus center comme Isacar.
- Vidéo home comme Isacar.
- Grosses icônes en fond de zone avec parallax.
- Mettre les boutons gradients.

---

## Snippets techniques (à réutiliser)

### Cache page repository
Voir section **Performance & Cache** ci-dessus.

### fetch keepalive
Voir section **Performance & Cache** ci-dessus.

### SweetAlert lazy import
Voir section **Frontend — JavaScript** ci-dessus.

### Form errors debug
```php
dump($form->isSynchronized());
dump((string) $form->getErrors(true, true));
dd($form->getErrors()->count());
```

### Translator sides
Voir section **Refactor / Dépréciations / Cleanup** ci-dessus.

### Screens sizes médias
Voir section **Médias / Uploader / Images** ci-dessus.

### Webpack ImageMinimizerPlugin
Voir section **Performance & Cache** ci-dessus.

---

## Divers / Petites tâches

- Mettre la microdonnée Schema.org (cf. SEO).
- Mettre `mediaQuery()` partout (rappel CLAUDE.md).
