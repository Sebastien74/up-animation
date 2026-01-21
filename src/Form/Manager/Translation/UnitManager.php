<?php

declare(strict_types=1);

namespace App\Form\Manager\Translation;

use App\Entity\Core\Website;
use App\Entity\Translation\Translation;
use App\Entity\Translation\TranslationUnit;
use App\Service\Translation\Extractor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\FormInterface;

/**
 * UnitManager.
 *
 * Manage intl Unit admin form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => UnitManager::class, 'key' => 'intl_unit_form_manager'],
])]
class UnitManager
{
    /**
     * UnitManager constructor.
     */
    public function __construct(
        private readonly Extractor $extractor,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @prePersist
     */
    public function onFlush(TranslationUnit $translationUnit, Website $website): void
    {
        $this->extractor->clearCache();
    }

    /**
     * Add Unit.
     */
    public function addUnit(FormInterface $form, Website $website): void
    {
        $existingUnit = $unit = $this->entityManager->getRepository(TranslationUnit::class)->findOneBy([
            'keyName' => $form->getData()['keyName'],
            'domain' => $form->getData()['domain'],
        ]);

        if (!$unit) {
            $unit = new TranslationUnit();
            $unit->setDomain($form->getData()['domain']);
            $unit->setKeyname($form->getData()['keyName']);
            $this->entityManager->persist($unit);
        }

        foreach ($website->getConfiguration()->getAllLocales() as $locale) {
            $translation = $existingUnit ? $this->entityManager->getRepository(Translation::class)->findOneBy([
                'unit' => $unit,
                'locale' => $locale,
            ]) : false;

            if (!$translation) {
                $translation = new Translation();
                $translation->setLocale($locale);
                $translation->setUnit($unit);
                $this->entityManager->persist($translation);
            }
        }

        $this->entityManager->flush();
        $this->extractor->clearCache();
    }
}
