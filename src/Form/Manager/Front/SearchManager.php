<?php

declare(strict_types=1);

namespace App\Form\Manager\Front;

use App\Entity\Core\Website;
use App\Entity\Layout;
use App\Entity\Media\Media;
use App\Entity\Module\Catalog\FeatureValue;
use App\Entity\Module\Catalog\Product;
use App\Entity\Module\Search\Search;
use App\Entity\Module\Search\SearchValue;
use App\Model\MediaModel;
use App\Model\ViewModel;
use App\Service\Content\SitemapService;
use App\Service\Core\InterfaceHelper;
use App\Service\Core\StopWords;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Cache\InvalidArgumentException;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * SearchManager.
 *
 * Manage front Search Action
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SearchManager
{
    private const bool ACTIVE_STOP_WORDS = true;
    private const string BOOLEAN_MODE = 'IN BOOLEAN MODE';
    private const string LANGUAGE_MODE = 'IN NATURAL LANGUAGE MODE';
    private const array PROPERTIES = ['title' => 5, 'body' => 4, 'introduction' => 3, 'associatedWords' => 5];

    private ?Request $request;
    private string $uploadDirname = '';
    private array $sitemap = [];
    private array $results = [];

    /**
     * SearchManager constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly EntityManagerInterface $entityManager,
        private readonly InterfaceHelper $interfaceHelper,
        private readonly SitemapService $sitemapService,
        private readonly PaginatorInterface $paginator,
        private readonly StopWords $stopWords,
        private readonly TranslatorInterface $translator,
    ) {
        $this->request = $this->coreLocator->request();
    }

    /**
     * Execute request.
     *
     * @throws InvalidArgumentException|NonUniqueResultException|MappingException|QueryException|ReflectionException
     */
    public function execute(Search $search, Website $website, ?string $searchTerm = null): array
    {
        $results = [];
        $mode = 'boolean' === $search->getMode() ? self::BOOLEAN_MODE : self::LANGUAGE_MODE;
        $searchTerm = empty($this->clearText($searchTerm)) ? $searchTerm : $this->clearText($searchTerm);

        $this->uploadDirname = 'uploads/'.$website->getUploadDirname().'/';

        foreach ($search->getEntities() as $classname) {
            $interface = $this->interfaceHelper->generate($classname);
            $results[$interface['name']] = [];
            if ('pdf' === $classname) {
                $pdf = $this->getPDF($mode, $website, $searchTerm);
                if ($pdf) {
                    foreach ($pdf as $key => $file) {
                        $pdf[$key]['entity'] = $entityModel = MediaModel::fromEntity($file[0], $this->coreLocator);
                        $snippet = '';
                        foreach (['filename', 'name'] as $property) {
                            if (property_exists($entityModel, $property)) {
                                $snippet = $this->getHighlightedSnippet($entityModel->$property, $searchTerm);
                                if ($snippet) {
                                    break;
                                }
                            }
                        }
                        $pdf[$key]['snippets'] = $snippet;
                        unset($pdf[$key][0]);
                    }
                    $results['pdf'] = $pdf;
                }
            } else {
                if (Product::class === $classname && $searchTerm) {
                    $featuresValue = $this->getByClassname(FeatureValue::class, $mode, $website, $searchTerm);
                    $queryResult = $this->getByRelations($classname, $mode, $website, $searchTerm, ['values' => ['items' => $featuresValue, 'subRelation' => 'value']]);
                    if ($queryResult) {
                        $results[$interface['name']] = array_merge($results[$interface['name']], $queryResult);
                    }
                }
                $referEntity = new $classname();
                if (method_exists($referEntity, 'getCategories')) {
                    $metadata = $this->entityManager->getClassMetadata($classname);
                    $categoryClassname = $metadata->associationMappings['categories']['targetEntity'];
                    $categories = $this->getByClassname($categoryClassname, $mode, $website, $searchTerm);
                    $queryResult = $this->getByRelations($classname, $mode, $website, $searchTerm, ['categories' => ['items' => $categories]]);
                    if ($queryResult) {
                        $results[$interface['name']] = array_merge($results[$interface['name']], $queryResult);
                    }
                }
                $queryResult = $this->getByClassname($classname, $mode, $website, ucfirst($searchTerm));
                if ($queryResult) {
                    $results[$interface['name']] = array_merge($results[$interface['name']], $queryResult);
                }
            }
        }

        $response = $this->init($search, $results, $website, $searchTerm, [
            Product::class => ['sort' => 'catalog', 'order' => ['position' => 'ASC']],
        ]);

        $this->registerSearch($search, $response, $searchTerm);

        return $response;
    }

    /**
     * Get PDF.
     */
    private function getPDF(string $mode, Website $website, string $searchTerm): array
    {
        $repository = $this->entityManager->getRepository(Media::class);

        $against = '(';
        $against .= "(MATCH_AGAINST(m.filename, :matchAgainst '".$mode."') * 5) + ";
        $against .= "(CASE WHEN LOWER(m.filename) LIKE LOWER(:likeSearch) THEN 5 ELSE 0 END) + ";
        $against .= "(MATCH_AGAINST(m.name, :matchAgainst '".$mode."') * 4) + ";
        $against .= "(CASE WHEN LOWER(m.name) LIKE LOWER(:likeSearch) THEN 4 ELSE 0 END) + ";
        $against = rtrim($against, '+ ').') as score';

        $searchQuery = $searchTerm;
        $searchTerms = array_map(
            fn($term) => preg_replace('/[\'"]/', '', strtolower(trim($term))),
            explode(' ', $searchQuery)
        );
        $matchAgainst = '+'. implode(' +', array_map('strtolower', $searchTerms));
        $likeSearch = '%'. implode('%', array_map('strtolower', $searchTerms)).'%';

        return $repository->createQueryBuilder('m')->select('m')
            ->andWhere('m.extension = :extension')
            ->andWhere('m.website = :website')
            ->setParameter('matchAgainst', $matchAgainst)
            ->setParameter('likeSearch', $likeSearch)
            ->setParameter('website', $website)
            ->setParameter('extension', 'pdf')
            ->addSelect($against)
            ->having('score > 0')
            ->orderBy('score', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get result by classname.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    private function getByClassname(string $classname, string $mode, Website $website, string $searchTerm): array
    {
        $entityObj = new $classname();
        $repository = $this->entityManager->getRepository($classname);

        $searchTerms = array_map(
            fn($term) => preg_replace('/[\'"]/', '', strtolower(trim($term))),
            explode(' ', $searchTerm)
        );
        $matchAgainst = '+'.implode(' +', array_map('strtolower', $searchTerms));
        $likeSearch = '%'.implode('%', array_map('strtolower', $searchTerms)).'%';

        $against = '(';
        foreach (self::PROPERTIES as $property => $score) {
            $against .= '(MATCH_AGAINST(i.'.$property.", :matchAgainst '".$mode."') * ".$score.') + ';
            $against .= "(CASE WHEN LOWER(i.$property) LIKE LOWER(:likeSearch) THEN $score ELSE 0 END) + ";
        }
        $against = rtrim($against, '+ ').') as score';

        $statement = $repository->createQueryBuilder('e')
            ->select('e')
            ->andWhere('i.locale = :locale')
            ->andWhere('i.website = :website')
            ->setParameter('locale', $this->request->getLocale())
            ->setParameter('website', $website)
            ->setParameter('matchAgainst', $matchAgainst)
            ->setParameter('likeSearch', $likeSearch)
            ->addSelect($against)
            ->addSelect('i')
            ->having('score > 0')
            ->orderBy('score', 'DESC');

        if (method_exists($entityObj, 'getIntls')) {
            $statement->leftJoin('e.intls', 'i');
        } elseif (method_exists($entityObj, 'getIntl')) {
            $statement->leftJoin('e.intl', 'i');
        }

        if (method_exists($entityObj, 'getUrls')) {
            $statement->leftJoin('e.urls', 'u')
                ->andWhere('u.online = :online')
                ->andWhere('u.locale = :locale')
                ->setParameter('online', true);
        }

        $results = $statement->getQuery()->getResult();

        foreach ($results as $key => $result) {
            $entityModel = ViewModel::fromEntity($result[0], $this->coreLocator, ['disabledLayout' => true, 'disabledMedias' => true]);
            $snippets = [];
            foreach (self::PROPERTIES as $property => $score) {
                if (property_exists($entityModel->intl, $property)) {
                    $snippet = $this->getHighlightedSnippet($entityModel->intl->$property, $searchTerm);
                    if ($snippet) {
                        $snippets[$property] = $snippet;
                    }
                }
            }
            $results[$key]['entity'] = $entityModel;
            $results[$key]['snippets'] = $snippets;
            unset($results[$key][0]);
        }

        return $results;
    }

    /**
     * Get by relations.
     */
    private function getByRelations(string $classname, string $mode, Website $website, ?string $searchTerm = null, array $relations = []): array
    {
        if (!empty($relations)) {
            $entityObj = new $classname();
            $repository = $this->entityManager->getRepository($classname);
            $statement = $repository->createQueryBuilder('e')
                ->select('e')
                ->andWhere('i.locale = :locale')
                ->andWhere('i.website = :website')
                ->setParameter('locale', $this->request->getLocale())
                ->setParameter('website', $website)
                ->addSelect('i');

            if (method_exists($entityObj, 'getIntls')) {
                $statement->leftJoin('e.intls', 'i');
            } elseif (method_exists($entityObj, 'getIntl')) {
                $statement->leftJoin('e.intl', 'i');
            }

            if (method_exists($entityObj, 'getUrls')) {
                $statement->leftJoin('e.urls', 'u')
                    ->andWhere('u.online = :online')
                    ->andWhere('u.locale = :locale')
                    ->setParameter('online', true);
            }

            $execute = false;
            $against = '(';
            foreach ($relations as $name => $items) {
                if (!empty($items['items'])) {
                    $execute = true;
                    $matchKey = $name;
                    $statement->leftJoin('e.'.$name, $name)
                        ->addSelect($name);
                    if (!empty($items['subRelation'])) {
                        $matchKey = $items['subRelation'];
                        $relationKey = $name.'.'.$matchKey;
                        $statement->leftJoin($relationKey, $matchKey)
                            ->addSelect($matchKey);
                    }
                    foreach ($items['items'] as $item) {
                        $relation = $item['entity'];
                        $parameter = 'search_'.str_replace('-', '_', $relation->slug);
                        $against .= '(MATCH_AGAINST('.$matchKey.'.slug, :'.$parameter." '".$mode."') * 5) + ";
                        $statement->setParameter($parameter, $relation->slug);
                    }
                }
            }

            $against = rtrim($against, '+ ').') as score';
            $statement->addSelect($against)
                ->having('score > 0')
                ->addOrderBy('score', 'DESC');

            $results = $execute ? $statement->getQuery()->getResult() : [];
            if ($results) {
                foreach ($results as $key => $values) {
                    $results[$key]['entity'] = ViewModel::fromEntity($values[0], $this->coreLocator, ['disabledLayout' => true, 'disabledMedias' => true]);
                    $results[$key]['score'] = $values['score'] + 500 - $key;
                    $snippets = [];
                    foreach ($relations as $keyName => $relation) {
                        foreach ($relation['items'] as $item) {
                            $entityModel = $item['entity'];
                            foreach (self::PROPERTIES as $property => $score) {
                                if (property_exists($entityModel->intl, $property)) {
                                    $snippet = $this->getHighlightedSnippet($entityModel->intl->$property, $searchTerm);
                                    if ($snippet) {
                                        $snippets[$property] = $snippet;
                                    }
                                }
                            }
                        }
                    }
                    if (!empty($snippets)) {
                        $results[$key]['snippets']['relations'] = $snippets;
                    }
                    unset($results[$key][0]);
                }
            }

            return $results;
        }

        return [];
    }

    /**
     * Init results.
     *
     * @throws InvalidArgumentException|NonUniqueResultException|MappingException|QueryException|ReflectionException
     */
    private function init(Search $search, array $queryResults, Website $website, ?string $textParameter = null, array $parameters = []): array
    {
        $orderBy = $search->getOrderBy();
        $page = 1;
        $limit = $search->getItemsPerPage();
        $entities = [];
        $checkItems = [];
        $countLimit = 0;

        if (empty($this->sitemap)) {
            $this->sitemap = $this->sitemapService->execute($website, $this->request->getLocale(), true, true);
        }

        foreach ($queryResults as $interfaceName => $result) {
            if (!empty($result[0]) && is_array($result[0])) {
                foreach ($result as $entity) {
                    $isFormField = !empty($entity['entity']) && $entity['entity']->entity instanceof Layout\Block && !empty($entity['entity']->entity->getFieldConfiguration());
                    $interfaceName = !empty($entity['interfaceName']) ? $entity['interfaceName'] : $interfaceName;
                    $infos = is_array($entity) ? $this->getInfos($entity['entity'], $interfaceName, $textParameter) : null;
                    if ($infos && $entity) {
                        $classname = !empty($infos['classname']) ? $infos['classname'] : get_class($entity['entity']);
                        $id = $infos['entity']->id;
                        $alreadyInResult = !empty($checkItems[$interfaceName]) && in_array($id, $checkItems[$interfaceName]);
                        if (!$isFormField && !$alreadyInResult) {
                            $orderKey = $this->getOrderKey($orderBy, $entity, $classname, $parameters);
                            $entities[$orderKey] = [
                                'entity' => $entity['entity'],
                                'interfaceName' => $interfaceName,
                                'url' => $infos['url'],
                                'snippets' => !empty($entity['snippets']) ? $entity['snippets'] : false,
                                'score' => $orderKey
                            ];
                            $checkItems[$interfaceName][] = $id;
                        }
                    }
                }
            }
        }
        krsort($entities);

        foreach ($entities as $entity) {
            if ($search->isFilterGroup()) {
                $existing = $this->setGroups($page, $entity, $orderBy, $entity['score'], $entity);
            } else {
                $existing = $this->setItems($page, $entity['score'], $entity, $orderBy);
            }
            if (!$existing) {
                ++$countLimit;
            }
            if ($countLimit === $limit) {
                $countLimit = 0;
                ++$page;
            }
        }

        return [
            'results' => $this->results,
            'pagination' => $this->paginator->paginate(
                $entities,
                $this->request->query->getInt('page', 1),
                $limit,
                ['wrap-queries' => true]
            ),
            'counts' => $this->getCounts($search),
        ];
    }

    /**
     * Get By Layout.
     *
     * @throws NonUniqueResultException
     */
    private function getByLayout(mixed $entity): mixed
    {
        if (!empty($entity[0]) && $entity[0] instanceof Layout\Block) {
            $layout = $entity[0]->getCol()->getZone()->getLayout();

            $layoutEntities = [];
            $interfaces = [];
            $allMetasData = $this->entityManager->getMetadataFactory()->getAllMetadata();
            foreach ($allMetasData as $metadata) {
                $classname = $metadata->getName();
                $referEntity = 0 === $metadata->getReflectionClass()->getModifiers() ? new $classname() : null;
                if ($referEntity && method_exists($referEntity, 'getLayout')) {
                    $interface = $this->interfaceHelper->generate($classname);
                    if (isset($interface['search']) && $interface['search'] && !empty($interface['name'])) {
                        $layoutEntities[] = $interface['name'];
                        $interfaces[$interface['name']] = $interface['name'];
                    }
                }
            }

            $metasData = $this->entityManager->getClassMetadata(Layout\Layout::class);
            $mappings = $metasData->getAssociationMappings();

            foreach ($mappings as $mapping) {
                $getter = 'get'.ucfirst($mapping['fieldName']);
                $getter = method_exists($layout, $getter) ? $getter : 'is'.ucfirst($mapping['fieldName']);
                if (in_array($mapping['fieldName'], $layoutEntities) && method_exists($layout, $getter) && !empty($layout->$getter())) {
                    $entity[0] = $layout->$getter();
                    $entity['interfaceName'] = $interfaces[$mapping['fieldName']];

                    return $entity;
                }
            }
        }

        return null;
    }

    /**
     * Get url infos.
     *
     * @throws NonUniqueResultException|MappingException|ReflectionException|QueryException
     */
    private function getInfos(mixed $findEntity, string $interfaceName, ?string $textParameter = null): array
    {
        $infos = [];
        $entity = null;

        if ($findEntity instanceof MediaModel) {
            $infos['entity'] = $findEntity;
            $infos['url'] = $this->uploadDirname.$findEntity->filename;
            $infos['interfaceName'] = $interfaceName;
            $infos['infos'] = null;
        } elseif ($findEntity instanceof Layout\Block) {
            $entity = $this->entityManager->getRepository(Layout\Page::class)->findByBlock($findEntity);
            $entity = ViewModel::fromEntity($entity, $this->coreLocator, ['disabledLayout' => true, 'disabledMedias' => true]);
            $interfaceName = $this->interfaceHelper->generate(Layout\Page::class)['name'];
        } else {
            $entity = $findEntity;
        }

        if ($entity && !empty($this->sitemap[$interfaceName][$entity->id])) {
            $translation = $this->translator->trans('singular', [], 'entity_'.$interfaceName);
            $label = 'singular' !== $translation ? $translation : ucfirst($interfaceName);
            $infos['entity'] = $entity;
            $infos['interfaceName'] = $interfaceName;
            $infos['classname'] = is_object($entity->entity) ? get_class($entity->entity) : null;
            $infos['url'] = $entity->url;
            $infos['urlCode'] = $entity->urlCode;
            $infos['label'] = $label;
        }

        return $infos;
    }

    /**
     * Get order key for array results.
     *
     * @throws Exception
     */
    private function getOrderKey(string $orderBy, array $entityResult, string $classname, array $parameters = []): int|string
    {
        $entity = $entityResult['entity']->entity;
        $score = $entityResult['score'];
        if (str_contains($orderBy, 'date')) {
            $publicationDate = method_exists($entity, 'getPublicationStart') && $entity->getPublicationStart() ? $entity->getPublicationStart()
                : (method_exists($entity, 'getUpdatedAt') && $entity->getUpdatedAt() ? $entity->getUpdatedAt()
                    : (method_exists($entity, 'getCreatedAt') && $entity->getCreatedAt() ? $entity->getCreatedAt() : new \DateTime('now', new \DateTimeZone('Europe/Paris'))));

            return intval($publicationDate->format('YmdHis')).uniqid();
        } else {
            $parameters = !empty($parameters[$classname]) ? $parameters[$classname] : [];
            $sortBy = !empty($parameters['sort']) ? $parameters['sort'] : null;
            $orders = !empty($parameters['order']) ? $parameters['order'] : null;
            $orderBy = !empty($orders) ? array_key_first($orders) : null;
            $direction = !empty($orders) ? $orders[array_key_first($orders)] : null;
            $method = $sortBy ? 'get'.ucfirst($sortBy) : null;
            $orderProperty = $method && method_exists($entity, $method) ? $entity->$method() : null;
            $score = round($score);
            $score = str_replace('.', '', strval($score)).mt_rand(111111, 999999);
            if ($sortBy && $orderBy && $direction && $orderProperty && is_object($orderProperty)) {
                $keyMethod = 'get'.ucfirst($orderBy);
                $keySort = $orderProperty->$keyMethod();
                $keySort = is_numeric($keySort) && 'ASC' === $direction
                    ? (1000000 - $keySort) : (is_numeric($keySort) && 'DESC' === $direction ? (1000000 + $keySort) : $keySort);

                return $keySort.intval($score);
            } else {
                return intval($score);
            }
        }
    }

    /**
     * Set group item.
     */
    private function setGroups(int $page, array $infos, string $orderBy, mixed $orderKey, array $infosRow): bool
    {
        $existing = !empty($this->results[$page]['items'][$infos['interfaceName']][$orderKey]);
        $this->results[$page]['items'][$infos['interfaceName']][$orderKey] = $infosRow;
        if ('date-desc' === $orderBy) {
            krsort($this->results[$page]['items'][$infos['interfaceName']]);
        } elseif ('date-asc' === $orderBy || 'score' === $orderBy) {
            krsort($this->results[$page]['items'][$infos['interfaceName']]);
        }

        return $existing;
    }

    /**
     * Set item.
     */
    private function setItems(int $page, mixed $orderKey, array $infosRow, string $orderBy): bool
    {
        $existing = !empty($this->results[$page]['items'][$orderKey]);
        $this->results[$page]['items'][$orderKey] = $infosRow;
        if ('date-desc' === $orderBy) {
            krsort($this->results[$page]['items']);
        } elseif ('date-asc' === $orderBy) {
            ksort($this->results[$page]['items']);
        }

        return $existing;
    }

    /**
     * To remove stop words from text.
     */
    private function clearText(string $text): ?string
    {
        $this->stopWords->init();
        $text = str_replace(['*'], '', $text);
        if (self::ACTIVE_STOP_WORDS) {
            $stopWords = $this->stopWords->stopWords($this->request->getLocale());
            foreach ($stopWords as $word) {
                $pattern = '/\b'.$word.'\b/i';
                $text = preg_replace($pattern, '', $text);
            }
        }
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Get counts.
     */
    private function getCounts(Search $search): array
    {
        $counters = [];
        $counters['all'] = 0;
        foreach ($this->results as $key => $results) {
            foreach ($results as $keyName => $items) {
                if ($search->isFilterGroup()) {
                    foreach ($items as $key => $item) {
                        $counters = $this->getCount($key, $item, $counters);
                    }
                } else {
                    $counters = $this->getCount($keyName, $items, $counters);
                }
            }
        }

        return $counters;
    }

    /**
     * Get count.
     */
    private function getCount(mixed $key, array $items, array $counters): array
    {
        $previousCount = !empty($counters[$key]) ? $counters[$key] : 0;
        $counters[$key] = $previousCount > 0 ? $previousCount + count($items) : count($items);
        $counters['all'] = count($items) + $counters['all'];

        return $counters;
    }

    /**
     * To register search value.
     */
    private function registerSearch(Search $search, array $response = [], ?string $text = null): void
    {
        if ($text && $search->isRegisterSearch()) {
            $search = $this->entityManager->getRepository(Search::class)->find($search->getId());
            $searchValue = $this->entityManager->getRepository(SearchValue::class)->findOneBy([
                'search' => $search,
                'text' => $text,
            ]);
            if (!$searchValue instanceof SearchValue) {
                $searchValue = new SearchValue();
                $searchValue->setSearch($search);
                $searchValue->setText($text);
            }
            $count = $searchValue->getCounter() + 1;
            $searchValue->setCounter($count);
            $resultCount = !empty($response['counts']['all']) ? $response['counts']['all'] : 0;
            $searchValue->setResultCount($resultCount);
            $this->entityManager->persist($searchValue);
            $this->entityManager->flush();
        }
    }

    private function getHighlightedSnippet(?string $text = null, ?string $searchQuery = null, int $contextLength = 300): string|bool
    {
        if (!$text || !$searchQuery || !$contextLength) {
            return false;
        }

        static $tagFilter = null;
        if (null === $tagFilter) {
            $tagFilter = '/[\r\n\t\x{00A0}]/u';
        }

        $text = strip_tags($text);
        $text = preg_replace($tagFilter, ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        
        $searchTerms = explode(' ', trim($searchQuery));
        $firstMatchPos = false;
        foreach ($searchTerms as $term) {
            $pos = stripos($text, $term);
            if ($pos !== false) {
                $firstMatchPos = $pos;
                break;
            }
        }

        if ($firstMatchPos === false) {
            return false;
        }

        $start = max(0, $firstMatchPos - $contextLength / 2);
        $snippet = mb_substr($text, $start, $contextLength);

        foreach ($searchTerms as $term) {
            $snippet = preg_replace(
                '/('.preg_quote($term, '/').')/i',
                '<mark>$1</mark>',
                $snippet
            );
        }

        $snippet = $snippet && mb_strlen($text) > $start + $contextLength ? $snippet.'...' : $snippet;

        return str_contains($snippet, '<mark>') ? $snippet : false;
    }
}
