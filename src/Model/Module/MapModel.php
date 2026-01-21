<?php

declare(strict_types=1);

namespace App\Model\Module;

use App\Entity\Module\Map\Map;
use App\Entity\Module\Map\Point;
use App\Model\BaseModel;
use App\Model\Core\WebsiteModel;
use App\Model\ViewModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use League\ISO3166\ISO3166;
use Symfony\Component\Filesystem\Filesystem;

/**
 * MapModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MapModel extends BaseModel
{
    /**
     * fromEntity.
     *
     * @throws MappingException|NonUniqueResultException|QueryException
     */
    public static function fromEntity(Map $map, CoreLocatorInterface $coreLocator, array $options = []): object
    {
        $model = ViewModel::fromEntity($map, $coreLocator, array_merge($options));
        $website = self::$coreLocator->website();
        self::geoJsonFile($map);

        return (object) array_merge((array) $model, [
            'points' => self::getPoints($map, $website),
        ]);
    }

    /**
     * Get Points.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    private static function getPoints(Map $map, WebsiteModel $website): array
    {
        $points = [];
        foreach ($map->getPoints() as $key => $point) {
            /** @var Point $point */
            $address = $point->getAddress();
            if ($address && $address->getLatitude() && str_contains($address->getLatitude(), ',')) {
                $address->setLatitude(str_replace(',', '.', $address->getLatitude()));
            }
            if ($address && $address->getLongitude() && str_contains($address->getLongitude(), ',')) {
                $address->setLongitude(str_replace(',', '.', $address->getLongitude()));
            }
            $points[$key] = (array) ViewModel::fromEntity($point, self::$coreLocator);
            $iso3166 = new ISO3166();
            $countries = $point->getCountries() && is_array($point->getCountries()) ? $point->getCountries() : [];
            $departments = $point->getDepartments() && is_array($point->getDepartments()) ? $point->getDepartments() : [];
            $countriesData = [];
            foreach ($countries as $country) {
                $countriesData[] = $iso3166->alpha2($country)['alpha3'];
            }
            $points[$key]['zones'] = array_merge($countriesData, $departments);
            $geoJson = $point->getGeoJson() && $point->getGeoJson()->getMedia() ? $point->getGeoJson()->getMedia() : null;
            $points[$key]['geoJson'] = $geoJson && $geoJson->getFilename() ? '/uploads/'.$website->uploadDirname.'/'.$point->getGeoJson()->getMedia()->getFilename() : false;
        }

        return $points;
    }

    /**
     * Check geoJson file.
     */
    private static function geoJsonFile(Map $map): void
    {
        if ($map->isCountriesGeometry() || $map->isDepartmentsGeometry()) {
            $filesystem = new Filesystem();
            $publicDir = self::$coreLocator->publicDir().DIRECTORY_SEPARATOR.'geo-json'.DIRECTORY_SEPARATOR.'geo-lite.json';
            if (!$filesystem->exists($publicDir)) {
                $zipPath = self::$coreLocator->projectDir().DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'geo-lite.zip';
                if ($filesystem->exists($zipPath)) {
                    $zip = new \ZipArchive();
                    if ($zip->open($zipPath) === true) {
                        $dirname = self::$coreLocator->publicDir().DIRECTORY_SEPARATOR.'geo-json';
                        if (!$filesystem->exists($dirname)) {
                            $filesystem->mkdir($dirname);
                        }
                        $zip->extractTo($dirname);
                        $zip->close();
                    }
                }
            }
        }
    }
}