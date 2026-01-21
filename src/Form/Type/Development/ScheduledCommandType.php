<?php

declare(strict_types=1);

namespace App\Form\Type\Development;

use App\Entity\Core\ScheduledCommand;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ScheduledCommandType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ScheduledCommandType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * ScheduledCommandType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, ['adminNameGroup' => $isNew ? 'col-md-6' : 'col-md-4']);

        $builder->add('command', CommandChoiceType::class, [
            'label' => $this->translator->trans('Commande', [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'display' => 'search',
            'attr' => ['group' => $isNew ? 'col-md-6' : 'col-md-4'],
        ]);

        $builder->add('cronExpression', Type\TextType::class, [
            'label' => $this->translator->trans('Expression cron', [], 'admin'),
            'attr' => [
                'group' => $isNew ? 'col-md-6' : 'col-md-4',
                'placeholder' => $this->translator->trans('*/10 * * * *', [], 'admin'),
            ],
            'help' => '<a href="http://www.abunchofutils.com/utils/developer/cron-expression-helper/" target="_blank">'.$this->translator->trans('Générer', [], 'admin').'</a>',
        ]);

        $builder->add('description', Type\TextType::class, [
            'label' => $this->translator->trans('Description', [], 'admin'),
            'attr' => [
                'group' => $isNew ? 'col-md-6' : 'col-md-9',
                'placeholder' => $this->translator->trans('Saisissez une description*', [], 'admin'),
            ],
        ]);

        if (!$isNew) {
            $builder->add('logFile', Type\TextType::class, [
                'required' => false,
                'label' => $this->translator->trans('Nom du fichier de log', [], 'admin'),
                'attr' => [
                    'group' => 'col-md-3',
                    'placeholder' => $this->translator->trans('Saisissez un nom', [], 'admin'),
                ],
            ]);

            $builder->add('executeImmediately', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Exécuter maintenant', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
            ]);

            $builder->add('active', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Activer', [], 'admin'),
                'attr' => ['group' => 'col-md-2', 'class' => 'w-100'],
            ]);

            $builder->add('locked', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Bloquée suite erreur', [], 'admin'),
                'attr' => ['group' => 'col-md-2', 'class' => 'w-100'],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ScheduledCommand::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
