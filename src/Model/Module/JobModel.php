<?php

declare(strict_types=1);

namespace App\Model\Module;

use App\Entity\Module\Recruitment\Job;
use App\Model\BaseModel;
use App\Model\EntityModel;
use App\Model\ViewModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;

/**
 * JobModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class JobModel extends BaseModel
{
    /**
     * fromEntity.
     *
     * @throws MappingException|NonUniqueResultException|QueryException|\Exception
     */
    public static function fromEntity(Job $job, CoreLocatorInterface $coreLocator, array $options = []): object
    {
        $model = (array) ViewModel::fromEntity($job, $coreLocator, array_merge($options));
        $model['promote'] = self::getContent('promote', $job, true);
        $model['place'] = self::getContent('place', $job);
        $model['zipCode'] = self::getContent('zipCode', $job);
        $model['zipCodeSmall'] = $model['zipCode'] ? substr($model['zipCode'], 0, 2) : null;
        $model['department'] = self::getContent('department', $job);
        $model['body'] = !empty($model['intl']) ? $model['intl']->body : false;
        $model['introduction'] = !empty($model['intl']) ? $model['intl']->introduction : false;
        $model['remuneration'] = !empty($model['intl']) ? $model['intl']->intl->getRemuneration() : false;
        $model['duration'] = !empty($model['intl']) ? $model['intl']->intl->getDuration() : false;
        $model['company'] = !empty($model['intl']) ? $model['intl']->intl->getCompany() : false;
        $model['diploma'] = !empty($model['intl']) ? $model['intl']->intl->getDiploma() : false;
        $model['drivingLicence'] = !empty($model['intl']) ? $model['intl']->intl->getDrivingLicence() : false;
        $model['profil'] = !empty($model['intl']) ? $model['intl']->intl->getProfil() : false;
        $model['contract'] = $job->getContract() ? (array) EntityModel::fromEntity($job->getContract(), self::$coreLocator)->response : false;
        $model['contract']['title'] = !empty($model['contract']['intl']) ? $model['contract']['intl']->title : false;
        $model['form'] = self::getContent('form', $job);

        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $model['date'] = self::getContent('date', $job);
        if ($model['date'] instanceof \DateTime && $now >= $model['date']) {
            $model['date'] = $coreLocator->translator()->trans('Dès que possible', [], 'front');
        }

        return (object) array_merge($model, [
        ]);
    }
}
