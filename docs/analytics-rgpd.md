# Module analytics - conformite RGPD

Ce document decrit le traitement effectue par le module analytics interne (route `/a/c` + dashboard `/admin/stats`). Il sert de support au registre des traitements (article 30 RGPD) et a la justification de l'exemption de consentement (article 7 RGPD, lignes directrices CNIL "mesure d'audience").

## 1. Finalite

Mesurer l'audience du site (visiteurs, sessions, pages vues, sources, pays, appareils) pour piloter le contenu et la performance editoriale. Aucun croisement avec d'autres traitements, aucun ciblage publicitaire, aucun partage avec un tiers.

## 2. Base legale

Interet legitime du responsable de traitement (article 6.1.f). Conformement a la doctrine CNIL, ce traitement est exempte de consentement prealable (et donc de bandeau cookies bloquant) car :

- il sert exclusivement la mesure d'audience du site,
- les donnees ne sont pas recoupees avec d'autres traitements,
- elles ne sont pas transmises a des tiers,
- aucun identifiant persistant cote client n'est utilise (pas de cookie, pas de localStorage),
- la duree de conservation des donnees detaillees ne depasse pas 13 mois.

## 3. Donnees collectees

Pour chaque evenement, le module persiste :

| Champ            | Type        | Origine                                   | PII ?          |
|------------------|-------------|-------------------------------------------|----------------|
| `sessionHash`    | hex(32)     | sha256(sel quotidien + IP + UA), tronque  | Pseudonymise   |
| `websiteId`      | int         | Resolu via Host                           | Non            |
| `occurredAt`     | datetime    | Horodatage UTC                            | Non            |
| `eventType`      | string(16)  | pageview / click / scroll / form          | Non            |
| `urlPath`        | string(512) | location.pathname + location.search       | Non            |
| `referrerDomain` | string(190) | Domaine seulement, jamais l'URL complete  | Non            |
| `countryCode`    | char(2)     | Resolu via geo-IP offline (cf. section 7) | Non            |
| `device`         | string(16)  | UA parsing (desktop/mobile/tablet)        | Non            |
| `browser`        | string(32)  | UA parsing (chrome/firefox/...)           | Non            |
| `os`             | string(32)  | UA parsing (windows/macos/...)            | Non            |
| `locale`         | string(8)   | document.documentElement.lang             | Non            |
| `viewport`       | string(16)  | window.innerWidth x innerHeight           | Non            |
| `eventPayload`   | JSON        | Donnees libres (max 16 cles, 256 c.)      | Non par design |

L'adresse IP brute et le User-Agent brut ne sont **jamais** persistes. Ils sont utilises uniquement dans le cycle HTTP de la requete d'ingestion, puis disparaissent.

## 4. Anonymisation

Le `sessionHash` est calcule selon `sha256(sel_quotidien || ip || ua)` puis tronque a 16 octets (32 caracteres hex). Le sel est :

- genere aleatoirement (`random_bytes(32)`) a la premiere requete du jour,
- stocke dans le cache applicatif Symfony (`cache.app`),
- conserve 25 heures pour couvrir la rotation,
- detruit a expiration : impossible apres coup de retrouver l'IP d'origine a partir du hash.

La rotation quotidienne du sel empeche le recoupement d'un visiteur d'un jour sur l'autre. C'est cette propriete qui rend le traitement compatible avec l'exemption CNIL.

## 5. Duree de conservation

| Table                | Granularite | Conservation   |
|----------------------|-------------|----------------|
| `analytics_event`    | Brut        | 30 jours       |
| `analytics_hourly`   | Agrege H    | 12 mois        |
| `analytics_daily`    | Agrege J    | Illimitee      |

La purge des tables `event` et `hourly` est assuree par la commande `app:analytics:purge` (cron nocturne). Les agregats journaliers ne contiennent **aucun** identifiant de session : ce sont des compteurs purs (visiteurs, sessions, pages vues) par dimension (urlPath, countryCode, device).

