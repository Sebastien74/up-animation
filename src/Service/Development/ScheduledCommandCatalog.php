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
            new ScheduledCommandDefinition('Refresh token Instagram', 'app:instagram:refresh-token', '0 4 * * 1', 'Renouvelle les tokens Instagram avant expiration (60 jours)', true),
            new ScheduledCommandDefinition('Refresh token TikTok', 'app:tiktok:refresh-token', '0 */6 * * *', 'Renouvelle les tokens TikTok avant expiration (24 heures)', true),
        ];
    }

    /**
     * The commands active by default (the four we want running everywhere).
     *
     * @return array<ScheduledCommandDefinition>
     */
    public function defaults(): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn (ScheduledCommandDefinition $definition): bool => $definition->active,
        ));
    }
}
