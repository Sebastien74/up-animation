<?php

declare(strict_types=1);

namespace App\Model\Core;

use App\Entity\Core\Website;
use App\Entity\Information\Address;
use App\Entity\Information\Information;
use App\Entity\Information\Legal;
use App\Entity\Information\Phone;
use App\Model\BaseModel;
use App\Model\IntlModel;
use App\Model\MediaModel;
use App\Model\MediasModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * InformationModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class InformationModel extends BaseModel
{
    private static array $cache = [];

    /**
     * InformationModel constructor.
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?object $entity = null,
        public readonly ?string $companyName = null,
        public readonly ?array $medias = null,
        public readonly ?array $logos = null,
        public readonly ?IntlModel $intl = null,
        public readonly ?array $networks = null,
        public readonly ?array $addresses = null,
        public readonly ?Address $address = null,
        public readonly ?string $schedule = null,
        public readonly ?array $phones = null,
        public readonly ?Phone $phone = null,
        public readonly ?array $emails = null,
        public readonly ?string $email = null,
        public readonly ?array $zones = null,
        public readonly ?string $emailFrom = null,
        public readonly ?string $emailNoReply = null,
        public readonly ?Legal $legals = null,
        public readonly ?array $scheduleDays = null,
        public readonly ?array $alerts = null,
        public readonly ?string $alertType = null,
        public readonly ?int $alertDuration = null,
        public readonly ?string $footerDescription = null,
    ) {
    }

    /**
     * Get model.
     *
     * @throws NonUniqueResultException|MappingException
     */
    public static function fromEntity(Website $website, CoreLocatorInterface $coreLocator, ?string $locale = null): self
    {
        self::setLocator($coreLocator);

        $locale = $locale ?: self::$coreLocator->locale();

        if (isset(self::$cache['response'][$website->getId()][$locale])) {
            return self::$cache['response'][$website->getId()][$locale];
        }

        $information = self::information($website);
        $medias = MediasModel::fromEntity($website->getConfiguration(), $coreLocator, $locale)->list;
        $intl = IntlModel::fromEntity($information, $coreLocator, false);
        $logos = self::logos($website, $medias, $locale);
        $addresses = self::addresses($information);
        $emails = self::emails($information);
        $phones = self::phones($information);
        $legals = IntlModel::intls($information, 'legals', false);

        self::$cache['response'][$website->getId()][$locale] = new self(
            id: self::getContent('id', $information),
            entity: $information,
            companyName: $intl->title,
            logos: $logos,
            intl: $intl,
            networks: !empty($logos['social-networks']) ? $logos['social-networks'] : [],
            addresses: $addresses->all,
            address: $addresses->main,
            schedule: $intl->body,
            phones: $phones->all,
            phone: $phones->main,
            emails: $emails->forFront,
            email: $emails->main,
            zones: self::contactZones($addresses->all, $phones->all, $emails->all),
            emailFrom: $emails->from,
            emailNoReply: $emails->noReply,
            legals: !empty($legals[0]) ? $legals[0] : null,
            scheduleDays: IntlModel::intls($information, 'scheduleDays', false),
            alerts: self::alerts($intl),
            alertType: self::getContent('alertType', $intl->intl),
            alertDuration: self::getContent('alertDuration', $intl->intl),
            footerDescription: $intl->body,
        );

        return self::$cache['response'][$website->getId()][$locale];
    }

    /**
     * To get media relation by locale.
     *
     * @throws NonUniqueResultException
     */
    private static function information(Website $website): ?object
    {
        return self::$coreLocator->em()->getRepository(Information::class)
            ->createQueryBuilder('i')
            ->innerJoin('i.website', 'w')
            ->leftJoin('i.phones', 'p')
            ->leftJoin('i.emails', 'e')
            ->leftJoin('i.addresses', 'a')
            ->leftJoin('i.legals', 'l')
            ->leftJoin('i.scheduleDays', 's')
            ->leftJoin('i.socialNetworks', 'sn')
            ->leftJoin('i.intls', 'intl')
            ->andWhere('w.id =  :website')
            ->setParameter('website', $website)
            ->addSelect('w')
            ->addSelect('p')
            ->addSelect('e')
            ->addSelect('a')
            ->addSelect('l')
            ->addSelect('s')
            ->addSelect('sn')
            ->addSelect('intl')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * To get logos.
     */
    private static function logos(Website $website, array $medias, string $locale): array
    {
        if (!empty(self::$cache['logos'][$website->getId()])) {
            return self::$cache['logos'][$website->getId()];
        }

        $logos = [];
        $socialLogos = [];
        $filesystem = new Filesystem();
        $uploadDirname = $website->getUploadDirname();
        $projectDir = self::$coreLocator->projectDir();
        $socialNetworksCategories = ['linkedin', 'youtube', 'instagram', 'facebook', 'twitter', 'tiktok', 'pinterest', 'tripadvisor', 'google'];
        $socialNetworks = (self::$coreLocator->request() && !preg_match('/\/admin-'.$_ENV['SECURITY_TOKEN'].'/', self::$coreLocator->request()->getUri()))
        || (self::$coreLocator->request() && preg_match('/\/preview\//', self::$coreLocator->request()->getUri()))
            ? self::socialNetworks($website, $locale) : [];

        foreach ($medias as $media) {
            /** @var MediaModel $media */
            $entityMedia = $media->media;
            $filename = $entityMedia->getFilename();
            $dirname = $filename ? '/uploads/'.$uploadDirname.'/'.$filename : null;
            $appDirname = $projectDir.'/public'.$dirname;
            $appDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $appDirname);
            $file = $filename && $filesystem->exists($appDirname) ? $dirname : 'medias/placeholder.jpg';
            $category = $media->mediaRelation->getCategorySlug();
            $mediaCategory = $category ?: $entityMedia->getCategory();
            $logos[$category] = $file;
            $logos['medias'][$category] = $media;
            $logos['mediaRelation'][$category] = $media->mediaRelation;
            if ('social-network' === $mediaCategory && !empty($socialNetworks[$category])
                || in_array($mediaCategory, $socialNetworksCategories) && !empty($socialNetworks[$category])) {
                $arguments = [
                    'title' => ucfirst($category),
                    'link' => $socialNetworks[$category],
                    'dirname' => $file,
                    'icon' => self::socialIcon($category),
                ];
                $socialLogos[$category] = $arguments;
                $logos['social-networks'] = $socialLogos;
                $logos[$category] = $arguments;
            } elseif ('social-network' !== $category) {
                $logos[$category] = $file;
                $logos['medias'][$category] = $media;
            }
        }

        ksort($logos);

        if (!empty($logos['social-networks'])) {
            $networks = [];
            foreach ($socialNetworksCategories as $network) {
                if (!empty($logos['social-networks'][$network])) {
                    $networks[$network] = $logos['social-networks'][$network];
                }
            }
            foreach ($logos['social-networks'] as $network => $link) {
                if (!isset($networks[$network])) {
                    $networks[$network] = $link;
                }
            }
            $logos['social-networks'] = $networks;
        }

        self::$cache['logos'][$website->getId()] = $logos;

        return $logos;
    }

    /**
     * Get Website social networks.
     */
    private static function socialNetworks(Website $website, string $locale): array
    {
        $result = [];

        foreach ($website->getInformation()->getSocialNetworks() as $socialNetwork) {
            if ($locale === $socialNetwork->getLocale()) {
                $result = self::metadata($socialNetwork);
            }
        }

        return $result;
    }

    /**
     * Get social network icon by name.
     */
    private static function socialIcon(string $name): ?string
    {
        $icons = [
            'facebook' => 'fab facebook-f',
            'google' => 'fab google',
            'instagram' => 'fab instagram',
            'linkedin' => 'fab linkedin-in',
            'pinterest' => 'fab pinterest-p',
            'tripadvisor' => 'fab tripadvisor',
            'twitter' => 'fab twitter',
            'youtube' => 'fab youtube',
            'tiktok' => 'fab tiktok',
        ];

        return !empty($icons[$name]) ? $icons[$name] : null;
    }

    /**
     * Get intl Email[].
     *
     * @throws NonUniqueResultException
     */
    private static function addresses(Information $information): object
    {
        $main = null;
        $addresses = IntlModel::intls($information, 'addresses', false);
        foreach ($addresses as $address) {
            if (!$main) {
                $main = $address;
            }
        }

        return (object) [
            'all' => $addresses,
            'main' => $main,
        ];
    }

    /**
     * Get intl Email[].
     *
     * @throws NonUniqueResultException
     */
    private static function emails(Information $information): object
    {
        $main = null;
        $emails = IntlModel::intls($information, 'emails', false);
        $from = 'dev@agence-felix.fr';
        $noReply = 'noreply@agence-felix.fr';
        $forFront = [];

        foreach ($emails as $email) {
            if ('support' === $email->getSlug()) {
                $from = $email->getEmail();
            } elseif ('no-reply' === $email->getSlug()) {
                $noReply = $email->getEmail();
            } else {
                $forFront[] = $email;
            }
            if (!$main && in_array('contact', $email->getZones())) {
                $main = $email->getEmail();
            }
        }

        return (object) [
            'all' => $emails,
            'main' => $main,
            'from' => $from,
            'noReply' => $noReply,
            'forFront' => $forFront,
        ];
    }

    /**
     * Get intl Phone[].
     *
     * @throws NonUniqueResultException
     */
    private static function phones(Information $information): object
    {
        $dbPhones = IntlModel::intls($information, 'phones', false);
        $main = null;

        foreach ($dbPhones as $phone) {
            if (!$main && 'office' === $phone->getType() || !$main && 'fixe' === $phone->getType()) {
                $main = $phone;
            }
        }

        return (object) [
            'all' => $dbPhones,
            'main' => $main,
        ];
    }

    /**
     * Get contact info sort by zones.
     */
    private static function contactZones(array $addresses, array $phones, array $emails): array
    {
        $contacts = [];

        foreach ($addresses as $address) {
            foreach ($address->getZones() as $zone) {
                $contacts['addresses'][$zone][] = $address;
                $contacts['addresses']['all'][] = $address;
            }
        }

        foreach ($phones as $phone) {
            foreach ($phone->getZones() as $zone) {
                $contacts['phones'][$zone][] = $phone;
                $contacts['phones']['all'][] = $phone;
            }
        }

        foreach ($emails as $email) {
            foreach ($email->getZones() as $zone) {
                $contacts['emails'][$zone][] = $email;
                $contacts['emails']['all'][] = $email;
            }
        }

        return $contacts;
    }

    /**
     * Get alertes.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private static function alerts(IntlModel $intl): array
    {
        $hideAlerts = self::$coreLocator->request() && true === self::$coreLocator->request()->getSession()->get('front_website_alert_hide');
        $message = !$hideAlerts ? self::getContent('placeholder', $intl, true) : [];
        $alerts = [];

        if (!$message) {
            return $alerts;
        }

        preg_match_all('/<li[^>]*>(.*?)<\\/li>/is', $message, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $text) {
                if ($text && strlen(strip_tags(str_replace('&nbsp;', '', $text))) > 0) {
                    $alerts[] = $text;
                }
            }
        }

        return $alerts;
    }
}
