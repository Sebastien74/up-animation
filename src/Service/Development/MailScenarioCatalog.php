<?php

declare(strict_types=1);

namespace App\Service\Development;

final class MailScenarioCatalog
{
    /**
     * @return list<array{id: string, label: string, description: string}>
     */
    public function all(): array
    {
        return [
            ['id' => 'newsletter-confirmation', 'label' => 'Newsletter - confirmation double opt-in',          'description' => 'Mail envoyé au visiteur après inscription à la newsletter pour confirmer son adresse.'],
            ['id' => 'newsletter-webmaster',    'label' => 'Newsletter - alerte webmaster',                    'description' => 'Notification interne envoyée à l\'équipe quand un nouvel inscrit confirme.'],
            ['id' => 'contact-form',            'label' => 'Formulaire de contact - notification webmaster',   'description' => 'Email reçu par le webmaster avec les champs remplis du formulaire.'],
            ['id' => 'contact-confirmation',    'label' => 'Formulaire de contact - confirmation',             'description' => 'Accusé de réception envoyé au visiteur ayant rempli un formulaire de contact.'],
            ['id' => 'registration',            'label' => 'Inscription - confirmation utilisateur',           'description' => 'Lien d\'activation envoyé après création de compte côté front.'],
            ['id' => 'reset-password',          'label' => 'Réinitialisation mot de passe',                    'description' => 'Lien de reset envoyé sur demande utilisateur (front comme admin).'],
            ['id' => 'password-expire',         'label' => 'Mot de passe expiré',                              'description' => 'Alerte cron quand le mot de passe arrive à expiration ou est expiré.'],
            ['id' => '2fa-code',                'label' => '2FA - code de vérification',                       'description' => 'Code à usage unique envoyé lors d\'une connexion avec 2FA email.'],
        ];
    }

    public function has(string $id): bool
    {
        foreach ($this->all() as $scenario) {
            if ($scenario['id'] === $id) {
                return true;
            }
        }

        return false;
    }
}
