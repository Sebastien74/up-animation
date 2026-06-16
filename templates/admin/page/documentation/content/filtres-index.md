# Filtres d'index (dropdown)

Les pages « index » du back (listes d'entités) disposent d'un bouton **Filtres** qui
ouvre un **dropdown ancré** (style EasyAdmin) : une ligne par champ filtrable, avec une
case d'activation, le libellé et le contrôle de valeur, et un en-tête **Effacer /
Filtres / Appliquer**.

Le moteur de filtrage n'a pas changé : seul l'affichage a été refondu. Tout repose sur la
configuration `searchFilters` par entité et le service existant.

## Activer des filtres sur une entité

Le bouton et le dropdown apparaissent **uniquement** si l'entité a des champs déclarés
dans `searchFilters` (table `core_entity`, colonne `searchFilters`, JSON). Exemple :
`["active", "createdAt", "status"]`.

Seuls les champs **scalaires mappés Doctrine** d'un type pris en charge sont rendus :

| Type Doctrine        | Contrôle rendu                          | Filtrage             |
|----------------------|-----------------------------------------|----------------------|
| `string` / `text`    | champ texte                             | contient (LIKE)      |
| `integer`            | champ nombre                            | égalité / plage      |
| `datetime`           | champ date                              | égalité / plage      |
| `boolean`            | liste Oui / Non                         | est vrai / est faux  |

Un champ qui n'existe pas sur l'entité (pas de getter/isser) ou dont le type n'est pas
géré est simplement ignoré.

## Fonctionnement

- **Activer un filtre** : cocher la ligne. Un champ **décoché est désactivé** (`disabled`)
  et **n'est donc pas soumis** : le filtre est ignoré côté serveur. Saisir une valeur
  coche automatiquement la ligne.
- **Appliquer** : soumet le formulaire (méthode `GET`) vers la route d'index ; les filtres
  passent en query (`?filter[champ]=...`), donc l'URL est **partageable et rechargeable**.
- **Effacer** : lien vers l'index sans paramètres de filtre (actif seulement si un filtre
  est en cours). Le bouton Filtres affiche une pastille quand un filtre est actif.
- Le dropdown reste ouvert pendant la saisie (`data-bs-auto-close="outside"`) et se ferme
  au clic à l'extérieur.

## Architecture

| Élément | Rôle |
|---------|------|
| `Entity.searchFilters` (`core_entity`) | Liste des champs filtrables par entité. |
| `App\Form\Type\Core\FilterType` | Construit le formulaire de filtres (un champ par type Doctrine, via Spiriit FormFilterBundle). |
| `AdminController::index()` | Crée le formulaire (méthode `GET`), applique les filtres soumis. |
| `App\Service\Admin\SearchFilterService` | Applique les conditions au QueryBuilder (`addFilterConditions`) en respectant le champ maître. |
| `templates/admin/core/form/filters.html.twig` | Le dropdown (en-tête, lignes, valeurs). |
| `assets/js/admin/form/filter-panel.js` | Active/désactive les champs selon la case, auto-coche à la saisie. |
| `assets/scss/admin/pages/index.scss` (+ `themes/light.scss`) | Styles du panneau (dark + override clair). |

La recherche textuelle libre (champ de recherche à côté du bouton) reste séparée et repose
sur `searchFields` + `SearchManager`.

## Limites

- Pas de **sélecteur d'opérateur** par champ (« est », « contient », « avant »…) :
  l'opération est déterminée par le type du champ. Ajouter de vrais opérateurs au choix
  nécessiterait d'étendre `FilterType` et `SearchFilterService`.
- Les champs **relationnels** ou calculés ne sont pas filtrables ici (seuls les scalaires
  mappés des types listés ci-dessus).
