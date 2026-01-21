<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core\Website;
use App\Entity\Information as InfoEntities;
use App\Entity\Security\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * InformationFixtures.
 *
 * Information Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => InformationFixtures::class, 'key' => 'information_fixtures'],
])]
class InformationFixtures
{
    private const array ZONES = ['contact', 'footer'];

    private array $yamlConfiguration = [];

    /**
     * InformationFixtures constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Add Information.
     */
    public function add(Website $website, array $yamlConfiguration, ?User $user = null): InfoEntities\Information
    {
        $this->yamlConfiguration = $yamlConfiguration;

        $configuration = $website->getConfiguration();
        $allLocales = $configuration->getAllLocales();
        $defaultLocale = $website->getConfiguration()->getLocale();

        $information = new InfoEntities\Information();
        $information->setWebsite($website);

        foreach ($allLocales as $locale) {
            $schedules = !empty($this->yamlConfiguration['information'][$locale]['schedules']) ? $this->yamlConfiguration['information'][$locale]['schedules'] : null;
            $alert = !empty($this->yamlConfiguration['information'][$locale]['alert']) ? $this->yamlConfiguration['information'][$locale]['alert'] : null;
            $informationIntl = new InfoEntities\InformationIntl();
            $informationIntl->setBody($schedules);
            $informationIntl->setPlaceholder($alert);
            $informationIntl->setLocale($locale);
            $informationIntl->setWebsite($website);
            $information->addIntl($informationIntl);
        }

        $website->setCreatedBy($user);
        $website->setInformation($information);

        foreach ($allLocales as $locale) {
            $this->addSocialNetworks($information, $locale, $defaultLocale);
            $this->addPhones($information, $locale, $defaultLocale);
            $this->addEmails($information, $locale, $defaultLocale);
            $this->addAddresses($information, $locale, $defaultLocale);
            $this->addLegacy($information, $locale, $defaultLocale);
            $this->addIntl($information, $locale);
        }

        $this->entityManager->persist($information);
        $this->entityManager->persist($website);

        return $information;
    }

    /**
     * Add social networks.
     */
    private function addSocialNetworks(InfoEntities\Information $information, string $locale, string $defaultLocale): void
    {
        $socialNetwork = new InfoEntities\SocialNetwork();
        $socialNetwork->setLocale($locale);
        $socialNetworks = !empty($this->yamlConfiguration['social_networks'][$locale])
            ? $this->yamlConfiguration['social_networks'][$locale] : (!empty($this->yamlConfiguration['social_networks'][$defaultLocale])
                ? $this->yamlConfiguration['social_networks'][$defaultLocale] : []);
        foreach ($socialNetworks as $name => $url) {
            $setter = 'set'.ucfirst($name);
            $socialNetwork->$setter($url);
        }
        $information->addSocialNetwork($socialNetwork);
    }

    /**
     * Add phones.
     */
    private function addPhones(InfoEntities\Information $information, string $locale, string $defaultLocale): void
    {
        $phones = !empty($this->yamlConfiguration['phones'][$locale])
            ? $this->yamlConfiguration['phones'][$locale] : (!empty($this->yamlConfiguration['phones'][$defaultLocale])
                ? $this->yamlConfiguration['phones'][$defaultLocale] : []);

        foreach ($phones as $phoneData) {
            $zones = isset($phoneData['zones']) && is_array($phoneData['zones']) ? $phoneData['zones'] : self::ZONES;
            $phone = new InfoEntities\Phone();
            $phone->setLocale($locale);
            $phone->setNumber($phoneData['number']);
            $phone->setTagNumber($phoneData['tag_number']);
            $phone->setType($phoneData['type']);
            $phone->setZones($zones);
            $information->addPhone($phone);
        }
    }

