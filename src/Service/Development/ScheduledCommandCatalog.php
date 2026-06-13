<?php

declare(strict_types=1);

namespace App\Service\Development;

/**
 * ScheduledCommandCatalog.
 *
 * Single source of truth for the scheduled commands shipped with the
 * application. Consumed by CommandFixtures (new websites) and by the
 * scheduler installer (existing websites).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class ScheduledCommandCatalog
{
    /**
     * Every command the application knows how to schedule.
     *
     * @return array<ScheduledCommandDefinition>
     */
    public function all(): array
    {
        return [
            new ScheduledCommandDefinition('Suppression des données RGPD', 'gdpr:remove', '00 1 * * *', 'Supprime les données personnelles tous les jours à 1H du matin'),
            new ScheduledCommandDefinition('Suppression des tokens utilisateurs', 'security:reset:token', '0 3 * * *', 'Suppression des tokens de plus de 24H, tous les jours à 3H du matin'),
            new ScheduledCommandDefinition('Alertes expiration des mots de passe utilisateurs', 'security:password:expire', '00 11 * * *', "Envoi d'emails (arrive à expiration & à expiré) tous les jours à 11H le matin"),
            new ScheduledCommandDefinition('Synchronisation des Social walls', 'app:feed:sync', '* * * * *', 'Mise à jour des social wall toutes les minutes'),
            new ScheduledCommandDefinition('Agrégation des statistiques', 'app:analytics:rollup', '15 * * * *', 'Reconstruit les buckets horaires et journaliers à partir des événements bruts', true),
            new ScheduledCommandDefinition('Purge des statistiques', 'app:analytics:purge', '30 3 * * *', 'Supprime les événements bruts au-delà de la fenêtre de rétention', true),
            new ScheduledCommandDefinition('Rotation du cache expiré', 'cache:pool:prune', '45 3 * * *', 'Supprime les entrées de cache expirées des pools (rotation type logs, compatible mutualisé), tous les jours à 3H45', true),
            new ScheduledCommandDefinition('Grand ménage du cache (hebdomadaire)', 'app:cache:reclaim', '0 4 * * 0', "Vide cache.app pour récupérer les entrées versionnées orphelines (fragments, result-cache). Vague de cache-miss assumée : dimanche 4H, inactive par défaut, à activer en admin uniquement si la pression disque le justifie", false, true),
            new ScheduledCommandDefinition('Refresh token Instagram', 'app:instagram:refresh-token', '0 4 * * 1', 'Renouvelle les tokens Instagram avant expiration (60 jours)', true),
            new ScheduledCommandDefinition('Refresh token TikTok', 'app:tiktok:refresh-token', '0 */6 * * *', 'Renouvelle les tokens TikTok avant expiration (24 heures)', true),
            new ScheduledCommandDefinition('Préchauffage du cache des pages', 'app:cache:warmup', '0 5 * * *', 'Rejoue les URLs du sitemap en HTTP pour reconstruire les caches front (result-cache, fragments Twig) avant expiration, tous les jours à 5H', true),
            new ScheduledCommandDefinition('Analyse périodique des pages', 'app:page-analysis:run', '0 6 * * 1', 'Analyse en HTTP les pages publiées (perf & rendu) et historise les indices, tous les lundis à 6H', true),
        ];
    }

    /**
     * The commands provisioned on every site by the standard install:
     * the active baseline plus opt-in tasks shipped inactive (admin enables them).
     *
     * @return array<ScheduledCommandDefinition>
     */
    public function defaults(): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn (ScheduledCommandDefinition $definition): bool => $definition->active || $definition->installByDefault,
        ));
    }
}
