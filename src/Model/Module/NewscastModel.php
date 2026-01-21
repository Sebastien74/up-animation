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
     * @throws MappingException|NonUniqueResultException|QueryException
     */
    public static function fromEntity(Newscast $newscast, CoreLocatorInterface $coreLocator, array $options = []): object
    {
        $model = ViewModel::fromEntity($newscast, $coreLocator, array_merge($options));
        $showLabel = self::getContent('help', $model->category->intl);
        $publicationLabel = self::getContent('error', $model->category->intl);
        $backLabel = self::getContent('targetLabel', $model->category->intl);

        return (object) array_merge((array) $model, [
            'asEvent' => $model->category->entity && $model->category->entity->isAsEvents(),
            'showLabel' => $showLabel ?: self::$coreLocator->translator()->trans('En savoir +'),
            'publicationLabel' => $publicationLabel ?: self::$coreLocator->translator()->trans('Publié le'),
            'backLabel' => $backLabel ?: self::$coreLocator->translator()->trans('Retourner à la liste des publications'),
            'formPageUrl' => self::getFormPage($model),
        ]);
    }
}
