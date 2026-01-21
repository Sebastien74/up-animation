<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core\Website;
use App\Entity\Module\Newsletter\Campaign;
use App\Entity\Module\Newsletter\CampaignIntl;
use App\Entity\Security\User;
use App\Service\Interface\CoreLocatorInterface;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * NewsletterFixtures.
 *
 * Newsletter Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => NewsletterFixtures::class, 'key' => 'newsletter_fixtures'],
])]
class NewsletterFixtures
{
    /**
     * NewsletterFixtures constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * Add Campaign.
     *
     * @throws Exception
     */
    public function add(Website $website, ?User $user = null): void
    {
        $campaign = new Campaign();
        $campaign->setAdminName('Principale');
        $campaign->setWebsite($website);
        $campaign->setSlug('main');
        $campaign->setSecurityKey($this->coreLocator->alphanumericKey(10));
        $campaign->setCreatedBy($user);

        $confirmation = '<p>Bonjour,</p>';
        $confirmation .= '<p>Vous avez demandé à recevoir notre newsletter.<br>';
        $confirmation .= 'Pour finaliser votre inscription et commencer à recevoir nos actualités, veuillez confirmer votre adresse e-mail en cliquant sur le lien ci-dessous :</p>';
        $confirmation .= '<p><strong><a href="%confirmationLink%">Confirmer mon inscription</a></strong></p>';
        $confirmation .= "<p>Ce lien est valable pendant 24 heures. Passé ce délai, vous devrez effectuer une nouvelle demande d'inscription.</p>";
        $confirmation .= "<p>Si vous n’êtes pas à l’origine de cette demande, vous pouvez ignorer cet e-mail. Aucune action ne sera entreprise sans votre confirmation.</p>";
        $confirmation .= "<p>Merci et à bientôt.</p>";

        $intl = new CampaignIntl();
        $intl->setWebsite($website);
        $intl->setLocale($website->getConfiguration()->getLocale());
        $intl->setIntroduction("J'accepte que mes données soient utilisées pour me recontacter dans le cadre de cette demande.");
        $intl->setBody($confirmation);

        $campaign->addIntl($intl);

        $this->coreLocator->em()->persist($campaign);
    }
}
