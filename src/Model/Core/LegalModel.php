<?php

declare(strict_types=1);

namespace App\Model\Core;

use App\Entity\Information\Information;
use App\Model\BaseModel;
use App\Model\IntlModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;

/**
 * LegalModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LegalModel extends BaseModel
{
    private static array $cache = [];

    /**
     * LegalModel constructor.
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $companyName = null,
        public readonly ?string $companyRepresentativeName = null,
        public readonly ?string $capital = null,
        public readonly ?string $vatNumber = null,
        public readonly ?string $siretNumber = null,
        public readonly ?string $commercialRegisterNumber = null,
        public readonly ?string $companyAddress = null,
        public readonly ?string $managerName = null,
        public readonly ?string $managerEmail = null,
        public readonly ?string $webmasterName = null,
        public readonly ?string $webmasterEmail = null,
        public readonly ?string $hostName = null,
        public readonly ?string $hostAddress = null,
        public readonly ?string $protectionOfficerName = null,
        public readonly ?string $protectionOfficerEmail = null,
        public readonly ?string $protectionOfficerAddress = null,
    ) {
    }

    /**
     * Get model.
     *
     * @throws MappingException
     */
    public static function fromEntity(Information $information, CoreLocatorInterface $coreLocator, ?string $locale = null): self
    {
        self::setLocator($coreLocator);

        $website = $information->getWebsite();
        $locale = $locale ?: $coreLocator->locale();

        if (isset(self::$cache['legals'][$website->getId()][$locale])) {
            return self::$cache['legals'][$website->getId()][$locale];
        }

        $intls = IntlModel::intls($information, 'legals', false);
        $legal = !empty($intls[0]) ? $intls[0] : null;

        self::$cache['legals'][$website->getId()][$locale] = new self(
            id: self::getContent('id', $legal),
            companyName: self::getContent('companyName', $legal),
            companyRepresentativeName: self::getContent('companyRepresentativeName', $legal),
            capital: self::getContent('capital', $legal),
            vatNumber: self::getContent('vatNumber', $legal),
            siretNumber: self::getContent('siretNumber', $legal),
            commercialRegisterNumber: self::getContent('commercialRegisterNumber', $legal),
            companyAddress: self::getContent('companyAddress', $legal),
            managerName: self::getContent('managerName', $legal),
            managerEmail: self::getContent('managerEmail', $legal),
            webmasterName: self::getContent('webmasterName', $legal),
            webmasterEmail: self::getContent('webmasterEmail', $legal),
            hostName: self::getContent('hostName', $legal),
            hostAddress: self::getContent('hostAddress', $legal),
            protectionOfficerName: self::getContent('protectionOfficerName', $legal),
            protectionOfficerEmail: self::getContent('protectionOfficerEmail', $legal),
            protectionOfficerAddress: self::getContent('protectionOfficerAddress', $legal),
        );

        return self::$cache['legals'][$website->getId()][$locale];
    }

    /**
     * Get model by cache.
     */
    public static function modelCache(object $data): LegalModel
    {
        return new self(
            id: $data->id,
            companyName: $data->companyName,
            companyRepresentativeName: $data->companyRepresentativeName,
            capital: $data->capital,
            vatNumber: $data->vatNumber,
            siretNumber: $data->siretNumber,
            commercialRegisterNumber: $data->commercialRegisterNumber,
            companyAddress: $data->companyAddress,
            managerName: $data->managerName,
            managerEmail: $data->managerEmail,
            webmasterName: $data->webmasterName,
            webmasterEmail: $data->webmasterEmail,
            hostName: $data->hostName,
            hostAddress: $data->hostAddress,
            protectionOfficerName: $data->protectionOfficerName,
            protectionOfficerEmail: $data->protectionOfficerEmail,
            protectionOfficerAddress: $data->protectionOfficerAddress,
        );
    }
}