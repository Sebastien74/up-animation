<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Module\Newscast\Newscast;
use App\Model\Module\NewscastModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Query\QueryException;
use Psr\Cache\InvalidArgumentException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Date.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsLiveComponent]
class LastNews
{
    use DefaultActionTrait;

    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * Get last published news.
     *
     * @throws QueryException|MappingException|InvalidArgumentException
     */
    public function getLastNews(): ?array
    {
        $locale = $this->coreLocator->locale();
        $website = $this->coreLocator->website();
        $newscasts = $this->coreLocator->em()->getRepository(Newscast::class)->findOptimized($locale, $website->entity, 500);

        $result = [];
        foreach ($newscasts as $newscast) {

//            if (!$newscast) {
//                return null;
//            }
//
//            $intl = null;
//            foreach ($newscast->getIntls() as $newscastIntl) {
//                if ($newscastIntl->getLocale() === $locale) {
//                    $intl = $newscastIntl;
//                    break;
//                }
//            }
//
//            $url = null;
//            foreach ($newscast->getUrls() as $newscastUrl) {
//                if ($newscastUrl->getLocale() === $locale) {
//                    $url = $newscastUrl;
//                    break;
//                }
//            }
//
//            $filenames = [];
//            $mainFilename = null;
//            foreach ($newscast->getMediaRelations() as $mediaRelation) {
//                $filename = $mediaRelation->getMedia()?->getFilename();
//                if ($filename) {
//                    $filenames[] = $filename;
//                    if ($mediaRelation->isMain()) {
//                        $mainFilename = $filename;
//                    }
//                }
//            }
//
//            if (!$mainFilename && !empty($filenames)) {
//                $mainFilename = $filenames[0];
//            }
        }

//        dd($newscasts);

        return [
//            'entity' => $newscast,
//            'category' => $newscast->getCategory(),
//            'intl' => $intl,
//            'dates' => [
//                'startDate' => $newscast->getStartDate(),
//                'endDate' => $newscast->getEndDate(),
//                'publicationStart' => $newscast->getPublicationStart(),
//                'publicationEnd' => $newscast->getPublicationEnd(),
//                'publicationDate' => $newscast->getPublicationDate(),
//            ],
//            'filename' => $mainFilename,
//            'filenames' => $filenames,
//            'urlCode' => $url?->getCode(),
//            'seo' => $url?->getSeo(),
        ];
    }
}