    /**
     * Add emails.
     */
    private function addEmails(InfoEntities\Information $information, string $locale, string $defaultLocale): void
    {
        $haveSupport = false;
        $haveNoReply = false;

        $emails = !empty($this->yamlConfiguration['emails'][$locale])
            ? $this->yamlConfiguration['emails'][$locale] : (!empty($this->yamlConfiguration['emails'][$defaultLocale])
                ? $this->yamlConfiguration['emails'][$defaultLocale] : []);

        foreach ($emails as $emailData) {
            $zones = isset($emailData['zones']) && is_array($emailData['zones']) ? $emailData['zones'] : self::ZONES;
            $slug = !empty($emailData['slug']) ? $emailData['slug'] : null;
            $deletable = true;

            if ('support' === $slug || 'no-reply' === $slug) {
                $deletable = false;
            }

            if ('support' === $slug) {
                $haveSupport = true;
            } elseif ('no-reply' === $slug) {
                $haveNoReply = true;
            }

            $email = new InfoEntities\Email();
            $email->setLocale($locale);
            $email->setSlug($slug);
            $email->setDeletable($deletable);
            $email->setEmail($emailData['email']);

            if ('support' !== $slug && 'no-reply' !== $slug) {
                $email->setZones($zones);
            }

            $information->addEmail($email);
        }

        if (!$haveSupport) {
            $supportEmail = new InfoEntities\Email();
            $supportEmail->setSlug('support');
            $supportEmail->setLocale($locale);
            $supportEmail->setEmail('support@agence-felix.fr');
            $supportEmail->setDeletable(false);
            $information->addEmail($supportEmail);
        }

        if (!$haveNoReply) {
            $noReplyEmail = new InfoEntities\Email();
            $noReplyEmail->setLocale($locale);
            $noReplyEmail->setSlug('no-reply');
            $noReplyEmail->setEmail('no-reply@agence-felix.fr');
            $noReplyEmail->setDeletable(false);
            $information->addEmail($noReplyEmail);
        }
    }

    /**
     * Add addresses.
     */
    private function addAddresses(InfoEntities\Information $information, string $locale, string $defaultLocale): void
    {
        $addresses = !empty($this->yamlConfiguration['addresses'][$locale])
            ? $this->yamlConfiguration['addresses'][$locale] : (!empty($this->yamlConfiguration['addresses'][$defaultLocale])
                ? $this->yamlConfiguration['addresses'][$defaultLocale] : []);

        foreach ($addresses as $addressData) {
            $zones = isset($addressData['zones']) && is_array($addressData['zones']) ? $addressData['zones'] : array_merge(self::ZONES, ['email']);
            $address = new InfoEntities\Address();
            $address->setLocale($locale);
            $address->setName($addressData['name']);
            $address->setLatitude($addressData['latitude']);
            $address->setLongitude($addressData['longitude']);
            $address->setAddress($addressData['address']);
            $address->setZipCode($addressData['zipcode']);
            $address->setCity($addressData['city']);
            $address->setDepartment($addressData['department']);
            $address->setRegion($addressData['region']);
            $address->setCountry($addressData['country']);
            $address->setSchedule($addressData['schedule']);
            $address->setGoogleMapUrl($addressData['googleMapUrl']);
            $address->setGoogleMapDirectionUrl($addressData['googleMapDirectionUrl']);
            $address->setZones($zones);
            $information->addAddress($address);
        }
    }

    /**
     * Add Legacy.
     */
    private function addLegacy(InfoEntities\Information $information, string $locale, string $defaultLocale): void
    {
        $legalsData = !empty($this->yamlConfiguration['legals'][$locale])
            ? $this->yamlConfiguration['legals'][$locale] : (!empty($this->yamlConfiguration['legals'][$defaultLocale])
                ? $this->yamlConfiguration['legals'][$defaultLocale] : []);
        $legal = new InfoEntities\Legal();
        $legal->setLocale($locale);
        foreach ($legalsData as $name => $value) {
            $setter = 'set'.ucfirst($name);
            $legal->$setter($value);
        }
        $information->addLegal($legal);
    }

    /**
     * Add intl.
     */
    private function addIntl(InfoEntities\Information $information, string $locale): void
    {
        $companyName = !empty($this->yamlConfiguration['company_name']) ? $this->yamlConfiguration['company_name'] : null;
        $intl = new InfoEntities\InformationIntl();
        $intl->setLocale($locale);
        $intl->setTitle($companyName);
        $intl->setInformation($information);
        $intl->setWebsite($information->getWebsite());
        $information->addIntl($intl);
    }
}
