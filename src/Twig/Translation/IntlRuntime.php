<?php

declare(strict_types=1);

namespace App\Twig\Translation;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Intl\Languages;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * IntlRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class IntlRuntime implements RuntimeExtensionInterface
{
    private const array LOCALES_CODES = [
        'EN' => 'GB',
    ];

    private const array TIME_FORMATS = [
        'none' => \IntlDateFormatter::NONE,
        'short' => \IntlDateFormatter::SHORT,
        'medium' => \IntlDateFormatter::MEDIUM,
        'long' => \IntlDateFormatter::LONG,
        'full' => \IntlDateFormatter::FULL,
    ];

    private array $dateFormatters = [];

    private ?Request $request;

    private static function availableDateFormats(): array
    {
        static $formats = null;

        if (null !== $formats) {
            return $formats;
        }

        $formats = [
            'none' => \IntlDateFormatter::NONE,
            'short' => \IntlDateFormatter::SHORT,
            'medium' => \IntlDateFormatter::MEDIUM,
            'long' => \IntlDateFormatter::LONG,
            'full' => \IntlDateFormatter::FULL,
        ];

        // Assuming that each `RELATIVE_*` constant are defined when one of them is.
        if (\defined('IntlDateFormatter::RELATIVE_FULL')) {
            $formats = array_merge($formats, [
                'relative_short' => \IntlDateFormatter::RELATIVE_SHORT,
                'relative_medium' => \IntlDateFormatter::RELATIVE_MEDIUM,
                'relative_long' => \IntlDateFormatter::RELATIVE_LONG,
                'relative_full' => \IntlDateFormatter::RELATIVE_FULL,
            ]);
        }

        return $formats;
    }

