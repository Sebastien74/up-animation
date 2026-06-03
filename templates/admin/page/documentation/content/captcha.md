# Captcha anti-bot (proof-of-work auto-hébergé)

Protection anti-spam/anti-bot des formulaires publics et du login, **sans tiers**
(pas de Google reCAPTCHA, aucune donnée envoyée à l'extérieur, RGPD-friendly).
Défense en profondeur, cinq couches indépendantes.

> Activation : inchangée. Le toggle `isRecaptcha()` de l'entité (formulaire / configuration)
> décide si le challenge est exigé. Les anciens noms de champs (`field_ho`, `field_ho_entitled`)
> et l'API JS (`generate()` / `onSubmit()`) sont conservés : le mécanisme a été remplacé
> dessous, pas l'intégration.

---

## Les cinq couches

1. **Proof-of-work** (cœur) : le serveur émet un défi ; le client doit retrouver par
   force brute le nombre `n` tel que `SHA-256(salt + n)` égale le challenge. Coût CPU réel
   pour un spammeur de masse, négligeable pour un humain (résolu dans un Web Worker).
2. **Signature HMAC stateless** : le challenge est signé HMAC-SHA256 avec le secret du site
   (`Api::securitySecretKey`). Le serveur ne stocke rien, ne peut pas être trompé par un
   défi forgé, et les sites sont isolés entre eux.
3. **Time-trap** : le `salt` porte l'horodatage d'émission. Rejet si la soumission arrive
   en moins de 2 s (bot) ou après expiration (10 min).
4. **Anti-rejeu** : chaque challenge n'est consommable qu'une fois (nonce en pool `cache.app`).
5. **Honeypot + RateLimiter** : `field_ho_entitled` doit rester vide (piège) ; limiteur IP
   `form_submission` (15/min, `config/packages/rate_limiter.yaml`).

Le format défi/solution est compatible **ALTCHA**, ce qui permettrait de basculer plus tard
sur le widget ALTCHA officiel sans changer le serveur.

---

## Architecture

| Rôle | Fichier |
| --- | --- |
| Cœur émission/vérification (pur, testable) | `src/Service/Security/CaptchaService.php` |
| Secret HMAC par site (génère + persiste) | `src/Service/Security/WebsiteSecretProvider.php` |
| Endpoint de défi JSON | `CryptController::captchaChallenge` (route `front_captcha_challenge`) |
| Solveur navigateur (Web Worker + SHA-256) | `assets/js/vendor/components/recaptcha.js` |
| Gate formulaires front | `src/Service/Content/RecaptchaService.php` |
| Gate login / reset / register | `BaseAuthenticator::checkRecaptcha`, `RecaptchaAuthenticator` |
| Champs cachés | `RecaptchaType`, `LoginType` (front/admin), `recaptcha-fields.html.twig` |

Les trois gates délèguent toutes à `CaptchaService::verify()` : une seule source de vérité
(fini la logique dupliquée et l'accès `filter_input_array` au superglobal).

---

## Flux

1. Au chargement, `generate()` lit `data-challenge` sur `.form-data`, appelle l'endpoint,
   reçoit le défi signé.
2. Le Web Worker résout le proof-of-work, stocke la solution (base64 JSON) sur `.field_ho`.
3. À la soumission, `onSubmit()` recopie la solution dans le champ caché.
4. Côté serveur, `CaptchaService::verify()` contrôle honeypot vide, signature HMAC,
   solution correcte, fenêtre temporelle, et non-rejeu. Échec = flash + log `var/log/spams.log`.

---

## Réglages

Constantes de `CaptchaService` (valeurs par défaut) : `maxNumber = 120000` (difficulté),
`expirySeconds = 600`, `minSolveSeconds = 2`. Augmenter `maxNumber` durcit le coût bot
mais allonge la résolution client.

> Accessibilité : les champs sont `aria-hidden`, `tabindex="-1"`, `autocomplete="off"` et
> **non requis** (un `required` sur champ caché bloquait la soumission sans JS). Sans JS, le
> proof-of-work ne peut être résolu : le serveur rejette proprement avec un message, sans
> blocage silencieux.

---

## Tests

`tests/Service/Security/CaptchaServiceTest.php` (testsuite `security`) : solution valide,
honeypot rempli, signature falsifiée, mauvaise clé site, mauvais nombre, soumission trop
rapide, défi expiré, rejeu, payloads malformés. SHA-256 JS vérifié bit-à-bit contre PHP.

```bash
php bin/phpunit --testsuite security
```

> Build : les assets sont compilés en CI (Node récent). En local, `yarn build` peut échouer
> sur un Node trop ancien (`??=` dans babel-loader) - sans rapport avec ce module.
