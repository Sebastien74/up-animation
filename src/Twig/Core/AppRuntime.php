<?php

declare(strict_types=1);

namespace App\Twig\Core;

use App\Entity\Security\User;
use App\Entity\Security\UserFront;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\PersistentCollection;
use Exception;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Intl\Countries;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * AppRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AppRuntime implements RuntimeExtensionInterface
{
    /**
     * AppRuntime constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * To get route args to generate route.
     */
    public function routeArgs(?string $route = null, mixed $entity = null, array $parameters = []): array
    {
        return $this->coreLocator->routeArgs($route, $entity, $parameters);
    }

    /**
     * Find entity.
     */
    public function find(string $classname, mixed $id): mixed
    {
        if (!is_numeric($id)) {
            return false;
        }

        return $this->coreLocator->em()->getRepository($classname)->find($id);
    }

    /**
     * Check if is debug mode.
     */
    public function isDebug(): bool
    {
        return $this->coreLocator->isDebug();
    }

    /**
     * Check if route exist in PHP CLASS.
     */
    public function routeExist(string $routeName): bool
    {
        return $routeName && $this->coreLocator->routeExist($routeName);
    }

    /**
     * To set route name.
     */
    public function routeName(string $string): string
    {
        $string = str_replace(['-'], '_', $string);
        $matches = preg_split('/(?=[A-Z])/', $string);
        $routeName = '';
        foreach ($matches as $match) {
            $routeName .= '_'.strtolower($match);
        }

        return trim($routeName, '_');
    }

    /**
     * Get Class name.
     */
    public function getClass(mixed $class): ?string
    {
        return $class ? get_class($class) : null;
    }

    /**
     * Check if is Instance of.
     */
    public function instanceof(mixed $var, string $instance): bool
    {
        return $var && $var instanceof $instance;
    }

    /**
     * Check if is an object.
     */
    public function isObject(mixed $var): bool
    {
        return $var && is_object($var);
    }

    /**
     * Check if is an array.
     */
    public function isArray(mixed $var): bool
    {
        return $var && is_array($var);
    }

    /**
     * Check if is boolean.
     */
    public function isBool(mixed $var): bool
    {
        return is_bool($var);
    }

    /**
     * Check if is numeric value.
     */
    public function isNumeric(mixed $var): bool
    {
        return is_numeric($var);
    }

    /**
     * Check if is an email.
     */
    public function isEmail(mixed $var): bool
    {
        return is_string($var) && filter_var($var, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Check if is phone.
     */
    public function isPhone(mixed $var): bool
    {
        foreach (Countries::getNames() as $code => $name) {
            $phoneUtil = PhoneNumberUtil::getInstance();
            try {
                if ($phoneUtil->parse($var, strtoupper($code))) {
                    return true;
                }
            } catch (Exception $exception) {
                return false;
            }
        }

        return false;
    }

    /**
     * Check if is DateTime.
     */
    public function isDateTime(mixed $var = null): bool
    {
        return $var instanceof \DateTime;
    }

    /**
     * Check if is DateTime.
     *
     * @throws Exception
     */
    public function hourBetweenTwoDates(?\DateTime $date = null, ?\DateTime $dateTo = null): ?int
    {
        $dateTo = $this->isDateTime($dateTo) ? $dateTo : new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        if ($this->isDateTime($date)) {
            $interval = $date->diff($dateTo);

            return $interval->h + ($interval->days * 24);
        }

        return null;
    }

    /**
     * Check if string is DateTime.
     */
    public function stringToDate(mixed $var = null): ?array
    {
        $formats = ['Y-m-d H:i:s', 'Y-m-d'];
        foreach ($formats as $format) {
            if (false !== \DateTime::createFromFormat($format, $var)) {
                return [
                    'datetime' => \DateTime::createFromFormat($format, $var),
                    'format' => $format,
                ];
            }
        }

        return null;
    }

    /**
     * To convert to string.
     */
    public function strVal(mixed $var = null): ?string
    {
        return strval($var);
    }

    /**
     * Check if is UploadedFile.
     */
    public function isUploadedFile(mixed $var): bool
    {
        return $var instanceof UploadedFile;
    }

    /**
     * Check if is integer.
     */
    public function isInt(mixed $var): bool
    {
        return is_int($var);
    }

    /**
     * To add day(s) to date.
     *
     * @throws Exception
     */
    public function addDay(mixed $date, int $nbr = 1): \DateTime
    {
        $date = $date instanceof \DateTime ? $date : new \DateTime($date);

        return $date->add(new \DateInterval('P'.$nbr.'D'));
    }

    /**
     * To add minute(s) to date.
     *
     * @throws Exception
     */
    public function addMinute(mixed $date, int $nbr = 1): \DateTime
    {
        $date = $date instanceof \DateTime ? $date : new \DateTime($date);

        return $date->add(new \DateInterval('PT'.$nbr.'M'));
    }

    /**
     * Convert Object to Array.
     */
    public function objectToArray(mixed $object = null): array
    {
        $result = [];

        if ($this->isObject($object)) {
            $reflectionClass = $this->coreLocator->em()->getClassMetadata(get_class($object));
            $cols = array_merge($reflectionClass->fieldMappings, $reflectionClass->associationMappings);
            foreach ($cols as $property => $col) {
                $getter = 'get'.ucfirst($property);
                if (!method_exists($object, $getter)) {
                    $getter = 'is'.ucfirst($property);
                }
                if (method_exists($object, $getter)) {
                    $result[$property] = $object->$getter();
                    if (!empty($col['targetEntity'])) {
                        $subObject = new $col['targetEntity']();
                        $reflectionSubClass = $this->coreLocator->em()->getClassMetadata(get_class($subObject));
                        $subCols = array_merge($reflectionSubClass->fieldMappings, $reflectionSubClass->associationMappings);
                        foreach ($subCols as $subProperty => $subCol) {
                            $subGetter = 'get'.ucfirst($subProperty);
                            if (method_exists($subObject, $subGetter)) {
                                $result[$property.'.'.$subProperty] = $subObject->$subGetter();
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get entity value.
     */
    public function entityValue($object = null, ?string $attribute = null): mixed
    {
        $result = null;

        if (is_object($object) && $attribute) {
            $reflectionClass = $this->coreLocator->em()->getClassMetadata(get_class($object));
            $cols = array_merge($reflectionClass->fieldMappings, $reflectionClass->associationMappings);
            $properties = explode('.', $attribute);

            foreach ($properties as $property) {
                $getMethod = 'get'.ucfirst($property);
                $isMethod = 'is'.ucfirst($property);
                if (is_object($result) && method_exists($result, $getMethod)) {
                    $result = $result->$getMethod();
                } elseif (is_object($result) && method_exists($result, $isMethod)) {
                    $result = $result->$isMethod();
                } elseif (method_exists($object, $getMethod)) {
                    $result = $object->$getMethod();
                    if (!$object->getId() && !empty($cols[$property]) && !empty($cols[$property]['targetEntity'])) {
                        $result = new $cols[$property]['targetEntity']();
                    }
                } elseif (method_exists($object, $isMethod)) {
                    $result = $object->$isMethod();
                    if (!$object->getId() && !empty($cols[$property]) && !empty($cols[$property]['targetEntity'])) {
                        $result = new $cols[$property]['targetEntity']();
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get an unique ID.
     */
    public function uniqId(): string
    {
        return uniqid();
    }

    /**
     * Get an unique chars ID.
     */
    public function charsId(int $length = 10): string
    {
        $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $result = '';
        $count = strlen($charset);

        for ($i = 0; $i < $length; ++$i) {
            $result .= $charset[mt_rand(0, $count - 1)];
        }

        return $result;
    }

    /**
     * Unset array key.
     */
    public function unset(array $array, mixed $key = null, bool $first = false): array
    {
        if (is_numeric($key) || $key) {
            unset($array[$key]);
        } elseif ($first) {
            array_shift($array);
        }

        return $array;
    }

    /**
     * To unescape string.
     */
    public function unescape(?string $string = null): ?string
    {
        return $this->coreLocator->unescape($string);
    }

    /**
     * To remove session by name.
     */
    public function removeSession(?string $name = null): void
    {
        if ($name) {
            $session = new Session();
            $session->remove($name);
        }
    }

    /**
     * Decode URL.
     */
    public function urlDecode(?string $string = null): ?string
    {
        if ($string) {
            return urldecode($string);
        }

        return null;
    }

    /**
     * Serialize array.
     */
    public function serialize(array $array = []): ?string
    {
        if (is_array($array)) {
            return serialize($array);
        }

        return null;
    }

    /**
     * Unserialize array.
     */
    public function unserialize(?string $serialize = null): ?string
    {
        if (is_string($serialize)) {
            return unserialize($serialize);
        }

        return null;
    }

    /**
     * Implode array.
     */
    public function implode(array $pieces, string $glue): string
    {
        return implode($glue, $pieces);
    }

    /**
     * Get current request.
     */
    public function currentRequest(): ?Request
    {
        return $this->coreLocator->requestStack()->getCurrentRequest();
    }

    /**
     * Get current request.
     */
    public function masterRequest(): ?Request
    {
        return $this->coreLocator->request();
    }

    /**
     * Get master request attribute value.
     */
    public function masterRequestGet(string $attribute): mixed
    {
        $response = $this->coreLocator->request()->get($attribute);
        if (!$response && !empty($_GET[$attribute])) {
            $response = $_GET[$attribute];
        }

        return $response;
    }

    /**
     * To replace request param.
     */
    public function replaceRequestParam(string $param, mixed $newValue): string
    {
        $uri = $this->coreLocator->request()->getUri();

        foreach ($_GET as $name => $value) {
            if ($name === $param) {
                $uri = str_replace($name.'='.$value, $param.'='.$newValue, $uri);
            }
        }

        if (!str_contains($uri, $param.'=')) {
            $uri = !str_contains($uri, '?') ? $uri.'?'.$param.'='.$newValue : $uri.'&'.$param.'='.$newValue;
        }

        return $uri;
    }

    /**
     * Calculate age.
     *
     * @throws Exception
     */
    public function age(?\DateTime $startDate = null): ?int
    {
        if ($startDate) {
            $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
            $interval = $now->diff($startDate);
            return $interval->y;
        }

        return null;
    }

    /**
     * Calculate percent.
     */
    public function percent(int $finished, int $count): float|int
    {
        return ($finished * 100) / $count;
    }

    /**
     * Get current Request Client IP.
     */
    public function currentIP(): ?string
    {
        return $this->coreLocator->requestStack()->getCurrentRequest()->getClientIp();
    }

    /**
     * Set entities tree.
     */
    public function entityTree(array $entities): array
    {
        $tree = [];
        foreach ($entities as $entity) {
            $parent = $entity->getParent() ? $entity->getParent()->getId() : 'main';
            $tree[$parent][$entity->getPosition()] = $entity;
            ksort($tree[$parent]);
        }

        return $tree;
    }

    /**
     * Convert string date to Datetime.
     */
    public function datetime(?string $stringDate = null): ?\DateTime
    {
        if ($stringDate) {
            try {
                return new \DateTime($stringDate);
            } catch (Exception $exception) {
                return null;
            }
        }

        return null;
    }

    /**
     * Count strip_tags chars.
     */
    public function countChars(?string $string = null): int
    {
        return $string ? strlen(strip_tags($string)) : 0;
    }

    /**
     * Count entity collection by property.
     *
     * @throws NonUniqueResultException
     */
    public function countCollection(mixed $entity = null, ?string $property = null): ?int
    {
        $getter = 'get'.ucfirst($property);
        if ($entity && is_object($entity) && method_exists($entity, $getter) && is_iterable($entity->$getter())) {
            $entities = $entity->$getter();
            $count = count($entities);
            $referEntity = $entities instanceof PersistentCollection ? $entities->first() : null;
            if (is_object($referEntity) && method_exists($referEntity, 'getUrls')) {
                $interface = $this->coreLocator->interfaceHelper()->generate(get_class($referEntity));
                $masterField = !empty($interface['masterField']) ? $interface['masterField'] : null;
                $queryBuilder = $this->coreLocator->em()->getRepository($interface['classname'])
                    ->createQueryBuilder('e')
                    ->leftJoin('e.urls', 'u')
                    ->andWhere('u.archived = :archived')
                    ->setParameter('archived', false)
                    ->addSelect('u');
                if ($masterField) {
                    $queryBuilder->andWhere('e.'.$masterField.'= :masterFiled');
                    $queryBuilder->setParameter('masterFiled', $entity->getId());
                }
                $count = count($queryBuilder->getQuery()->getResult());
            }

            return $count;
        }

        return null;
    }

    /**
     * Check if in Admin.
     */
    public function inAdmin(): bool
    {
        return $this->coreLocator->inAdmin();
    }

    /**
     * Check if is UserFront instance.
     */
    public function isUserBack(mixed $user = null): bool
    {
        return $user instanceof User;
    }

    /**
     * Check if is UserFront instance.
     */
    public function isUserFront(mixed $user = null): bool
    {
        return $user instanceof UserFront;
    }

    /**
     * File get content in project dir.
     */
    public function fileGetContent(?string $dirname = null): ?string
    {
        if ($dirname) {
            return file_get_contents($this->coreLocator->projectDir().DIRECTORY_SEPARATOR.'templates/'.DIRECTORY_SEPARATOR.$dirname);
        }

        return null;
    }

    /**
     * File get content by URL.
     */
    public function fileGetContentURL(?string $url = null): ?string
    {
        if ($url) {
            return file_get_contents($url);
        }

        return null;
    }

    /**
     * Get first key of array.
     */
    public function arrayKeyFirst(array $array = []): ?string
    {
        return array_key_first($array);
    }

    /**
     * Remove text between.
     */
    public function removeBetween(string $string, array $tags): ?string
    {
        if (empty($tags[1])) {
            return preg_replace('/<\s*'.$tags[0].'.+?<\s*\/\s*'.$tags[0].'.*?>/si', ' ', $string);
        }
        return preg_replace('/\\'.$tags[0].'([^()]*+|(?R))*\\'.$tags[1].'/', '', $string);
    }

    /**
     * Remove text between.
     */
    public function removeEmptyTags(?string $string = null, array $tags = ['p']): ?string
    {
        if ($string) {
            $string = str_replace(["&nbsp;", "\u{A0}"], ' ', $string);
            $string = preg_replace_callback(
                '/<([a-z0-9]+)(?:\s[^>]*)?>\s*<\/\1>/i',
                function ($matches) use ($tags) {
                    if (empty($tags) || in_array($matches[1], $tags)) {
                        return '';
                    }
                    return $matches[0];
                },
                $string
            );
            $string = preg_replace('/\s{2,}/', ' ', trim($string));
        }

        return $string;
    }

    /**
     * Get environment variable.
     */
    public function getEnv(?string $name = null): bool|string
    {
        return $name && !empty($_ENV[$name]) ? $_ENV[$name] : false;
    }

    /**
     * Die in twig file.
     */
    public function killRender(mixed $message = null): void
    {
        if ($this->coreLocator->isDebug()) {
            dump($message);
            exit;
        }
    }
}
