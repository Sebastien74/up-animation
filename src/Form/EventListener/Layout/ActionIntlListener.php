<?php

declare(strict_types=1);

namespace App\Form\EventListener\Layout;

use App\Entity\Layout\ActionIntl;
use App\Entity\Layout\Block;
use App\Form\EventListener\BaseListener;
use Symfony\Component\Form\FormEvent;

/**
 * ActionIntlListener.
 *
 * Listen ActionIntl Form attribute
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ActionIntlListener extends BaseListener
{
    /**
     * preSetData.
     */
    public function preSetData(FormEvent $event): void
    {
        /** @var Block $entity */
        $entity = $event->getData();

        if (!empty($entity)) {
            foreach ($this->locales as $locale) {
                $intl = null;
                foreach ($entity->getActionIntls() as $existingIntl) {
                    if ($existingIntl->getLocale() === $locale) {
                        $intl = $existingIntl;
                    }
                }
                if (!$intl) {
                    $intl = new ActionIntl();
                    $intl->setLocale($locale);
                    $entity->addActionIntl($intl);
                }
            }
        }
    }
}
