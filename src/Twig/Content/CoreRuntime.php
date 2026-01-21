<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Entity\Core\Website;
use App\Entity\Information\Phone;
use App\Model\Core\WebsiteModel;
use App\Service\Content\CryptService;
use App\Service\Core\Urlizer;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * CoreRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CoreRuntime implements RuntimeExtensionInterface
{
    /**
     * CoreRuntime constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly Environment $templating,
        private readonly CryptService $cryptService,
    ) {
    }

    /**
     * Get last route.
     */
    public function lastRoute(): ?string
    {
        $lastRoute = preg_match('/\/admin-'.$_ENV['SECURITY_TOKEN'].'/', $this->coreLocator->request()->getUri())
            ? $this->coreLocator->request()->getSession()->get('last_route_back')
            : $this->coreLocator->request()->getSession()->get('last_route');

        try {
            if (is_object($lastRoute) && property_exists($lastRoute, 'name') && $this->coreLocator->routeExist($lastRoute->name)) {
                return $this->coreLocator->router()->generate($lastRoute->name, $lastRoute->params);
            }
        } catch (\Exception $exception) {
            return $this->coreLocator->request()->headers->get('referer');
        }

        return $this->coreLocator->request()->headers->get('referer');
    }

    /**
     * json_decode
     */
    public function jsonDecode($json): mixed
    {
        return json_decode($json, true);
    }

    /**
     * Generate view for email.
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function email(string $email, WebsiteModel $website, array $options = []): void
    {
        echo $this->templating->render('core/email.html.twig', [
            'email' => $email,
            'website' => $website,
            'only_href' => !isset($options['only_href']) ? true : $options['only_href'],
            'icon' => !isset($options['icon']) ? true : $options['icon'],
            'entitled' => !isset($options['entitled']) ? true : $options['entitled'],
            'options' => $options,
        ]);
    }

    /**
     * Generate view for phone.
     *
     * @throws LoaderError|RuntimeError|SyntaxError
     */
    public function phone(mixed $phone, WebsiteModel $website, array $options = [])
    {
        if (is_string($phone)) {
            return null;
        }

        if (is_array($phone) && !isset($phone['id'])) {
            $data = $phone;
            $phone = new Phone();
            $phone->setTagNumber($data['link']);
            $phone->setNumber($data['label']);
        }

        if ($phone instanceof Phone && empty($phone->getType())) {
            $phone->setType('office');
        }

        echo $this->templating->render('core/phone.html.twig', [
            'phone' => $phone,
            'website' => $website,
            'only_href' => !isset($options['only_href']) ? true : $options['only_href'],
            'icon' => !isset($options['icon']) ? true : $options['icon'],
            'entitled' => !isset($options['entitled']) ? true : $options['entitled'],
            'options' => $options,
        ]);
    }

    /**
     * Generate view for address.
     *
     * @throws LoaderError|RuntimeError|SyntaxError
     */
    public function address(mixed $address, WebsiteModel $website, ?string $zone = null, ?array $options = []): void
    {
        echo $this->templating->render('core/address.html.twig', [
            'address' => $address,
            'website' => $website,
            'zone' => $zone,
            'options' => $options,
        ]);
    }

    /**
     * Convert text for mailto link.
     */
    public function mailToBody(string $text): string
    {
        $response = str_replace("\r\n", '<br>', $text);
        $response = str_replace('</p>', '</p><br>', $response);
        $response = str_replace('<ul>', '', $response);
        $response = str_replace('</ul>', '', $response);
        $response = str_replace('<li>', '%0D%0A%09•%20', $response);
        $response = str_replace('</li>', '', $response);
        $response = str_replace('<br/>', '%0D%0A', $response);
        $response = str_replace('<br>', '%0D%0A', $response);
        $response = str_replace(' ', '%20', $response);

        return strip_tags($response);
    }

    /**
     * Urlize string.
     */
    public function urlize(?string $string = null, bool $asFile = false): ?string
    {
        if ($asFile) {
            $filesExtensions = ['png', 'jpg', 'jpeg', 'svg', 'mp3', 'mp4', 'gif'];
            $matches = explode('.', $string);
            $extension = end($matches);
            if (in_array($extension, $filesExtensions)) {
                $string = str_replace('.'.$extension, '', $string);
            }
        }

        return Urlizer::urlize($string);
    }

    /**
     * To remove HTML attributes except <a> tag.
     */
    public function removeHtmlAttrs(string $html, array $except = ['a']): ?string
    {
        preg_match_all("/<([a-z][a-z0-9]*)[^>]*?(\/?)>/si", $html, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[0] as $key => $match) {
                if (!in_array($matches[1][$key], $except) && strlen($match) > 10) {
                    $html = str_replace($match, '<'.$matches[1][$key].'>', $html);
                }
            }
        }

        return preg_replace('/(<[^>]+) style=".*?"/i', '$1', $html);
    }

    /**
     * Encrypt string.
     *
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException|\ReflectionException
     */
    public function encrypt(string $string, Website $website): ?string
    {
        $website = WebsiteModel::fromEntity($website, $this->coreLocator);

        return $this->cryptService->execute($website, $string, 'e');
    }

    /**
     * Decrypt string.
     *
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException|\ReflectionException
     */
    public function decrypt(string $string, Website $website): ?string
    {
        $website = WebsiteModel::fromEntity($website, $this->coreLocator);

        return $this->cryptService->execute($website, $string, 'd');
    }

    /**
     * Get URI infos.
     */
    public function uriInfos(?string $string = null): ?array
    {
        if ($string) {

            $clean = str_replace($this->coreLocator->request()->getSchemeAndHttpHost(), '', $string);
            $matches = explode('#', $clean);
            $samePage = false;
            if (!empty($matches[0]) && !empty($matches[1])) {
                $clean = str_replace(['/#'.$matches[1]], '', $clean);
                $samePage = $clean === $this->coreLocator->request()->getRequestUri();
            }

            return [
                'samePage' => $samePage,
                'uri' => $matches[0],
                'anchor' => !empty($matches[1]) ? $matches[1] : null,
            ];
        }

        return null;
    }

    /**
     * Truncate string.
     */
    public function truncate(?string $string = null, int $length = 30, bool $dotes = true): ?string
    {
        if ($string) {

            // Remove HTML tags
            $originalString = strip_tags($string);

            // Replace specific HTML tags with spaces
            $string = str_replace(['</p>', '<br>', '<br/>'], [' '], $string);

            // Convert special characters to HTML entities
            $string = $this->htmlEntities($string);

            // If the string is already shorter than $length, return it as is
            if (mb_strlen($string, 'UTF-8') <= $length) {
                return $string;
            }

            // Truncate the string at $length
            $truncated = mb_substr($string, 0, $length, 'UTF-8');

            // Avoid cutting the last word
            if (!ctype_space(mb_substr($string, $length, 1, 'UTF-8'))) {
                // Find the last space before $length
                $lastSpace = mb_strrpos($truncated, ' ', 0, 'UTF-8');
                if ($lastSpace !== false) {
                    $truncated = mb_substr($truncated, 0, $lastSpace, 'UTF-8');
                }
            }

            // Check if we need to add ellipsis (...)
            $lastChar = mb_substr($truncated, -1, 1, 'UTF-8');
            $endSentencesChars = ['.', ',', '!', '?'];
            $dotes = $dotes && mb_strlen($originalString, 'UTF-8') > $length && !in_array($lastChar, $endSentencesChars) ? '...' : '';

            return $truncated . $dotes;
        }

        return null;
    }

    /**
     * Convert centimeter to meter.
     */
    public function centimeterToMeter(?int $centimeter = 0): ?string
    {
        $meter = $centimeter > 0 ? (float) ($centimeter / 100) : null;
        if ($meter) {
            $meter = str_replace('.', 'm', strval($meter));
        }

        return $meter;
    }

    /**
     * Convert minutes to hours.
     */
    public function minutesToHour(?int $time = 0, string $format = '%02d:%02d'): ?string
    {
        if ($time > 1) {
            $hours = floor($time / 60);
            $minutes = ($time % 60);

            return sprintf($format, $hours, $minutes);
        }

        return null;
    }

    /**
     * Check if first character is vowel.
     */
    public function firstIsVowel(?string $string = null): bool
    {
        if (!$string) {
            return false;
        }
        $vowels = ['a', 'e', 'i', 'o', 'u', 'y'];
        $firstChar = substr($string, 0, 1);

        return in_array(strtolower($firstChar), $vowels);
    }

    /**
     * To convert html.
     */
    public function htmlEntities(?string $string = null, bool $stripTag = true): ?string
    {
        if (!$string) {
            return null;
        }

        $string = trim(html_entity_decode(mb_convert_encoding($string, 'UTF-8'), ENT_QUOTES, 'UTF-8'));
        $string = preg_replace('/\\s+/', ' ', $string);

        if ($stripTag) {
            $string = strip_tags($string);
            $string = preg_replace('/(?=[^\n\r\t])\p{Cc}/u', '', $string);
            //			$string = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F\x00-\x1F\x80-\xFF]/', '', $string);
            $string = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F\x00-\x1F]/', '', $string);
            $string = str_replace("\u{FEFF}", '', $string);
        }

        return $string;
    }

    /**
     * To convert string to int.
     */
    public function intVal(mixed $string = null): ?int
    {
        return $string ? intval($string) : null;
    }

    /**
     * To shuffle array.
     */
    public function shuffle(array $array = []): array
    {
        shuffle($array);

        return $array;
    }
}
