# Annulation de la standardisation des marges (Zone / Col / Block)

Le panneau **Marges** d'une **Zone**, d'une **Colonne** ou d'un **Bloc** propose un bouton
**« Standardiser les marges »** (icône ordinateur + portable) qui **recopie les marges et
paddings de l'écran ordinateur sur les autres écrans** (portable, tablette, mobile). Cette
action est destructive : les valeurs responsive précédentes sont écrasées.

Pour pouvoir revenir en arrière, un **snapshot** des marges/paddings de l'élément est
enregistré **juste avant** la standardisation, dans une colonne JSON `marginsBackup` de
l'élément. Un bouton **« Annuler la standardisation »** (icône undo) apparaît alors dans le
panneau Marges et permet de **restaurer l'état précédent** en un clic.

---

## Fonctionnement

- Au clic sur **Standardiser**, `LayoutService::standardizeMarginsEL()` capture l'état
  courant (marges + paddings des 4 écrans) via `captureMargins()` et le stocke dans
  `marginsBackup`, **sauf si un snapshot est déjà en attente** (sémantique d'annulation à
  un seul niveau : on ne perd jamais l'état d'origine tant qu'il n'a pas été restauré).
- Le bouton **Annuler** (`restore-margins`) n'est affiché que si `entity.marginsBackup`
  n'est pas vide. Il appelle la route `admin_{zone|col|block}_restore_margins` (méthode
  `DELETE`, `ROLE_EDIT`) -> `LayoutService::restoreMarginsEL()`, qui réécrit les valeurs
  depuis le JSON puis **vide le backup**.
- La standardisation « layout entier » (`admin_layout_standardize_margins`) effectue le
  snapshot sur chaque Zone/Col/Block. L'annulation est possible **par élément** (panneau
  Marges) **ou pour toute la page** : le bouton « Revenir aux marges précédentes »
  (`restore-margins-page`, route `admin_layout_restore_margins`, méthode `DELETE`,
  `ROLE_EDIT`) -> `LayoutService::restoreLayoutMargins()` restaure tous les éléments qui
  ont encore un backup, en un seul `flush`. Il n'apparaît à côté de « Standardiser » que
  si `layout.hasMarginsBackup` est vrai.

## Portée et limites

- **Un seul niveau d'annulation** par élément : on revient à l'état précédant la dernière
  standardisation non encore annulée.
- Le backup voyage avec l'élément (colonne sur `upa_layout_zone` / `_col` / `_block`) et
  est supprimé en cascade avec lui : aucun fichier orphelin.
- La persistance passe par le même `persist`/`flush` que la standardisation, donc
  l'invalidation des caches layout (`CacheInvalidationSubscriber`, postFlush) est héritée.

## Côté code

- Entité : `App\Entity\Layout\BaseConfiguration` -> champ `marginsBackup` (JSON, nullable).
- Service : `App\Service\Admin\LayoutService` -> `restoreMarginsEL()`, `captureMargins()`.
- Contrôleurs : `ZoneController`, `ColController`, `BlockController` -> action
  `restoreMargins`.
- Front admin : bouton dans `templates/admin/core/layout/margins.html.twig`, branchement
  dans `assets/js/admin/pages/layout/edit-element.js` (réutilise le plugin
  `standardize-margins.js`).
- Migration : `Version20260615133000` (colonne `marginsBackup` sur les 3 tables layout).
