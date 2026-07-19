<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Entity\Module\Catalog\Product;
use App\Model\Module\ProductModel;
use App\Service\Core\Urlizer;
use App\Service\Interface\CoreLocatorInterface;
use App\Twig\Content\LocationPrepositionRuntime;
use Symfony\Component\Intl\Countries;

/**
 * LocationTokenService.
 *
 * Résout le contexte de localisation d'une fiche produit à partir du seul segment
 * d'URL {location} (route front_catalogproduct_view) et substitue le token %location%
 * du contenu par la localisation active, déclinée pour le SEO :
 *  - ville       => "à {ville} ({département})"
 *  - département => "en {département}"
 *  - région      => "en {région}"
 *  - base        => token retiré (aucune localisation).
 *
 * Le slug {location} est résolu par correspondance avec les villes / départements /
 * régions des agences (catalog 'agencies') — sans paramètre d'agence dans l'URL,
 * pour éviter le duplicate content (dept/région partagés par plusieurs agences).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class LocationTokenService
{
    public const string TOKEN = '%location%';

    /** @var array<string, array{dimension: string, city: ?string, department: ?string, region: ?string}>|null */
    private ?array $locationMap = null;
    /** @var array<string, string> */
    private array $valueCache = [];

    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly LocationPrepositionRuntime $locationPreposition,
    ) {
    }

    /**
     * Remplace le token %location% dans un texte selon le contexte de la requête courante.
     */
    public function apply(?string $text): ?string
    {
        if (null === $text || '' === $text || !str_contains($text, self::TOKEN)) {
            return $text;
        }

        $value = $this->currentValue();

        if ('' === $value) {
            // Variante base : retirer le token et l'espace qui le précède éventuellement.
            return preg_replace('/\s*'.preg_quote(self::TOKEN, '/').'/u', '', $text);
        }

        return str_replace(self::TOKEN, $value, $text);
    }

    /**
     * Libellé de localisation pour la requête courante ('' si base / non résolu).
     */
    public function currentValue(): string
    {
        $locationSlug = $this->coreLocator->request()?->attributes->get('location');
        if (!is_string($locationSlug) || '' === $locationSlug) {
            return '';
        }
        if (array_key_exists($locationSlug, $this->valueCache)) {
            return $this->valueCache[$locationSlug];
        }

        $context = $this->locationMap()[$locationSlug] ?? null;
        $value = $context
            ? $this->locationPreposition->buildLocationLabel($context['city'], $context['department'], $context['region'], $context['country'], $context['dimension'])
            : '';

        return $this->valueCache[$locationSlug] = $value;
    }

    /**
     * Table slug => contexte de localisation, construite depuis les agences (mémoïsée).
     * Priorité ville > département > région en cas de collision de slug.
     *
     * @return array<string, array{dimension: string, city: ?string, department: ?string, region: ?string}>
     */
    private function locationMap(): array
    {
        if (null !== $this->locationMap) {
            return $this->locationMap;
        }

        $agencies = $this->agencyLocations();
        $map = [];
        foreach (['city', 'department', 'region', 'country'] as $dimension) {
            foreach ($agencies as $agency) {
                $value = $agency[$dimension] ?? null;
                if (!$value) {
                    continue;
                }
                $slug = Urlizer::urlize($value);
                if (!$slug || isset($map[$slug])) {
                    continue;
                }
                $map[$slug] = [
                    'dimension' => $dimension,
                    'city' => 'city' === $dimension ? $agency['city'] : null,
                    'department' => in_array($dimension, ['city', 'department'], true) ? $agency['department'] : null,
                    'region' => $agency['region'],
                    'country' => in_array($dimension, ['city', 'country'], true) ? $agency['country'] : null,
                ];
            }
        }

        return $this->locationMap = $map;
    }

    /**
     * Villes / départements / régions de toutes les agences (catalog 'agencies') du website courant.
     *
     * @return list<array{city: ?string, department: ?string, region: ?string}>
     */
    private function agencyLocations(): array
    {
        $website = $this->coreLocator->website();
        $agencies = $this->coreLocator->em()->getRepository(Product::class)->findBy(['website' => $website->entity]);

        $locations = [];
        foreach ($agencies as $agencyDb) {
            if ('agencies' !== $agencyDb->getCatalog()?->getSlug()) {
                continue;
            }
            $model = ProductModel::fromEntity($agencyDb, $this->coreLocator, ['onlyForUrl' => true]);
            $countryCode = !empty($model->country) ? strtoupper((string) $model->country) : null;
            $locations[] = [
                'country' => ($countryCode && 'FR' !== $countryCode) ? Countries::getName($countryCode) : null,
                'city' => $model->city ?: null,
                'department' => $model->department ?: null,
                'region' => $model->region ?: null,
            ];
        }

        return $locations;
    }
}
