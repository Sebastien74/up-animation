<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Management;

use App\Entity\Layout\Block;
use App\Entity\Layout\Col;
use App\Entity\Layout\Zone;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ScreensType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ScreensType
{
    private TranslatorInterface $translator;

    /**
     * ScreensType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    /**
     * Add field.
     */
    public function add(FormBuilderInterface $builder, array $options = []): void
    {
        $entity = isset($options['entity']) && $options['entity'] ? $options['entity'] : null;

        if (isset($options['parentCount']) && $options['parentCount']) {
            $count = $entity instanceof Col ? $entity->getZone()->getCols()->count() : ($entity instanceof Block ? $entity->getCol()->getBlocks()->count() : 0);
        } else {
            $count = $entity instanceof Zone ? $entity->getCols()->count() : ($entity instanceof Col ? $entity->getBlocks()->count() : 0);
        }

        if ($count) {
            $choices = [];
            $choices[$this->translator->trans('Par défault', [], 'admin')] = null;
            for ($x = 1; $x <= $count; ++$x) {
                $choices[$x] = $x;
            }

            $builder->add('mobilePosition', ChoiceType::class, [
                'required' => false,
                'label' => isset($options['mobilePositionLabel']) ? $this->translator->trans('Ordre sur mobile', [], 'admin') : false,
                'display' => 'search',
                'choices' => $choices,
                'attr' => ['group' => $options['mobilePositionGroup'] ?? 'col-md-6'],
            ]);

            $builder->add('tabletPosition', ChoiceType::class, [
                'required' => false,
                'label' => isset($options['tabletPositionLabel']) ? $this->translator->trans('Ordre sur tablette', [], 'admin') : false,
                'display' => 'search',
                'choices' => $choices,
                'attr' => ['group' => $options['tabletPositionGroup'] ?? 'col-md-6'],
            ]);

            $builder->add('miniPcPosition', ChoiceType::class, [
                'required' => false,
                'label' => isset($options['miniPcPositionLabel']) ? $this->translator->trans('Ordre sur mini PC', [], 'admin') : false,
                'display' => 'search',
                'choices' => $choices,
                'attr' => ['group' => $options['miniPcPositionGroup'] ?? 'col-md-6'],
            ]);
        }

        $sizeChoices = [];
        $sizeChoices[$this->translator->trans('Par défault', [], 'admin')] = null;
        for ($i = 1; $i <= 12; ++$i) {
            $sizeChoices[$i] = $i;
        }

        $builder->add('mobileSize', ChoiceType::class, [
            'label' => isset($options['mobileSizeLabel']) ? $this->translator->trans('Taille sur mobile', [], 'admin') : false,
            'required' => false,
            'choices' => $sizeChoices,
            'display' => 'search',
            'attr' => ['group' => $options['mobileSizeGroup'] ?? 'col-md-6'],
        ]);

        $builder->add('tabletSize', ChoiceType::class, [
            'label' => isset($options['tabletSizeLabel']) ? $this->translator->trans('Taille sur tablette', [], 'admin') : false,
            'required' => false,
            'choices' => $sizeChoices,
            'display' => 'search',
            'attr' => ['group' => $options['tabletSizeGroup'] ?? 'col-md-6'],
        ]);

        $builder->add('miniPCSize', ChoiceType::class, [
            'label' => isset($options['miniPCSizeLabel']) ? $this->translator->trans('Taille sur mini PC', [], 'admin') : false,
            'required' => false,
            'choices' => $sizeChoices,
            'display' => 'search',
            'attr' => ['group' => $options['miniPCSizeGroup'] ?? 'col-md-6'],
        ]);
    }
}
