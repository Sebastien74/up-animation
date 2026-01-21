<?php

declare(strict_types=1);

namespace App\Model\Core;

use App\Entity\Core\Configuration;
use App\Entity\Core\Configuration as ConfigurationEntity;
use App\Model\BaseModel;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * DomainModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class DomainModel extends BaseModel
{
    /**
     * DomainModel constructor.
     */
    public function __construct(
        public readonly ?array $list = null,
        public readonly ?object $default = null,
        public readonly ?string $locale = null,
    ) {
    }

    /**
     * Get model.
     */
    public static function fromEntity(Configuration $configuration, CoreLocatorInterface $coreLocator, ?string $locale = null): self
    {
        self::setLocator($coreLocator);

        $locale = $locale ?: self::$coreLocator->locale();
        $domains = self::domains($configuration, $locale);

        return new self(
            list: $domains->list,
            default: $domains->default,
            locale: $domains->locale,
        );
    }

    /**
     * To get domains.
     */
    private static function domains(ConfigurationEntity $configuration, string $locale): object
    {
        $filesystem = new Filesystem();
        $dirname = self::$coreLocator->cacheDir().'/domains.cache.json';

        if (!$filesystem->exists($dirname)) {
            $domains = self::$coreLocator->em()->getRepository(\App\Entity\Core\Domain::class)
                ->createQueryBuilder('d')
                ->andWhere('d.configuration = :configuration')
                ->setParameter('configuration', $configuration)
                ->getQuery()
                ->getResult();
            $cacheData = [];
            foreach ($domains as $domain) {
                $cacheData[$configuration->getId()][$domain->getLocale()][] = [
                    'name' => $domain->getName(),
                    'locale' => $domain->getLocale(),
                    'asDefault' => $domain->isAsDefault(),
                ];
            }
            $fp = fopen($dirname, 'w');
            fwrite($fp, json_encode($cacheData, JSON_PRETTY_PRINT));
            fclose($fp);
        }

        $jsonDomains = (array) json_decode(file_get_contents($dirname));
        $configurationDomains = isset($jsonDomains[$configuration->getId()]) ? (array) $jsonDomains[$configuration->getId()] : [];
        $baseUrl = self::$coreLocator->request() ? str_replace([self::$coreLocator->request()->getScheme(), '://'], [''], self::$coreLocator->request()->getSchemeAndHttpHost()) : null;
        foreach ($configurationDomains as $domains) {
            foreach ($domains as $domain) {
                if ($baseUrl === $domain->name) {
                    $locale = $domain->locale;
                    break;
                }
            }
        }
        $localeDomains = $configurationDomains[$locale] ?? [];

        $default = null;
        foreach ($localeDomains as $domain) {
            if ($domain->asDefault) {
                $default = $domain;
                break;
            }
        }

        return new self(
            list: $localeDomains,
            default: $default,
            locale: $locale,
        );
    }
}
