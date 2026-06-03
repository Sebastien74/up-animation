# Progressive Web App (PWA)

Système PWA du **front public** : manifest dynamique par site, service worker avec page
hors-ligne, et invite d'installation personnalisée. Réservé au mobile.

> Le flag **`Configuration::$progressiveWebApp`** (case à cocher dans la configuration du
> site) est l'interrupteur unique : il pilote **à la fois** l'enregistrement du service
> worker **et** l'émission du `<link rel="manifest">`. PWA désactivée = aucun des deux.

---

## Composants

| Rôle | Fichier |
| --- | --- |
| Service worker (network-first + fallback offline) | `public/service-worker.js` |
| Enregistrement SW + UI d'installation + garde HTTPS | `public/service-worker/script.js` |
| Page hors-ligne (standalone, sans build) | `public/offline.php` |
| Manifest dynamique par site (filtre Twig `\|manifest`) | `src/Twig/Content/ManifestRuntime.php` |
| Partial d'install (modale HTTPS + bouton) | `templates/front/default/include/service-worker.html.twig` |
| `<link rel="manifest">` | `templates/core/favicon.html.twig` |

---

## Conditions d'activation

Le service worker n'est chargé que si **toutes** ces conditions sont vraies
(`templates/front/default/base.html.twig`) :

- `not isDesktop()` : mobile uniquement ;
- `configuration.progressiveWebApp` : flag activé pour le site ;
- `'APP_ENV_NAME'|getEnv != 'local'` : jamais en local ;
- `not onlyBody`.

Le `<link rel="manifest">` (et la génération du fichier manifest) est gardé sur le **même
flag** `progressiveWebApp`, passé à `favicon.html.twig` via `pwaEnabled`. Aucun manifest
n'est ni annoncé ni généré quand la PWA est désactivée.

> Installabilité : le navigateur n'affiche l'invite que sur **HTTPS** avec un service worker
> qui gère l'event `fetch`. `prefer_related_applications` est à **`false`** dans le manifest :
> le passer à `true` sans `related_applications` supprimerait l'invite d'installation web.

---

## Manifest dynamique

`ManifestRuntime::manifest()` génère un fichier
`public/manifest.webmanifest.<env>.<slug>.json` **par site**, mis en cache sur disque et
régénéré automatiquement si le host change. Champs : `display: standalone`, `start_url: /`,
`scope: /`, `theme_color` / `background_color` tirés des couleurs `favicon` du site, et les
icônes (144 / 192 / 512 + `mask-icon` en `any maskable`) construites depuis les logos
présents dans `/public/uploads/<uploadDirname>/`.

---

## Cache hors-ligne

Stratégie minimale : seul `offline.php` est mis en cache à l'installation du SW. Les
navigations passent par le réseau (avec navigation preload) ; en cas d'échec réseau, la
page hors-ligne est servie. Il n'y a **pas** de precaching des assets ni des pages visitées
(pas de Workbox).

---

## Tests

`tests/Twig/Content/ManifestRuntimeTest.php` (testsuite `twig`) verrouille le contrat du
manifest : JSON valide, `prefer_related_applications: false`, `display`/`start_url`/`scope`,
nom, couleurs, et construction des icônes (dont `mask-icon` maskable).

```bash
php bin/phpunit --testsuite twig
```