## 6. Securite

- Endpoint POST `/a/c` rate-limite (120 req/min par IP, token bucket).
- Bot filtering en amont du rate limiter (regex sur UA).
- Ingestion async via Symfony Messenger : le payload qui passe par la queue ne contient deja plus ni IP ni UA brut.
- Aucun `dump`, `var_dump` ni journalisation des donnees brutes.
- Dashboard accessible uniquement aux roles `ROLE_ADMIN` (scope par `Website`).
- Export CSV restreint aux agregats journaliers (pas d'export du brut).

## 7. Geo-IP

L'interface `App\Service\Analytics\GeoIpResolverInterface` definit le contrat de resolution pays. L'implementation par defaut est `NullGeoIpResolver` (renvoie `null`).

Pour activer une vraie resolution, deux options :

1. **MaxMind GeoLite2-Country (recommande)** : installer `geoip2/geoip2` via Composer, telecharger le fichier `.mmdb` depuis un compte MaxMind (gratuit), creer un `MaxMindGeoIpResolver` implementant l'interface, et le binder a la place de `NullGeoIpResolver`. La resolution se fait localement, jamais d'appel HTTP sortant.
2. **Alternative offline** : tout autre fournisseur de base IP -> pays, du moment que la lookup est strictement locale.

Tant qu'aucun resolver n'est branche, le champ `countryCode` reste nul et le tableau "Pays" du dashboard est vide. Aucun impact fonctionnel sur les autres metriques.

## 8. Information des personnes

Le module ne necessite pas de bandeau cookie bloquant (cf. section 2). En revanche, la politique de confidentialite du site doit mentionner :

- l'existence de la mesure d'audience,
- la duree de conservation,
- le caractere anonymise (pseudonymisation par sel rotatif),
- l'absence de transmission a des tiers,
- les modalites d'exercice des droits (acces, opposition).

Un paragraphe type :

> Ce site mesure son audience via un module interne, sans cookie ni identifiant persistant. Les adresses IP et les agents utilisateur ne sont jamais conserves : seul un identifiant de session anonyme, derive d'un sel rotatif quotidien et impossible a relier a une personne au-dela de 24 heures, est temporairement stocke. Les donnees detaillees sont supprimees apres 30 jours, seuls subsistent des compteurs agreges par jour. Aucune donnee n'est transmise a un tiers.

## 9. Responsable de traitement

A renseigner par client : raison sociale, adresse, contact DPO le cas echeant.

## 10. Sous-traitants

Aucun. Le traitement est strictement interne au socle applicatif.

## 11. Transferts hors UE

Aucun.

## 12. Droits des personnes

- **Acces / rectification** : les donnees etant pseudonymisees sans cle de re-identification, l'exercice direct n'est techniquement pas possible. Une demande peut conduire a la suppression de toutes les sessions sur la fenetre couverte.
- **Opposition** : possible en bloquant l'endpoint `/a/c` cote client (bloqueur de scripts ou requete) ou en demandant l'exclusion de l'IP par le responsable de traitement.
- **Effacement** : commande `app:analytics:purge` avec `--event-retention-days=0` pour purger immediatement le brut. Les agregats journaliers ne contenant aucun identifiant ne sont pas concernes.

## 13. Verification

Avant mise en production :

- [ ] Politique de confidentialite mise a jour avec le paragraphe analytics.
- [ ] Cron `app:analytics:rollup` planifie quotidiennement.
- [ ] Cron `app:analytics:purge` planifie quotidiennement.
- [ ] Geo-IP resolver branche si la mesure pays est attendue.
- [ ] Backups DB documentes (les agregats journaliers doivent etre sauvegardes au meme titre que le reste).
- [ ] Test fonctionnel : verifier que la table `analytics_event` se remplit et qu'aucun champ ne contient d'IP ou d'UA brut.
