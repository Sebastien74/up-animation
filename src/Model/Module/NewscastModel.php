<?php

declare(strict_types=1);

namespace App\Model\Module;

use App\Entity\Module\Newscast\Newscast;
use App\Entity\Module\Newscast\Teaser;
use App\Model\BaseModel;
use App\Model\ViewModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Psr\Cache\InvalidArgumentException;

/**
 * NewscastModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class NewscastModel extends BaseModel
{
    /**
     * fromEntity.
     *
     * @throws MappingException|NonUniqueResultException|QueryException|InvalidArgumentException
     */
    public static function fromEntity(Newscast $newscast, CoreLocatorInterface $coreLocator, array $options = []): object
    {
        $model = ViewModel::fromEntity($newscast, $coreLocator, array_merge($options));
        $category = $model->category;
        $categoryIntl = $category ? $category->intl : null;
        $showLabel = self::getContent('help', $categoryIntl);
        $publicationLabel = self::getContent('error', $categoryIntl);
        $backLabel = self::getContent('targetLabel', $categoryIntl);

        return (object) array_merge((array) $model, [
            'asEvent' => $category && $category->entity && $category->entity->isAsEvents(),
            'showLabel' => $showLabel ?: self::$coreLocator->translator()->trans('En savoir +'),
            'publicationLabel' => $publicationLabel ?: self::$coreLocator->translator()->trans('Publié le'),
            'backLabel' => $backLabel ?: self::$coreLocator->translator()->trans('Retourner à la liste des publications'),
            'formPageUrl' => self::getFormPage($model),
        ]);
    }
}
