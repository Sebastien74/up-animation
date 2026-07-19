<?php

declare(strict_types=1);

namespace App\Twig\Content;

use Twig\Extension\RuntimeExtensionInterface;

/**
 * LocationPrepositionRuntime.
 *
 * Provides helpers for French department:
 * - preposition (default): "dans le", "dans la", "dans l'", "dans les", "en", "à"
 * - extended prefixes: "dans tout le", "dans toute la", "dans tout l'", "dans toutes les"
 * - codes and names from name, code, or postal code
 */
final class LocationPrepositionRuntime implements RuntimeExtensionInterface
{
    public const string PREP_DEFAULT = 'default';      // 기존: dans le/la/l'/les OR en OR à
    public const string PREP_ALL_IN = 'all_in';       // "dans tout(e)(s) + article" (handles le/la/l'/les)
    public const string PREP_ALL = 'all';          // "tout(e)(s) + article" (no leading "dans")
    public const string PREP_IN = 'in';           // "dans le/la/l'/les" (force "dans", even if dept is "en" or "à")

    /**
     * Return the French preposition/prefix to use before a French department name/code/postal code.
     *
     * Types:
     * - default (default): returns "dans le/la/l'/les" OR "en" OR "à"
     * - all_in: returns "dans tout le", "dans toute la", "dans tout l'", "dans toutes les"
     * - all: returns "tout le", "toute la", "tout l'", "toutes les"
     * - in: returns "dans le/la/l'/les" (forced), even if dept is usually "en" or "à"
     *
     * @param string|int $department Department name (e.g. "Rhône"), code (e.g. "69", "2A") or postal code (e.g. "74330")
     * @param string     $type One of self::PREP_*
     */
    public function getDepartmentPreposition(string|int $department, string $type = self::PREP_DEFAULT): string
    {
        $resolved = $this->resolveDepartment((string) $department);

        if (!isset($resolved['code'])) {
            // Safe fallback
            return ($type === self::PREP_ALL_IN) ? 'dans tout le ' : (($type === self::PREP_ALL) ? 'tout le '  : 'dans le ');
        }

        $dept = $this->getDepartments();
        $row  = $dept[$resolved['code']] ?? null;

        if (!is_array($row)) {
            return ($type === self::PREP_ALL_IN) ? 'dans tout le ' : (($type === self::PREP_ALL) ? 'tout le ' : 'dans le ');
        }

        // Default: use stored canonical preposition ("dans …" OR "en" OR "à")
        if ($type === self::PREP_DEFAULT) {
            return $row['prep'];
        }

        // Force "dans le/la/l'/les" from article info
        if ($type === self::PREP_IN) {
            $article = $this->buildDansArticle($row['article']);
            return !str_contains($article, "'") ? ' '.$article : $article;
        }

        // "tout(e)(s) + article" (with or without "dans")
        if ($type === self::PREP_ALL_IN || $type === self::PREP_ALL) {
            $prefix = $this->buildToutArticle($row['article']);
            $prefix = !str_contains($prefix, "'") ? ' '.$prefix : $prefix;
            return ($type === self::PREP_ALL_IN) ? ('dans ' . $prefix) : $prefix;
        }

        // Unknown type => default
        return $row['prep'];
    }

    /**
     * Return the French preposition/prefix to use before a French region name.
     *
     * Regions are irregular: "en Bretagne", "dans les Hauts-de-France", "dans le Grand Est",
     * "à La Réunion". Lookup is name-based (no code system, unlike departments).
     *
     * @param string $region Region name (e.g. "Bretagne", "Hauts-de-France")
     * @param string $type One of self::PREP_*
     */
    public function getRegionPreposition(string $region, string $type = self::PREP_DEFAULT): string
    {
        $row = $this->getRegions()[$this->normalize($region)] ?? null;

        if (!is_array($row)) {
            // "en" covers most French regions
            return ($type === self::PREP_ALL_IN) ? 'dans toute la ' : (($type === self::PREP_ALL) ? 'toute la ' : 'en ');
        }

        if ($type === self::PREP_DEFAULT) {
            return $row['prep'];
        }

        if ($type === self::PREP_IN) {
            $article = $this->buildDansArticle($row['article']);
            return !str_contains($article, "'") ? ' '.$article : $article;
        }

        if ($type === self::PREP_ALL_IN || $type === self::PREP_ALL) {
            $prefix = $this->buildToutArticle($row['article']);
            $prefix = !str_contains($prefix, "'") ? ' '.$prefix : $prefix;
            return ($type === self::PREP_ALL_IN) ? ('dans ' . $prefix) : $prefix;
        }

        return $row['prep'];
    }

