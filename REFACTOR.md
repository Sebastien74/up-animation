# Pistes de refactorisation et d'allègement

> Analyse du 2026-06-19, basée sur des mesures concrètes du dépôt (lignes par fichier,
> répétitions de patterns, structure des contrôleurs). Pistes **priorisées** par
> rapport gain/effort. Chaque item indique : constat chiffré, piste, gain attendu,
> effort et risque. A discuter avant exécution ; rien n'est modifié ici.

Légende effort/risque : 🟢 faible · 🟡 moyen · 🔴 élevé.

---

## Priorité 1 - Gains rapides, risque faible

### 1.1 Sortir les libs front vendorées du dépôt

**Constat** : ~64 500 lignes de JS tiers committées dans `assets/` :
`jquery-ui.js` (18 705), `jquery.js` (11 008), `bootstrap.bundle.js` x2 (6 312 chacun),
`bootstrap.js`/`bootstrap.esm.js` dupliqués front + admin, jvectormap, cropper...

**Piste** : déclarer ces dépendances dans `package.json` (Yarn) et les importer via
Encore (imports ciblés ES6 pour Bootstrap, cf. CLAUDE.md « Bootstrap JS »).
Supprimer les copies committées.

**Gain** : dépôt allégé de dizaines de milliers de lignes, mises à jour de sécurité
gérées par Yarn, plus de duplication front/admin, tree-shaking possible.
**Effort** 🟡 · **Risque** 🟡 (vérifier que jQuery/jQuery-UI sont encore nécessaires ;
sinon suppression pure).

### 1.2 Remplacer le parseur User-Agent maison

**Constat** : `src/Service/Content/BrowserDetection.php` (1 386 lignes) est un parseur
UA à base de regex (`matchingRegex`, `HTTP_UA_CPU`, tables de matches) - réinvention
d'une bibliothèque.

**Piste** : si la détection sert au rendu, privilégier le CSS/feature-detection côté
client. Si elle est indispensable côté serveur, remplacer par une lib maintenue
(`matomo/device-detector`, `whichbrowser`) plutôt que maintenir 1 386 lignes.

**Gain** : -1 386 lignes, fiabilité accrue, plus de maintenance des regex UA.
**Effort** 🟡 · **Risque** 🟡 (vérifier les points d'appel et la couverture).

### 1.3 Supprimer le code mort déjà inventorié

Voir `CODE_MORT.md` section 1 (8 classes PHP, confiance haute) et 2a (templates Twig).
**Gain** immédiat, **Effort** 🟢, **Risque** 🟢 après contrôle ciblé.

---

## Priorité 2 - Factorisation du boilerplate des contrôleurs admin

C'est le plus gros gisement de duplication du projet.

### 2.1 Helper pour les actions AJAX « toggle »

**Constat** : motif répété à l'identique dans de nombreux contrôleurs (ex.
`ZoneController::size/standardizeElements/positions`) :
`denyUnlessEntityWebsite()` → setter → `em()->persist()` → `em()->flush()` →
`new JsonResponse(['success' => true])`.
Mesuré : **46** occurrences de `new JsonResponse(['success' => true])`, **29**
contrôleurs avec `em()->flush()` inline, **33** `handleRequest` directs.

**Piste** : exposer dans `AdminController` (ou un trait dédié) une méthode utilitaire,
ex. `protected function applyEntityChange(object $entity, callable $mutator): JsonResponse`
qui factorise deny + mutation + persist + flush + réponse JSON. La persistance inline
dans les contrôleurs contrevient au CLAUDE.md (« logique de persistance hors des
contrôleurs »).

**Gain** : suppression de dizaines de blocs identiques, persistance centralisée.
**Effort** 🟡 · **Risque** 🟢.

### 2.2 Déclarer la config CRUD au lieu de la setter impérativement

**Constat** : chaque contrôleur CRUD répète des wrappers minces qui ne font que
positionner des propriétés puis déléguer au parent :
```php
public function edit(Request $request) {
    $this->formType = FormType\XType::class;
    $this->template = 'admin/.../x.html.twig';
    $this->formManager = $this->locator->x();
    return parent::edit($request);
}
```
Sur l'ensemble des contrôleurs : **84** `index`, **76** `edit`, **69** `new`,
**97** `delete`, **64** `show`, **59** `position` - en grande partie ces wrappers.

