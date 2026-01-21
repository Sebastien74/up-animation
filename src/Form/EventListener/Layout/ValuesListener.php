<?php

declare(strict_types=1);

namespace App\Form\EventListener\Layout;

use App\Entity\Layout\FieldConfiguration;
use App\Form\EventListener\BaseListener;
use Symfony\Component\Form\FormEvent;

/**
 * ValuesListener.
 *
 * Listen Values Form attribute
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ValuesListener extends BaseListener
{
    /**
     * preSetData.
     */
    public function preSetData(FormEvent $event): void
    {
        /** @var FieldConfiguration $entity */
        $entity = $event->getData();
        $values = $entity->getFieldValues();

        foreach ($values as $value) {
            foreach ($this->locales as $locale) {
                $exist = $this->localeExist($value, $locale);
                if (!$exist) {
                    $this->addIntl($locale, $value);
                }
            }
        }
    }

    /**
     * Check if intl locale exist.
     */
    private function localeExist(mixed $entity, string $locale): bool
    {
        foreach ($entity->getIntls() as $existingIntl) {
            if ($existingIntl->getLocale() === $locale) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add intl.
     */
    private function addIntl(string $locale, mixed $entity): void
    {
        $intlData = $this->coreLocator->metadata($entity, 'intls');
        $intl = new ($intlData->targetEntity)();
        $intl->setLocale($locale);
        $intl->setWebsite($this->website->entity);
        if (method_exists($intl, $intlData->setter)) {
            $setter = $intlData->setter;
            $intl->$setter($entity);
        }
        $entity->addIntl($intl);
    }
}