    /**
     * Return the department code formatted as "(01)" by default.
     *
     * @param string|int $department Department name, department code or postal code.
     * @param bool       $withParentheses Whether to return the code wrapped in parentheses.
     */
    public function getDepartmentCode(string|int $department, bool $withParentheses = true): string
    {
        $resolved = $this->resolveDepartment((string) $department);

        if (!isset($resolved['code']) || $resolved['code'] === '') {
            return '';
        }

        return $withParentheses ? sprintf('(%s)', $resolved['code']) : $resolved['code'];
    }

    /**
     * Return the department name from a department code, postal code, or department name (normalized lookup).
     *
     * @param string|int $value Department name (e.g. "Rhône"), code (e.g. "69") or postal code (e.g. "74330")
     */
    public function getDepartmentName(string|int $value): string
    {
        $resolved = $this->resolveDepartment((string) $value);

        return $resolved['name'] ?? '';
    }

    /**
     * Return "d'" when the place name starts with a vowel (a,e,i,o,u,y),
     * otherwise return "de".
     *
     * @param string|null $place Place name (city, location, etc.)
     * @return string
     */
    public function getDeCityPrefix(?string $place = null): string
    {
        if (!$place) {
            return '';
        }

        $place = trim($place);
        if ($place === '') {
            return 'de ';
        }

        // Remove leading quotes/spaces and normalize casing
        $first = mb_strtolower(mb_substr($place, 0, 1));

        // Elision for vowels (safe). (H is intentionally not handled to avoid false positives.)
        if (in_array($first, ['a', 'e', 'i', 'o', 'u', 'y'], true)) {
            return "d'";
        }

        return 'de ';
    }

    /**
     * Build the SEO location label for a product variant, from an agency-like object
     * exposing city/department/region, according to the active dimension.
     *
     * @param mixed       $agency    Objet exposant ->city / ->department / ->region
     * @param string|null $dimension city|department|region (null = base)
     */
    public function getLocationLabel(mixed $agency, ?string $dimension = null): string
    {
        if (!is_object($agency)) {
            return '';
        }

        return $this->buildLocationLabel(
            !empty($agency->city) ? (string) $agency->city : null,
            !empty($agency->department) ? (string) $agency->department : null,
            !empty($agency->region) ? (string) $agency->region : null,
            $dimension,
        );
    }

    /**
     * Build the SEO location label from raw values + active dimension.
     * Source de vérité partagée (sitemap + substitution du token %location%).
     */
    public function buildLocationLabel(?string $city, ?string $department, ?string $region, ?string $dimension = null): string
    {
        return match ($dimension) {
            'city' => $city ? (($department && $department !== $city) ? sprintf('à %s (%s)', $city, $department) : 'à '.$city) : '',
            'department' => $department ? $this->concatPreposition($this->getDepartmentPreposition($department), $department) : '',
            'region' => $region ? $this->concatPreposition($this->getRegionPreposition($region), $region) : '',
            default => '',
        };
    }

    /**
     * Concatène une préposition et un nom en gérant l'espace (élision « l'/d' » incluse).
     */
    private function concatPreposition(string $preposition, string $name): string
    {
        $preposition = rtrim($preposition);

        return str_ends_with($preposition, "'") ? $preposition.$name : $preposition.' '.$name;
    }

