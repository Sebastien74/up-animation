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
            ['id' => 'newsletter-confirmation', 'label' => 'Newsletter - confirmation double opt-in', 'description' => 'Mail envoye au visiteur apres inscription a la newsletter pour confirmer son adresse.'],
            ['id' => 'newsletter-webmaster',    'label' => 'Newsletter - alerte webmaster',          'description' => 'Notification interne envoyee a l equipe quand un nouvel inscrit confirme.'],
            ['id' => 'contact-form',            'label' => 'Formulaire de contact - notification webmaster', 'description' => 'Email recu par le webmaster avec les champs remplis du formulaire.'],
            ['id' => 'contact-confirmation',    'label' => 'Formulaire de contact - confirmation',   'description' => 'Accuse de reception envoye au visiteur ayant rempli un formulaire de contact.'],
            ['id' => 'registration',            'label' => 'Inscription - confirmation utilisateur', 'description' => 'Lien d activation envoye apres creation de compte cote front.'],
            ['id' => 'reset-password',          'label' => 'Reinitialisation mot de passe',          'description' => 'Lien de reset envoye sur demande utilisateur (front comme admin).'],
            ['id' => 'password-expire',         'label' => 'Mot de passe expire',                    'description' => 'Alerte cron quand le mot de passe arrive a expiration ou est expire.'],
            ['id' => '2fa-code',                'label' => '2FA - code de verification',             'description' => 'Code a usage unique envoye lors d une connexion avec 2FA email.'],
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