    /**
     * IntlRuntime constructor.
     */
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Environment $templating)
    {
        $this->request = $this->requestStack->getMainRequest();
    }

    /**
     * Get reading direction.
     */
    public function readingDirection(?string $locale = null): string
    {
        return in_array($locale, [
            'ar', // Arabic
            'he', // Hebrew
            'fa', // Persian (Farsi)
            'ur', // Urdu
            'ps', // Pashto
            'ar-eg', // Egyptian Arabic
            'ar-gulf', // Gulf Arabic
            'ar-sy', // Syrian Arabic
            'ku', // Kurdish (Kurmanji)
            'yi' // Yiddish
        ]) ? 'rtl' : 'ltr';
    }

    /**
     * Get canonicalize locale.
     */
    public function canonicalizeLocale(?string $locale = null, string $separator = '-'): ?string
    {
        if ($locale) {
            return str_replace('_', $separator, \Locale::canonicalize($locale));
        }
        return null;
    }

    /**
     * Get countries names by locale.
     */
    public function countryNames(?string $locale = null): array
    {
        if ($locale) {
            \Locale::setDefault($locale);
        }
        return Countries::getNames();
    }

    /**
     * Get country name by locale.
     */
    public function countryName(?string $countryCode = null, ?string $locale = null): ?string
    {
        if (!$countryCode) {
            return null;
        }

        if ($locale) {
            \Locale::setDefault($locale);
        }

        $countryCode = !empty(self::LOCALES_CODES[strtoupper($countryCode)]) ? self::LOCALES_CODES[strtoupper($countryCode)] : $countryCode;

        return Countries::getName(strtoupper($countryCode));
    }

    /**
     * Get languages name.
     */
    public function languagesName(?string $locale = null): array
    {
        if ($locale) {
            \Locale::setDefault($locale);
        }
        return Languages::getNames();
    }

    /**
     * Get language name by locale.
     */
    public function languageName(string $countryCode, ?string $locale = null): ?string
    {
        if (!$countryCode) {
            return null;
        }

        if ($locale) {
            \Locale::setDefault($locale);
        }

        return Languages::getName($countryCode);
    }

    /**
     * Get currencies names by locale.
     */
    public function currencyNames(?string $locale = null): array
    {
        if ($locale) {
            \Locale::setDefault($locale);
        }
        return Currencies::getNames();
    }

    /**
     * Get currency name by locale.
     */
    public function currencyName(?string $currencyCode = null, ?string $locale = null): ?string
    {
        if (!$currencyCode) {
            return null;
        }

        if ($locale) {
            \Locale::setDefault($locale);
        }

        return Currencies::getName($currencyCode);
    }

    /**
     * Get currency symbol by locale.
     */
    public function currencySymbol(string $currencyCode, ?string $locale = null): ?string
    {
        if (!$currencyCode) {
            return null;
        }

        if ($locale) {
            \Locale::setDefault($locale);
        }

        return Currencies::getSymbol($currencyCode);
    }

    /**
     * Get day name in current locale by date.
     *
     * @throws Exception
     */
    public function localeDayNameByDateTime(\DateTime $datetime, ?string $locale = null): ?string
    {
        return $this->localizedDate($this->templating, $datetime, 'medium', 'medium', $locale, null, 'cccc');
    }

    /**
     * Get day name in current locale by date.
     *
     * @throws Exception
     */
    public function localeMonthNameByDateTime(\DateTime $datetime, ?string $locale = null): ?string
    {
        return $this->localizedDate($this->templating, $datetime, 'medium', 'medium', $locale, null, 'MMMM');
    }

    /**
     * Get day name in current locale by english day name.
     *
     * @throws Exception
     */
    public function localeDayNameByEnglishName(string $dayName, ?string $locale = null): ?string
    {
        $today = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $datetime = new \DateTime(date('d-m-Y', strtotime('first '.$dayName.' '.$today->format('Y').'-01')));
        return $this->localeDayNameByDateTime($datetime);
    }

    /**
     * Converts an input to a \DateTime instance to locale string.
     *
     * @throws RuntimeError|Exception
     */
    public function localizedDate(
        Environment $env, $date,
        ?string $dateFormat = 'medium',
        ?string $timeFormat = 'medium',
        string $pattern = '',
        $timezone = null,
        string $calendar = 'gregorian',
        ?string $locale = null): bool|string
    {
        $date = $env->getExtension(CoreExtension::class)->convertDate($date, $timezone);
        $formatterTimezone = $timezone;
        if (null === $formatterTimezone) {
            $formatterTimezone = $date->getTimezone();
        } elseif (\is_string($formatterTimezone)) {
            $formatterTimezone = new \DateTimeZone($timezone);
        }
        $formatter = $this->createDateFormatter($locale, $dateFormat, $timeFormat, $pattern, $formatterTimezone, $calendar);
        if (false === $ret = $formatter->format($date)) {
            throw new RuntimeError('Unable to format the given date.');
        }
        if ($date instanceof \DateTime) {
            $year = $date->format('Y');
            $nextYear = strval(intval($year) + 1);
            $ret = str_contains($ret, $nextYear) ? str_replace($nextYear, $year, $ret) : $ret;
        }

        return $ret;
    }

    /**
     * Create date formatter.
     */
    private function createDateFormatter(?string $locale, ?string $dateFormat, ?string $timeFormat, string $pattern, ?\DateTimeZone $timezone, string $calendar): \IntlDateFormatter
    {
        $dateFormats = self::availableDateFormats();

        if (null !== $dateFormat && !isset($dateFormats[$dateFormat])) {
            throw new RuntimeError(sprintf('The date format "%s" does not exist, known formats are: "%s".', $dateFormat, implode('", "', array_keys($dateFormats))));
        }

        if (null !== $timeFormat && !isset(self::TIME_FORMATS[$timeFormat])) {
            throw new RuntimeError(sprintf('The time format "%s" does not exist, known formats are: "%s".', $timeFormat, implode('", "', array_keys(self::TIME_FORMATS))));
        }

        $formatter = new \IntlDateFormatter($locale, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT);

        if (null === $locale) {
            $locale = $formatter->getLocale();
            $locale = $locale ?: \Locale::getDefault();
        }

        $calendar = 'gregorian' === $calendar ? \IntlDateFormatter::GREGORIAN : \IntlDateFormatter::TRADITIONAL;
        $dateFormatValue = $dateFormats[$dateFormat] ?? null;
        $timeFormatValue = self::TIME_FORMATS[$timeFormat] ?? null;
        $dateFormatValue = $dateFormatValue ?: $formatter->getDateType();
        $timeFormatValue = $timeFormatValue ?: $formatter->getTimeType();
        $timezone = $timezone ?: $formatter->getTimeZone()->toDateTimeZone();
        $calendar = $calendar ?: $formatter->getCalendar();
        $pattern = $pattern ?: $formatter->getPattern();
        $timezoneName = $timezone ? $timezone->getName() : '(none)';
        $hash = $locale.'|'.$dateFormatValue.'|'.$timeFormatValue.'|'.$timezoneName.'|'.$calendar.'|'.$pattern;

        if (!isset($this->dateFormatters[$hash])) {
            $this->dateFormatters[$hash] = new \IntlDateFormatter($locale, $dateFormatValue, $timeFormatValue, $timezone, $calendar, $pattern);
        }

        return $this->dateFormatters[$hash];
    }

    /**
     * Get format Date by locale.
     *
     * @throws Exception
     */
    public function formatDate(?string $locale = null): object
    {
        $locale = !$locale ? $this->request->getLocale() : $locale;
        $formatter = new \IntlDateFormatter($locale, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT);
        $fullOrigin = $formatter->getPattern();
        $matches = explode(' ', $fullOrigin);

        $matchesFormat = explode('/', $matches[0]);
        if ('dd' === $matchesFormat[0]) {
            $formatter->setPattern('DD/MM/YYYY H:i:s');
        } else {
            $formatter->setPattern('YYYY/MM/DD g:i:s A');
        }

        $large = $formatter->getPattern();
        $matchesLarge = explode(' ', $large);
        $monthDay = !empty($matches[0]) ? rtrim(ltrim(str_replace('y', '', $matches[0]), '/'), '/') : null;
        $month = rtrim(ltrim(str_replace('d', '', $monthDay), '/'), '/');

        if ('dd' === $matchesFormat[0]) {
            $formatter->setPattern('dd/mm/yyyy');
        } else {
            $formatter->setPattern('yyyy/mm/dd');
        }
        $datepicker = $formatter->getPattern();

        return (object) [
            'dateTime' => $fullOrigin,
            'dateTimeLarge' => $large,
            'date' => !empty($matches[0]) ? $matches[0] : null,
            'dateLarge' => !empty($matchesLarge[0]) ? $matchesLarge[0] : null,
            'dayMonth' => strtolower($monthDay),
            'dayMonthLarge' => $monthDay,
            'month' => strtolower($month),
            'monthLarge' => $month,
            'time' => !empty($matches[0]) ? $matches[0] : null,
            'timeLarge' => !empty($matches[1]) ? $matches[1] : null,
            'inputDate' => str_replace(['dd', 'mm', 'yyyy'], ['d', 'm', 'Y'], $datepicker),
            'datepickerPHP' => str_replace('mm', 'MM', $datepicker),
            'datepickerJS' => $datepicker,
        ];
    }
}
