<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Management;

use App\Entity\Core\Transition;
use App\Entity\Core\Website;
use App\Repository\Core\TransitionRepository;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * TransitionType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TransitionType
{
    private TranslatorInterface $translator;
    private Website $website;

    /**
     * TransitionType constructor.
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
        $this->website = isset($options['website']) && $options['website'] instanceof Website ? $options['website'] : null;

        if ($this->website) {
            $builder->add('transition', EntityType::class, [
                'required' => false,
                'label' => $this->translator->trans('Éffet', [], 'admin'),
                'display' => 'search',
                'class' => Transition::class,
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'attr' => ['group' => 'col-md-4'],
                'query_builder' => function (TransitionRepository $repository) {
                    return $repository->createQueryBuilder('t')
                        ->andWhere('t.active = :active')
                        ->andWhere('t.configuration = :configuration')
                        ->andWhere('t.section IS NULL')
                        ->setParameter('active', true)
                        ->setParameter('configuration', $this->website->getConfiguration())
                        ->orderBy('t.adminName', 'ASC');
                },
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getAdminName());
                },
            ]);

            $builder->add('delay', Type\IntegerType::class, [
                'required' => false,
                'label' => $this->translator->trans('Délai avant apparition', [], 'admin'),
                'attr' => [
                    'group' => 'col-md-4',
                    'placeholder' => $this->translator->trans('Saisissez un délai', [], 'admin'),
                ],
                'help' => $this->translator->trans('Optionnel', [], 'admin'),
            ]);

            $builder->add('duration', Type\IntegerType::class, [
                'required' => false,
                'label' => $this->translator->trans('Durée de la transition', [], 'admin'),
                'attr' => [
                    'group' => 'col-md-4',
                    'placeholder' => $this->translator->trans('Saisissez une durée', [], 'admin'),
                ],
                'help' => $this->translator->trans('Optionnel', [], 'admin'),
            ]);
        }
    }
}
