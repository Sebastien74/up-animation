<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Block;

use App\Entity\Layout\Layout;
use App\Service\Interface\CoreLocatorInterface;
use App\Twig\Translation\i18nRuntime;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FieldLayoutChoiceType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FieldLayoutChoiceType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * FieldLayoutChoiceType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly i18nRuntime $i18nRuntime,
    ) {
        $this->translator = $this->coreLocator->translator();
    }

    /**
     * Add type.
     *
     * @throws NonUniqueResultException
     */
    public function add(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('associatedElements', ChoiceType::class, [
            'multiple' => true,
            'required' => false,
            'label' => $this->translator->trans('Éléments associés', [], 'admin'),
            'display' => 'search',
            'attr' => [
                'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            ],
            'row_attr' => ['class' => $options['asDynamic'] ? 'col-md-8' : 'col-md-12'],
            'choices' => $this->getChoices($options['layout']),
        ]);
    }

    /**
     * To get choices.
     *
     * @throws NonUniqueResultException
     */
    private function getChoices(Layout $layout): array
    {
        $choices = [];
        foreach ($layout->getZones() as $zone) {
            $zoneName = 'Zone '.$zone->getPosition();
            $choices['Zones'][$zoneName] = 'zone-'.$zone->getId();
            foreach ($zone->getCols() as $col) {
                $colName = 'Colonne '.$col->getPosition().' ('.$zoneName.')';
                $choices['Colonnes'][$colName] = 'col-'.$col->getId();
                foreach ($col->getBlocks() as $block) {
                    $intl = $this->i18nRuntime->intl($block);
                    $title = $intl && $intl->getTitle() ? $intl->getTitle() : str_replace('(form)', '', $block->getBlockType()->getAdminName());
                    $blockName = 'Bloc '.$block->getPosition().' - '.trim($title).' ('.$zoneName.' - Colonne '.$col->getPosition().')';
                    $choices['Bloc'][$blockName] = 'block-'.$block->getId();
                }
            }
        }

        return $choices;
    }
}
