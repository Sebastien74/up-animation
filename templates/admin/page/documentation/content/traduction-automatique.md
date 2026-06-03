# Traduction automatique

Le bouton **« Tout traduire »** (page *Groupes de traductions*, `ROLE_INTERNAL`) remplit
automatiquement les traductions manquantes des deux systèmes via une chaîne de services
gratuits, avec barre de progression.

## Périmètre

- **Translations** (clés d'interface) : lignes `Translation` à `content` vide pour les
  locales ≠ locale par défaut, sur les domaines marqués « à exporter ».
- **Intl** (contenu éditorial : `PageIntl`, `ProductIntl`, …) : champs texte vides pour
  les locales ≠ locale par défaut.

La source est toujours le contenu de la **locale par défaut** du site. Seuls les champs
**vides** sont traduits (idempotent : relancer ne réécrit rien de déjà rempli).

> Les contenus Intl traduits sont diffusés sur le front. Ce sont des traductions machine
> **à relire avant publication** (validation humaine).

## Chaîne de providers (bascule automatique)

Ordre de priorité, défini par tag `app.translator_provider` :

1. **DeepL Free** (`DeepLProvider`) — quota mensuel lu via `/v2/usage` (mis en cache 120 s).
   Gère le HTML (`tag_handling=html`). Activé par `DEEPL_ENABLED=true` ; `DEEPL_ENABLED=false`
   (ou clé `DEEPL_API_KEY` vide) le retire de la chaîne.
2. **MyMemory** (`MyMemoryProvider`) — gratuit, 1 requête/texte. Quota journalier épuisé
   mémorisé 6 h en cache. **Ne traite pas le HTML** (les champs riches lui sont sautés).
   `MYMEMORY_EMAIL` (optionnel) relève le quota.
3. **LibreTranslate** (`LibreTranslateProvider`) — auto-hébergé, illimité. Désactivé tant
   que `LIBRETRANSLATE_URL` est vide. `LIBRETRANSLATE_API_KEY` optionnel.

`TranslatorChain` choisit le premier provider disponible (quota suffisant, HTML supporté
si besoin) et bascule au suivant sur quota/erreur. Les bascules sont journalisées dans
`var/log/translation.log` (canal Monolog `translation`).

## Architecture

- Collecte : `ExportService::collectTranslatable(Website)` réutilise la logique d'export
  (provisionne les lignes intl/seo manquantes) et renvoie des groupes
  `{type, locale, group, class?, items:[{ref, source, html}]}`, découpés en lots de 25.
- Persistance + traduction : `MachineTranslationService::translateAndPersist()`.
- Endpoints (`MachineTranslationController`, `ROLE_INTERNAL`) :
  - `GET …/translations/translate/progress` → rend la barre de progression.
  - `POST …/translations/translate/batch` → traduit + persiste un lot. **CSRF** par header
    `X-CSRF-Token` (id `machine_translate`).
- Front : `assets/js/admin/pages/translation.js` (flux `translate*`), template
  `translate-progress.html.twig`, bouton dans `domains.html.twig`.

## Caches

- **Translations** : le flux termine par `admin_translation_cache_clear` puis recharge.
- **Intl** : `CacheInvalidationSubscriber` invalide le result-cache au flush.

## Configuration

Variables dans `.env.local` (template dans `.env.dist`) :

```
DEEPL_API_KEY=xxxxxxxx:fx
MYMEMORY_EMAIL=
LIBRETRANSLATE_URL=
LIBRETRANSLATE_API_KEY=
```

Après ajout de routes : `php bin/console fos:js-routing:dump --format=json` et `yarn build`.
