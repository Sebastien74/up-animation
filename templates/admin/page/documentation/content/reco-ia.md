# Reco IA - AI Overviews et AI Mode (Google)

## Contexte

Google déploie en France ses fonctionnalités de recherche générative **d'ici le 23 septembre 2026** (officialisé le 29 juin 2026) :

- **AI Overviews** : résumé généré par IA affiché au-dessus des résultats de recherche, avec liens sources.
- **AI Mode** : mode conversationnel intégré au moteur (type ChatGPT), basé sur Gemini.

Ces fonctionnalités sont actives dans plus de 120 pays depuis 2024. Les données observées ailleurs :

| Fait mesuré | Valeur | Source |
|---|---|---|
| Baisse de CTR position 1 quand un AI Overview s'affiche | ~ -58 % | Ahrefs, février 2026 |
| Recherches avec AI Overview sans aucun clic | 83 % | études 2026 |
| Recherches en AI Mode sans aucun clic | 93 % | études 2026 |
| Requêtes e-commerce/achat déclenchant un AI Overview | ~ 3 % | contre 43 % en santé/info |
| Gain de clics pour une marque citée dans l'AI Overview | +35 % | vs marque non citée |

**Hypothèse de travail pour ce site** : impact faible à modéré. Les requêtes visées (prestations événementielles, location, intention d'achat locale) font partie des moins touchées. À vérifier avec la baseline (voir plus bas), pas une certitude.

## Position officielle de Google (mai 2026)

Guide officiel : [Google Search Central - AI features](https://developers.google.com/search/docs/appearance/ai-features).

- **Aucun prérequis technique nouveau.** Une page apparaît dans les AI Overviews si elle est indexable et éligible aux snippets. C'est du SEO classique.
- **Mythes à ignorer** (Google est explicite) : fichier `llms.txt` (non utilisé par Google Search), "schema IA" spécial, découpage du contenu en petits blocs, réécriture "pour les LLM".
- Ce qui compte : contenu unique avec point de vue propre, structure claire (titres, sections), crawlabilité, données structurées pour les rich results, Google Business Profile pour le local.

## État du site (conforme)

Rien de bloquant. Le site coche déjà toutes les cases :

- **Rendu 100 % serveur** (Twig) : contenu indexable sans JS.
- **Meta robots** : `index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1` (`templates/front/default/include/seo.html.twig`). `max-snippet:-1` autorise Google à utiliser le contenu dans les AI Overviews sans limite. Pour retirer une page des réponses IA : `nosnippet` ou `max-snippet` réduit (contrôle par page via l'admin SEO).
- **JSON-LD** : Organization/LocalBusiness, WebSite, Product, Article, JobPosting, FAQPage, ItemList, BreadcrumbList (`SeoService` + templates `microdata`).
- **robots.txt dynamique** + sitemap XML multilingue, canonical, hreflang.
- **Bots IA non bloqués** : `RobotsService` n'émet aucune directive `Google-Extended`, `GPTBot`, etc. Tout est autorisé. Note : bloquer `Google-Extended` n'empêche PAS d'apparaître dans les AI Overviews (elles utilisent l'index Googlebot classique), cela bloque uniquement l'entraînement de Gemini. Choix actuel : ouvert, cohérent avec un objectif de visibilité.

## FAQ sur les fiches produits (dispositif principal)

Les AI Overviews citent en priorité les pages qui répondent directement à une question. Le levier : des blocs question/réponse sur les fiches prestations, avec le JSON-LD `FAQPage` émis automatiquement.

### Fonctionnement (depuis juillet 2026)

Chaque produit du catalogue peut être associé à une FAQ :

1. Créer une FAQ et ses questions dans **Admin > FAQ** (une FAQ peut être partagée par plusieurs produits, ex. une FAQ par catégorie de prestations).
2. Dans la fiche produit, onglet **Configuration**, champ **FAQ associée** : sélectionner la FAQ.
3. La FAQ s'affiche en bas de la fiche (accordéon) et le JSON-LD `FAQPage` est injecté dans la page.

Technique : relation nullable `Product.faq` (migration `Version20260705120000`), rendu via `FaqController::view` dans `actions/catalog/view.html.twig`, JSON-LD géré par `FaqModel` (fonctionne aussi hors bloc de layout). La case "Désactiver les microdonnées" de la FAQ reste respectée.

**Cache** : après association ou modification d'une FAQ, si le changement n'apparaît pas en front, invalider le cache du site (Dashboard > Invalider le cache du site, bump de `cacheClearDate`).

### Rédaction des questions (règles)

- De vraies questions clients : tarif, délai de réservation, zone d'intervention, durée, nombre de participants, matériel fourni, conditions météo/annulation.
- Réponse complète et autonome en 2-4 phrases : l'IA cite les réponses qui se suffisent à elles-mêmes.
- Pas de claims invérifiables ni de superlatifs : chiffres et engagements réels uniquement.

## Plan d'action avant mi-septembre 2026

1. **Baseline (à faire en priorité, ~1 h)** : exporter Search Console + Matomo (impressions, clics, CTR par page et requête) sur juillet-août 2026. Sans référence avant lancement, impossible de distinguer en octobre l'effet AI Overviews de la saisonnalité.
2. **Contenu FAQ** : associer une FAQ aux fiches prestations principales (commencer par les plus vues selon Matomo).
3. **Google Business Profile** : fiche complète et à jour (catégories, zone, photos, horaires). Signal cité par Google pour les réponses IA locales.
4. **Suivi (à partir d'octobre)** : rapports Search Console (les performances AI Mode/AI Overviews y sont remontées). Ne pas réagir avant d'avoir 4-6 semaines de données.

## KPI de suivi

- Impressions et clics organiques (GSC), par page prestation, comparés à la baseline.
- CTR des requêtes marque vs hors-marque.
- Apparitions/citations dans les AI Overviews (rapport GSC dédié).
- Demandes de devis (formulaires) : l'indicateur final, un clic perdu sur une requête info n'est pas une conversion perdue.

## Sources

- [Guide officiel Google - AI features et votre site](https://developers.google.com/search/docs/appearance/ai-features)
- [Guide d'optimisation IA - Google Search Central](https://developers.google.com/search/docs/fundamentals/ai-optimization-guide)
- [Abondance - AI Overviews en France](https://www.abondance.com/20260630-2528492-google-ai-overviews-arrivee-france-ete.html)
- [Search Engine Journal - "AEO/GEO is still SEO"](https://www.searchenginejournal.com/googles-new-ai-search-guide-calls-aeo-and-geo-still-seo/575026/)
- [Am I Cited - Google-Extended](https://www.amicited.com/blog/google-extended-what-it-does-should-you-block-it/)