    /**
     * Resolve an input (name, department code or postal code) into a normalized payload.
     *
     * @return array{code?: string, name?: string, prep?: string}
     */
    private function resolveDepartment(string $input): array
    {
        $departments = $this->getDepartments();        // code => ['name' => ..., 'prep' => ..., 'article' => ...]
        $nameToCode  = $this->getNameToCodeIndex();    // normalized name => code

        // 0) If input is a postal code (or contains one), resolve to department code.
        $codeFromPostal = $this->extractDepartmentCodeFromPostalCode($input);
        if ($codeFromPostal !== null) {
            if (isset($departments[$codeFromPostal])) {
                return [
                    'code' => $codeFromPostal,
                    'name' => $departments[$codeFromPostal]['name'],
                    'prep' => $departments[$codeFromPostal]['prep'],
                ];
            }

            return ['code' => $codeFromPostal];
        }

        $key = $this->normalize($input);

        // 1) Input is already a department code
        if ($this->looksLikeCode($key)) {
            $code = $this->formatCode($key);

            if (isset($departments[$code])) {
                return [
                    'code' => $code,
                    'name' => $departments[$code]['name'],
                    'prep' => $departments[$code]['prep'],
                ];
            }

            return ['code' => $code];
        }

        // 2) Input is a name (or alias)
        if (isset($nameToCode[$key])) {
            $code = $nameToCode[$key];

            return [
                'code' => $code,
                'name' => $departments[$code]['name'],
                'prep' => $departments[$code]['prep'],
            ];
        }

        return [];
    }

    /**
     * Build "dans le/la/l'/les" from article.
     *
     * @param string $article One of: le|la|l'|les|'' (empty)
     */
    private function buildDansArticle(string $article): string
    {
        if ($article === '') {
            // e.g. Paris (we avoid "dans le Paris")
            return 'à ';
        }

        if ($article === "l'") {
            return "dans l'";
        }

        return 'dans ' . $article;
    }

    /**
     * Build "tout le", "toute la", "tout l'", "toutes les" from article.
     *
     * @param string $article One of: le|la|l'|les|''
     */
    private function buildToutArticle(string $article): string
    {
        if ($article === '') {
            // e.g. Paris: "tout Paris"
            return 'tout ';
        }

        if ($article === 'les') {
            return 'toutes les ';
        }

        if ($article === 'la') {
            return 'toute la ';
        }

        // le or l'
        return 'tout ' . $article;
    }

    /**
     * Extract a department code from a French postal code.
     *
     * Rules:
     * - DOM: first 3 digits (971..976)
     * - Metropolitan: first 2 digits (01..95)
     * - Corsica: postal codes start with "20"
     *   - 20000..20199 => 2A (Corse-du-Sud)
     *   - 20200..20699 => 2B (Haute-Corse) (best-effort)
     *
     * @return string|null Canonical department code ("74", "2A", "971"...), or null if input is not a postal code.
     */
    private function extractDepartmentCodeFromPostalCode(string $input): ?string
    {
        $raw = trim($input);

        // Keep digits only (handles "74330 ", "74-330", etc.)
        $digits = preg_replace('~\D+~', '', $raw);
        if (!is_string($digits) || $digits === '' || strlen($digits) < 5) {
            return null;
        }

        $postal5 = substr($digits, 0, 5);

        // DOM departments: 971xx..976xx (excluding 975/977/978 as they are not departments)
        $first3 = substr($postal5, 0, 3);
        if (in_array($first3, ['971', '972', '973', '974', '976'], true)) {
            return $first3;
        }

        // Corsica: "20xxx" -> split into 2A/2B using common ranges
        $first2 = substr($postal5, 0, 2);
        if ($first2 === '20') {
            $n = (int) $postal5;
            return ($n >= 20200) ? '2B' : '2A';
        }

        // Metropolitan: first 2 digits
        return $first2;
    }

