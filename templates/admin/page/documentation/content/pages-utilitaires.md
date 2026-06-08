# Pages utilitaires publiques

Pages PHP **autonomes** servies depuis `public/`, hors du layout Symfony : diagnostic
système, page d'erreur de secours, page hors-ligne du service worker et page d'accès
refusé. Elles partagent une **charte sombre neutre et réutilisable** (aucune couleur de
marque), pensée pour être reprise telle quelle d'un projet à l'autre.

> Ces fichiers ne passent **pas** par Webpack ni par le moteur Twig : ils embarquent leur
> CSS en ligne. C'est volontaire (robustesse, indépendance du build). L'interdiction
> projet de `var(--xxx)` vise l'architecture SCSS, **pas** ces pages.

---

## Les quatre pages

| Fichier | Rôle | Accent sémantique | Accès |
|---|---|---|---|
| `public/check.php` | Diagnostic système (Symfony Requirements) | vert / ambre / rouge selon l'état | Allowlist IP, sinon 403 |
| `public/error.php` | Page d'erreur serveur de secours | rouge | Allowlist IP, sinon 403 |
| `public/offline.php` | Fallback hors-ligne du service worker | bleu (info) | Public (servie hors réseau) |
| `public/denied.php` | Corps de la réponse 403 | ambre (restriction) | Inclus par les pages protégées |

---

## Charte visuelle commune

- **Fond** : slate/encre neutre (`#0e1014` → `#14171d`), surfaces blanches en faible alpha,
  grain fin, halo radial discret. Aucune teinte issue du front du projet.
- **Accent sémantique** : l'accent n'est pas une couleur de marque mais le **sens** de la
  page (OK/attention/erreur/info). `check.php` change d'accent selon l'état via
  `data-state` sur `<html>`. Le halo de fond se teinte automatiquement de l'accent courant
  (`color-mix`).
- **Typographie** : Hanken Grotesk (Google Fonts) ; IBM Plex Mono pour les détails
  techniques de `check.php`. `offline.php` utilise une **stack système** (pas de requête
  réseau, voir plus bas).
- **Motion** : une seule entrée orchestrée (révélations échelonnées). `prefers-reduced-motion`
  respecté partout.
- **Responsive** : `clamp()` sur titres et espacements, offsets éditoriaux annulés sous
  600px, garde-fou `overflow-wrap` sur titres/paragraphes (pas de débordement d'un mot long
  en capitales sur petit écran).

---

## Sécurité et indexation

- **Allowlist IP** (`check.php`, `error.php`) : accès restreint à `$_SERVER['REMOTE_ADDR']`
  (le pair TCP réel ; `X-Forwarded-For` est usurpable côté client et n'est donc **pas** de
  confiance). Hors allowlist : `403` + inclusion de `denied.php`.
- **Non-indexation** : chaque page envoie l'en-tête HTTP
  `X-Robots-Tag: noindex, nofollow, noarchive` (plus fiable que la seule balise meta, qui
  reste présente en complément). À conserver sur toute nouvelle page utilitaire.

---

## `offline.php` : contrainte d'autonomie

Cette page est **mise en cache par le service worker** et affichée **sans réseau**. Elle
doit donc rester strictement autonome :

- CSS **en ligne** uniquement, **aucune** ressource externe (pas de Google Fonts : stack de
  polices système en fallback).
- La dépendance historique `require './service-worker/style.html'` (tout Bootstrap inliné) a
  été supprimée au profit d'une feuille ciblée et légère.
- Traductions FR/EN intégrées (titre, accroche, message, bouton) sélectionnées via
  `\Locale::getDefault()`.

---

## Modifier ces pages

- **Changer l'accent d'une page** : ajuster `--hl` / `--hl-soft` dans le `:root` du fichier
  (et, pour `check.php`, les règles `[data-state="…"]`).
- **Ajouter une IP autorisée** : compléter le tableau `$ips` en tête de `check.php` **et**
  `error.php`.
- **Nouvelle page utilitaire** : reprendre le `:root` (tokens neutres + accent sémantique),
  l'en-tête `X-Robots-Tag`, la balise `meta robots`, et les garde-fous responsive.
