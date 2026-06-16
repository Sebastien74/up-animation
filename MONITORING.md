# Suivi de stabilité & maintenance — outils recommandés

Panorama des API gratuites (ou à offre gratuite) et bundles Symfony utiles pour
surveiller la **stabilité** (le site répond et ne plante pas) et la **maintenance**
(dépendances, sécurité, santé SEO/performance) du projet.

Pour chaque outil : **description**, **impact** et **utilité** concrète pour ce projet
(CMS Symfony custom). Les quotas des offres gratuites évoluent — à revérifier au moment
du choix.

> ⚠️ **RGPD** : brancher un service externe est un traitement de données (URLs, parfois
> données utilisateurs pour le Real User Monitoring). À valider selon l'outil et son lieu
> d'hébergement (UE ou hors UE), idéalement via un DPA.

---

## 1. Disponibilité / stabilité (le site répond-il ?)

### Uptime Kuma
- **Description** : moniteur de disponibilité open source, auto-hébergé (HTTP, ping, port, certificat SSL).
- **Impact** : alerte (mail, Slack, webhook…) dès qu'une URL ne répond plus ou qu'un certificat expire.
- **Utilité** : 100 % gratuit si on dispose d'un petit serveur ; tableau de bord de statut clair, sans dépendance à un tiers.

### UptimeRobot
- **Description** : service externe de surveillance d'URL.
- **Impact** : notification en cas d'indisponibilité, historique de disponibilité.
- **Utilité** : mise en place immédiate, offre gratuite (contrôles toutes les 5 min) ; alternative SaaS à Uptime Kuma.

### Better Stack / StatusCake / Hyperping
- **Description** : services externes de monitoring + pages de statut publiques.
- **Impact** : disponibilité, latence, page de statut pour les clients.
- **Utilité** : offres gratuites limitées ; pertinent si une page de statut publique est souhaitée.

### Healthchecks.io
- **Description** : surveillance de tâches planifiées (cron), service + open source auto-hébergeable.
- **Impact** : alerte si un job attendu ne « ping » pas dans la fenêtre prévue.
- **Utilité** : surveille les crons critiques (génération sitemap, vidage de cache, imports, mesures PageSpeed planifiées).

---

## 2. Suivi des erreurs runtime (cœur de la stabilité)

### Sentry
- **Description** : suivi d'exceptions PHP/JS en production. Bundle `sentry/sentry-symfony`.
- **Impact** : chaque erreur est capturée avec sa stack trace, le contexte requête/utilisateur et la fréquence.
- **Utilité** : **le meilleur rapport valeur/effort pour la maintenance** ; offre gratuite (~5k erreurs/mois). Détecte les régressions avant les utilisateurs.

### GlitchTip
- **Description** : alternative open source **compatible API Sentry**, auto-hébergeable.
- **Impact** : identique à Sentry (capture d'erreurs).
- **Utilité** : 100 % gratuit en auto-hébergé ; on réutilise le bundle Sentry en le pointant vers son instance. Idéal si les données doivent rester chez soi.

---

## 3. Sécurité & maintenance des dépendances

### `composer audit`
- **Description** : commande intégrée à Composer qui croise les dépendances avec les CVE connues.
- **Impact** : signale les paquets vulnérables installés.
- **Utilité** : **gratuit, zéro dépendance** ; à exécuter en CI et avant chaque mise en production.

### GitHub Dependabot
- **Description** : surveillance des dépendances et mises à jour automatisées (déjà disponible sur GitHub).
- **Impact** : alertes de sécurité + pull requests de montée de version.
- **Utilité** : gratuit, automatique ; maintient les dépendances à jour sans effort manuel.

### nelmio/security-bundle
- **Description** : bundle Symfony d'en-têtes de sécurité (CSP, HSTS, X-Frame-Options…).
- **Impact** : durcit la configuration HTTP contre XSS/clickjacking.
- **Utilité** : améliore la note « Bonnes pratiques » et la sécurité réelle du front.

---

## 4. Santé SEO & Web Vitals réels (prolonge l'intégration PageSpeed)

### Google Search Console API
- **Description** : API gratuite exposant indexation, couverture, Core Web Vitals terrain (CrUX), actions manuelles.
- **Impact** : vue de la santé SEO réelle et des problèmes d'exploration côté Google.
- **Utilité** : complément naturel de l'outil PageSpeed déjà en place ; intégrable dans le dashboard d'analyse.

### CrUX API (Chrome UX Report)
- **Description** : API gratuite des données terrain (utilisateurs réels) par URL/origine.
- **Impact** : LCP/INP/CLS réels, au-delà de la mesure labo.
- **Utilité** : déjà partiellement consommée via PageSpeed ; interrogeable directement pour un suivi de tendance.

### Bibliothèque `web-vitals` (JS, open source)
- **Description** : librairie Google mesurant LCP/INP/CLS côté navigateur.
- **Impact** : collecte des Web Vitals des vrais visiteurs.
- **Utilité** : le projet possède déjà un endpoint analytics (`IngestController`) — on peut stocker ces métriques en interne, sans service tiers ni enjeu RGPD externe.

---

## 5. Performance / profilage

### Blackfire / Tideways
- **Description** : profilage applicatif (APM) pour Symfony/PHP.
- **Impact** : identifie les goulots d'étranglement (requêtes SQL, temps CPU, mémoire).
- **Utilité** : offres gratuites limitées ; utile ponctuellement pour optimiser une page lente.

### Symfony Profiler
- **Description** : barre de débogage intégrée (composant `symfony/web-profiler-bundle`).
- **Impact** : détail des requêtes, du temps, des logs en développement.
- **Utilité** : gratuit, mais **dev uniquement** (jamais en production).

---

## Priorités recommandées (agence, Symfony custom)

Par rapport valeur / effort :

1. **Sentry ou GlitchTip** — suivi des erreurs runtime : l'impact maintenance le plus immédiat.
2. **`composer audit` en CI + Dependabot** — sécurité des dépendances, quasi zéro effort.
3. **Search Console API** — santé SEO/Web Vitals, à côté du panneau PageSpeed existant.
4. **Uptime Kuma ou UptimeRobot** — disponibilité + alertes.
5. **Healthchecks.io** — si des crons critiques existent.