    /**
     * Normalize input for stable lookups (lowercase, remove accents, unify separators).
     */
    private function normalize(string $value): string
    {
        $value = trim($value);
        $value = mb_strtolower($value);

        // Replace typographic apostrophes and unify separators.
        $value = str_replace(['’', '\''], ' ', $value);
        $value = str_replace(['-', '_', '/', '\\', '.'], ' ', $value);

        // Deterministic accent stripping (iconv //TRANSLIT is locale-dependent).
        $value = strtr($value, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'å' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'í' => 'i',
            'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'õ' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
            'ç' => 'c', 'ÿ' => 'y', 'ñ' => 'n', 'œ' => 'oe', 'æ' => 'ae',
        ]);
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;

        // Keep only alnum + spaces.
        $value = preg_replace('~[^a-z0-9 ]+~', ' ', $value) ?? $value;

        // Collapse spaces.
        $value = preg_replace('~\s+~', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * Detect if normalized input is likely a department code.
     */
    private function looksLikeCode(string $normalized): bool
    {
        // "69", "01", "2a", "974"
        return preg_match('~^(?:\d{1,3}|2a|2b)$~', $normalized) === 1;
    }

    /**
     * Format a normalized code into the canonical representation:
     * - 1..9 => "01".."09"
     * - 10..95 => "10".."95"
     * - 971..976 => "971".."976"
     * - 2a/2b => "2A"/"2B"
     */
    private function formatCode(string $normalizedCode): string
    {
        $normalizedCode = trim(mb_strtolower($normalizedCode));

        if ($normalizedCode === '2a') {
            return '2A';
        }
        if ($normalizedCode === '2b') {
            return '2B';
        }

        if (ctype_digit($normalizedCode)) {
            // DOM keep 3 digits
            if (strlen($normalizedCode) === 3) {
                return $normalizedCode;
            }

            // Metropolitan pad to 2 digits
            return str_pad($normalizedCode, 2, '0', STR_PAD_LEFT);
        }

        return $normalizedCode;
    }

    /**
     * Build an index normalized-name => code (cached).
     *
     * @return array<string, string>
     */
    private function getNameToCodeIndex(): array
    {
        static $index = null;

        if (is_array($index)) {
            return $index;
        }

        $index = [];

        foreach ($this->getDepartments() as $code => $row) {
            $index[$this->normalize($row['name'])] = $code;
        }

        // Extra aliases (common variants)
        $index['cote dor'] = '21';
        $index['cote d or'] = '21';
        $index['alpes de haute provence'] = '04';
        $index['pyrenees atlantiques'] = '64';
        $index['pyrenees orientales'] = '66';
        $index['reunion'] = '974'; // allow "Reunion" without article

        return $index;
    }

    /**
     * Single source of truth: all departments with their canonical name + default preposition + article.
     *
     * article is used to build:
     * - "dans le/la/l'/les"
     * - "tout le / toute la / tout l' / toutes les"
     *
     * @return array<string, array{name: string, prep: string, article: string}> code => data
     */
    private function getDepartments(): array
    {
        static $departments = null;

        if (is_array($departments)) {
            return $departments;
        }

        // Helper to avoid repeating article logic
        $d = static function (string $name, string $prep, string $article): array {
            return ['name' => $name, 'prep' => $prep, 'article' => $article];
        };

        $departments = [
            '01' => $d('Ain', "dans l'", "l'"),
            '02' => $d('Aisne', "dans l'", "l'"),
            '03' => $d('Allier', "dans l'", "l'"),
            '04' => $d('Alpes-de-Haute-Provence', 'dans les ', 'les'),
            '05' => $d('Hautes-Alpes', 'dans les ', 'les'),
            '06' => $d('Alpes-Maritimes', 'dans les ', 'les'),
            '07' => $d('Ardèche', "dans l'", "l'"),
            '08' => $d('Ardennes', 'dans les ', 'les'),
            '09' => $d('Ariège', "dans l'", "l'"),

            '10' => $d('Aube', "dans l'", "l'"),
            '11' => $d('Aude', "dans l'", "l'"),
            '12' => $d('Aveyron', "dans l'", "l'"),
            '13' => $d('Bouches-du-Rhône', 'dans les ', 'les'),
            '14' => $d('Calvados', 'dans le ', 'le'),
            '15' => $d('Cantal', 'dans le ', 'le'),
            '16' => $d('Charente', 'dans la ', 'la'),
            '17' => $d('Charente-Maritime', 'dans la ', 'la'),
            '18' => $d('Cher', 'dans le ', 'le'),
            '19' => $d('Corrèze', 'dans la ', 'la'),

            '2A' => $d('Corse-du-Sud', 'en ', 'la'),
            '2B' => $d('Haute-Corse', 'en ', 'la'),

            '21' => $d("Côte-d'Or", 'dans la ', 'la'),
            '22' => $d("Côtes-d'Armor", 'dans les ', 'les'),
            '23' => $d('Creuse', 'dans la ', 'la'),
            '24' => $d('Dordogne', 'dans la ', 'la'),
            '25' => $d('Doubs', 'dans le ', 'le'),
            '26' => $d('Drôme', 'dans la ', 'la'),
            '27' => $d('Eure', "dans l'", "l'"),
            '28' => $d('Eure-et-Loir', "dans l'", "l'"),
            '29' => $d('Finistère', 'dans le ', 'le'),

            '30' => $d('Gard', 'dans le ', 'le'),
            '31' => $d('Haute-Garonne', 'en ', 'la'),
            '32' => $d('Gers', 'dans le ', 'le'),
            '33' => $d('Gironde', 'dans la ', 'la'),
            '34' => $d('Hérault', "dans l'", "l'"),
            '35' => $d('Ille-et-Vilaine', "dans l'", "l'"),
            '36' => $d('Indre', "dans l'", "l'"),
            '37' => $d('Indre-et-Loire', "dans l'", "l'"),
            '38' => $d('Isère', "dans l'", "l'"),
            '39' => $d('Jura', 'dans le ', 'le'),

            '40' => $d('Landes', 'dans les ', 'les'),
            '41' => $d('Loir-et-Cher', 'dans le ', 'le'),
            '42' => $d('Loire', 'dans la ', 'la'),
            '43' => $d('Haute-Loire', 'en ', 'la'),
            '44' => $d('Loire-Atlantique', 'en ', 'la'),
            '45' => $d('Loiret', 'dans le ', 'le'),
            '46' => $d('Lot', 'dans le ', 'le'),
            '47' => $d('Lot-et-Garonne', 'dans le ', 'le'),
            '48' => $d('Lozère', 'dans la ', 'la'),
            '49' => $d('Maine-et-Loire', 'dans le ', 'le'),

            '50' => $d('Manche', 'dans la ', 'la'),
            '51' => $d('Marne', 'dans la ', 'la'),
            '52' => $d('Haute-Marne', 'en ', 'la'),
            '53' => $d('Mayenne', 'dans la ', 'la'),
            '54' => $d('Meurthe-et-Moselle', 'dans la ', 'la'),
            '55' => $d('Meuse', 'dans la ', 'la'),
            '56' => $d('Morbihan', 'dans le ', 'le'),
            '57' => $d('Moselle', 'dans la ', 'la'),
            '58' => $d('Nièvre', 'dans la ', 'la'),
            '59' => $d('Nord', 'dans le ', 'le'),

            '60' => $d('Oise', "dans l'", "l'"),
            '61' => $d('Orne', "dans l'", "l'"),
            '62' => $d('Pas-de-Calais', 'dans le ', 'le'),
            '63' => $d('Puy-de-Dôme', 'dans le ', 'le'),
            '64' => $d('Pyrénées-Atlantiques', 'dans les ', 'les'),
            '65' => $d('Hautes-Pyrénées', 'dans les ', 'les'),
            '66' => $d('Pyrénées-Orientales', 'dans les ', 'les'),
            '67' => $d('Bas-Rhin', 'dans le ', 'le'),
            '68' => $d('Haut-Rhin', 'dans le ', 'le'),
            '69' => $d('Rhône', 'dans le ', 'le'),

            '70' => $d('Haute-Saône', 'en ', 'la'),
            '71' => $d('Saône-et-Loire', 'dans la ', 'la'),
            '72' => $d('Sarthe', 'dans la ', 'la'),
            '73' => $d('Savoie', 'en ', 'la'),
            '74' => $d('Haute-Savoie', 'en ', 'la'),
            '75' => $d('Paris', 'à', ''), // special: "tout Paris"
            '76' => $d('Seine-Maritime', 'dans la ', 'la'),
            '77' => $d('Seine-et-Marne', 'dans la ', 'la'),
            '78' => $d('Yvelines', 'dans les ', 'les'),
            '79' => $d('Deux-Sèvres', 'dans les ', 'les'),

            '80' => $d('Somme', 'dans la ', 'la'),
            '81' => $d('Tarn', 'dans le ', 'le'),
            '82' => $d('Tarn-et-Garonne', 'dans le ', 'le'),
            '83' => $d('Var', 'dans le ', 'le'),
            '84' => $d('Vaucluse', 'dans le ', 'le'),
            '85' => $d('Vendée', 'dans la ', 'la'),
            '86' => $d('Vienne', 'dans la ', 'la'),
            '87' => $d('Haute-Vienne', 'en ', 'la'),
            '88' => $d('Vosges', 'dans les ', 'les'),
            '89' => $d('Yonne', "dans l'", "l'"),

            '90' => $d('Territoire de Belfort', 'dans le ', 'le'),
            '91' => $d('Essonne', "dans l'", "l'"),
            '92' => $d('Hauts-de-Seine', 'dans les ', 'les'),
            '93' => $d('Seine-Saint-Denis', 'dans la ', 'la'),
            '94' => $d('Val-de-Marne', 'dans le ', 'le'),
            '95' => $d("Val-d'Oise", 'dans le ', 'le'),

            '971' => $d('Guadeloupe', 'en ', 'la'),
            '972' => $d('Martinique', 'en ', 'la'),
            '973' => $d('Guyane', 'en ', 'la'),
            '974' => $d('La Réunion', 'à', 'la'), // "dans toute la Réunion" possible
            '976' => $d('Mayotte', 'à', 'la'),
        ];

        return $departments;
    }

    /**
     * Single source of truth: French regions with canonical preposition + article.
     *
     * Keyed by normalized name. prep is concatenated directly before the region name,
     * article feeds "dans le/la/l'/les" and "tout(e)(s)" builders.
     *
     * @return array<string, array{prep: string, article: string}>
     */
    private function getRegions(): array
    {
        static $regions = null;

        if (is_array($regions)) {
            return $regions;
        }

        $r = static function (string $prep, string $article): array {
            return ['prep' => $prep, 'article' => $article];
        };

        $regions = [
            'auvergne rhone alpes' => $r('en ', 'la'),
            'bourgogne franche comte' => $r('en ', 'la'),
            'bretagne' => $r('en ', 'la'),
            'centre val de loire' => $r('dans le ', 'le'),
            'corse' => $r('en ', 'la'),
            'grand est' => $r('dans le ', 'le'),
            'hauts de france' => $r('dans les ', 'les'),
            'ile de france' => $r('en ', 'la'),
            'normandie' => $r('en ', 'la'),
            'nouvelle aquitaine' => $r('en ', 'la'),
            'occitanie' => $r('en ', 'la'),
            'pays de la loire' => $r('dans les ', 'les'),
            'provence alpes cote d azur' => $r('en ', 'la'),
            'guadeloupe' => $r('en ', 'la'),
            'martinique' => $r('en ', 'la'),
            'guyane' => $r('en ', 'la'),
            'la reunion' => $r('à ', 'la'),
            'mayotte' => $r('à ', 'la'),
        ];

        $regions['paca'] = $regions['provence alpes cote d azur'];
        $regions['reunion'] = $regions['la reunion'];

        return $regions;
    }
}