**Piste** : décrire la configuration (formType / template / manager) de façon
déclarative - attribut PHP sur la classe (`#[CrudConfig(...)]`) ou tableau statique lu
par `AdminController` - et ne garder dans le contrôleur que les actions réellement
spécifiques. Les actions purement déléguées (`return parent::delete($request)` sans
config) peuvent disparaître si le routing pointe sur la base.

**Gain** : des centaines de lignes de wrappers supprimées, intention lisible d'un coup
d'oeil, moins d'erreurs de copier-coller.
**Effort** 🔴 (toucher beaucoup de contrôleurs) · **Risque** 🟡 (couvrir par tests /
crawl admin avant/après). A faire par lots (un sous-dossier `Admin/Module/...` à la fois).

---

## Priorité 3 - Découper les god objects

### 3.1 `ImageThumbnail` (1 727 lignes, 41 méthodes)

**Piste** : séparer par responsabilité - résolution de chemin, génération/variantes
Liip, WebP, fallback. Extraire des services collaborateurs injectés. Attention aux
caches médias (cf. CLAUDE.md, `public/medias/webp`).
**Effort** 🔴 · **Risque** 🟡.

### 3.2 `LayoutRuntime` (1 164 lignes, 20 fonctions Twig)

**Piste** : scinder le Twig runtime par domaine (layout / blocs / grille) en plusieurs
runtimes ciblés, chargés à la demande. Réduit le coût de chargement et clarifie.
**Effort** 🟡 · **Risque** 🟢.

### 3.3 Contrôleurs de base surdimensionnés

`ActionController` (955 l.), `AdminController` (661 l.) concentrent beaucoup de logique.
**Piste** : extraire les helpers transverses (breadcrumb, export, edition arguments)
en traits ou services dédiés. Couplé à la P2, allège fortement la hiérarchie.
**Effort** 🟡 · **Risque** 🟡.

### 3.4 Service Locators agrégateurs

**Constat** : `CoreLocator` (838 l.), `AdminLocator` (313 l.), `DataFixturesLocator`
(278 l.) centralisent un grand nombre de services. Pattern pratique mais qui masque les
dépendances réelles et grossit sans limite.
**Piste** : pour les nouveaux développements, injecter directement les services requis
(constructeur) plutôt que de passer par le locator ; réserver le locator aux cas où il
apporte réellement de la valeur. Pas de big bang, on stoppe l'inflation.
**Effort** 🟡 (progressif) · **Risque** 🟢.

---

## Priorité 4 - Front (SCSS / Twig)

### 4.1 Thème light : multiplicateur de maintenance

**Constat** : `assets/scss/admin/themes/light.scss` (3 686 l.) +
`pages/dashboard.scss` (3 557 l.). Convention projet : toute couleur thémée dans
`pages/*.scss` doit avoir un override `!important` dupliqué dans `light.scss`.

**Piste** : centraliser les couleurs dérivées du thème dans des maps SCSS / mixins
de theming, pour qu'une page ne déclare sa couleur qu'une fois et que le light se
dérive automatiquement, sans recopie `!important`. Réduit la double saisie et le risque
d'oubli. (Respecter l'interdiction `var(--xxx)` : rester sur variables/maps SCSS.)
**Effort** 🔴 · **Risque** 🟡.

### 4.2 Templates volumineux

`admin/page/core/dashboard.html.twig` (680), `catalog/view/layout.html.twig` (549),
`admin/core/index.html.twig` (433), `macros/card.html.twig` (406).
**Piste** : extraire des partials/composants réutilisables, factoriser les blocs
répétés. **Effort** 🟡 · **Risque** 🟢.

---

## Outillage recommandé (non installé)

Pour objectiver et sécuriser ces chantiers :

- `phpstan` (déjà présent) : monter le niveau progressivement.
- `shipmonk/dead-code-detector` ou `tomasvotruba/unused-public` : code mort fin.
- `sebastian/phpcpd` (ou rector `--dry-run`) : mesurer la duplication réelle (cette
  analyse-ci repose sur des heuristiques de comptage, pas sur un détecteur de clones).
- `rector` : automatiser une partie de la P2 (extraction, modernisation) en lots
  contrôlés.

---

## Ordre d'attaque suggéré

1. **P1** (1.1 à 1.3) : gains immédiats, risque faible, allègent visiblement le dépôt.
2. **P2.1** : trait toggle AJAX (factorisation transverse à fort effet de levier).
3. **P3.2 / P3.4** : découpages à risque faible.
4. **P2.2 / P3.1 / P4.1** : chantiers plus lourds, par lots, sous couverture de tests
   et de crawl admin avant/après.
