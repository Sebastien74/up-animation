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
    public const string TOKEN_CITIES = '%location_cities%';

    /** @var array<string, array{dimension: string, city: ?string, department: ?string, region: ?string, cities: list<string>}>|null */
    private ?array $locationMap = null;
    /** @var array<string, string> */
    private array $valueCache = [];
    /** @var array<string, list<string>> */
    private array $citiesCache = [];

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
        if (null === $text || '' === $text) {
            return $text;
        }

        $hasToken = str_contains($text, self::TOKEN);
        $hasCities = str_contains($text, self::TOKEN_CITIES);
        if (!$hasToken && !$hasCities) {
            return $text;
        }

        // %location_cities% : clause de couverture (communes desservies) — d'abord (ne contient pas %location%).
        if ($hasCities) {
            $text = str_replace(self::TOKEN_CITIES, $this->coverageClause(), $text);
        }
        if (!str_contains($text, self::TOKEN)) {
            return $text;
        }

        $value = $this->currentValue();

        // H1 : localisation mise en évidence (span text-primary), sans toucher le <title>/metas.
        $text = preg_replace_callback('/<h1\b[^>]*>.*?<\/h1>/su', fn (array $m): string => $this->substitute($m[0], $value, true), $text) ?? $text;

        // <title>, metas et contenu : substitution simple.
        return $this->substitute($text, $value, false);
    }

    /**
     * Clause de couverture pour la variante courante : liste des communes desservies de la zone
     * (dépt/ville => communes de l'agence ; région/pays => villes-agences). Vide sur la base.
     */
    private function coverageClause(): string
    {
        $cities = $this->currentCities();
        if ([] === $cities) {
            return '';
        }

        // Échapper les communes (issues de données éditables) avant assemblage HTML.
        $safe = array_map(static fn (string $c): string => htmlspecialchars($c, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $cities);

        return '<p class="location-coverage">'.htmlspecialchars($this->coverageLabel(), ENT_QUOTES | ENT_HTML5, 'UTF-8').' '.implode(', ', $safe).'.</p>';
    }

    /**
     * Libellé d'intro de la clause de couverture (surchargé par site si besoin).
     */
    private function coverageLabel(): string
    {
        return 'Nous intervenons à';
    }

    /**
     * Communes desservies pour la localisation courante ([] si base / non résolu).
     *
     * @return list<string>
     */
    public function currentCities(): array
    {
        $slug = $this->coreLocator->request()?->attributes->get('location');
        if (!is_string($slug) || '' === $slug) {
            return [];
        }
        if (array_key_exists($slug, $this->citiesCache)) {
            return $this->citiesCache[$slug];
        }

        return $this->citiesCache[$slug] = $this->locationMap()[$slug]['cities'] ?? [];
    }

    /**
     * Substitue le token %location% dans un fragment. Capitalise la 1ʳᵉ lettre de la localisation
     * quand elle suit une ponctuation forte (. ! ?), et l'enveloppe d'un span text-primary si $highlight.
     */
    private function substitute(string $text, string $value, bool $highlight): string
    {
        if ('' === $value) {
            // Variante base : retirer le token et l'espace qui le précède éventuellement.
            return preg_replace('/\s*'.preg_quote(self::TOKEN, '/').'/u', '', $text) ?? $text;
        }

        $token = preg_quote(self::TOKEN, '/');
        $result = preg_replace_callback('/([.!?])(\s*)'.$token.'|'.$token.'/u', function (array $m) use ($value, $highlight): string {
            $strong = !empty($m[1]);
            $label = $strong ? $this->ucFirst($value) : $value;
            if ($highlight) {
                $label = '<span class="text-primary">'.$label.'</span>';
            }

            return $strong ? $m[1].$m[2].$label : $label;
        }, $text);

        return $result ?? $text;
    }

    /**
     * Passe la première lettre (multi-octets) en majuscule sans modifier le reste.
     */
    private function ucFirst(string $value): string
    {
        if ('' === $value) {
            return $value;
        }

        return mb_strtoupper(mb_substr($value, 0, 1)).mb_substr($value, 1);
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
                if (!$slug) {
                    continue;
                }
                if (!isset($map[$slug])) {
                    $map[$slug] = [
                        'dimension' => $dimension,
                        'city' => 'city' === $dimension ? $agency['city'] : null,
                        'department' => in_array($dimension, ['city', 'department'], true) ? $agency['department'] : null,
                        'region' => $agency['region'],
                        'country' => in_array($dimension, ['city', 'country'], true) ? $agency['country'] : null,
                        'cities' => [],
                    ];
                }
                // Communes desservies : ville/dépt => communes de l'agence ; région/pays => villes-agences (union).
                $add = in_array($dimension, ['city', 'department'], true)
                    ? ($agency['cities'] ?? [])
                    : array_values(array_filter([$agency['city']]));
                $map[$slug]['cities'] = array_values(array_unique(array_merge($map[$slug]['cities'], $add)));
            }
        }

        return $this->locationMap = $map;
    }

    /**
     * Villes / départements / régions / communes desservies de toutes les agences (catalog 'agencies').
     *
     * @return list<array{country: ?string, city: ?string, department: ?string, region: ?string, cities: list<string>}>
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
            $json = $agencyDb->getJsonValues() ?? [];
            $cities = !empty($json['servedCities']) && is_array($json['servedCities'])
                ? array_values(array_filter(array_map(static fn ($c): string => trim((string) $c), $json['servedCities'])))
                : [];
            $locations[] = [
                'country' => ($countryCode && 'FR' !== $countryCode) ? Countries::getName($countryCode) : null,
                'city' => $model->city ?: null,
                'department' => $model->department ?: null,
                'region' => $model->region ?: null,
                'cities' => $cities,
            ];
        }

        return $locations;
    }
}
