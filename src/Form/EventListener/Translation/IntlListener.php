<?php

declare(strict_types=1);

namespace App\Form\EventListener\Translation;

use App\Form\EventListener\BaseListener;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * IntlListener.
 *
 * Listen intl Form attribute
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class IntlListener extends BaseListener
{
    /**
     * preSetData.
     */
    public function preSetData(FormEvent $event): void
    {
        $session = new Session();
        $entity = $event->getData();
        if (empty($entity->getIntl())) {
            $intlData = $this->coreLocator->metadata($entity, 'intl');
            $intl = new ($intlData->targetEntity)();
            $intl->setWebsite($this->website->entity);
            if ($intlData->setter && method_exists($intl, $intlData->setter)) {
                $setter = $intlData->setter;
                $intl->$setter($entity);
            }
            $intl->setLocale($session->get('currentEntityLocale'));
            $entity->setIntl($intl);
        }
    }
}
