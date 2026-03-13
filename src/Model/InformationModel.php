<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Information\Address;
use App\Service\Interface\CoreLocatorInterface;

/**
 * InformationModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class InformationModel extends BaseModel
{
    /**
     * InformationModel constructor.
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?object $entity = null,
        public readonly ?array $address = null,
        public readonly ?array $addresses = null,
    ) {
    }

    /**
     * Get model.
     */
    public static function fromEntity(mixed $information, CoreLocatorInterface $coreLocator, ?string $locale = null): self
    {
        $entityId = method_exists($information, 'getId') ? $information->getId() : $information->id;
        $addressesDb = method_exists($information, 'getAddresses')
            ? $information->getAddresses()
            : (method_exists($information, 'getAddress') ? [$information->getAddress()] : []);
        $addresses = [];
        foreach ($addressesDb as $address) {
            $addresses[] = is_array($address) ? $address : self::getAddress($address);
        }

        return new self(
            id: $entityId,
            entity: $information,
            address: $addresses[0] ?? null,
            addresses: $addresses,
        );
    }

    private static function getAddress(?Address $address = null): array
    {
        return [
            'name' => $address?->getName(),
            'address' => $address?->getAddress(),
            'zipCode' => $address?->getZipCode(),
            'city' => $address?->getCity(),
            'department' => $address?->getDepartment(),
            'region' => $address?->getRegion(),
            'country' => $address?->getCountry(),
            'latitude' => $address?->getLatitude(),
            'longitude' => $address?->getLongitude(),
            'googleMapUrl' => $address?->getGoogleMapUrl(),
            'googleMapDirectionUrl' => $address?->getGoogleMapDirectionUrl(),
            'phones' => $address?->getPhones()->toArray(),
            'emails' => $address?->getEmails()->toArray(),
        ];
    }

    /**
     * Get model by cache.
     */
    protected static function modelCache(mixed $data): InformationModel
    {
        return new self(
            id: $data ? $data->id : null,
            address: $data ? (array)$data->address : null,
            addresses: $data ? (array)$data->addresses : null,
        );
    }
}