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

            'row_attr' => ['class' => $isNew ? 'col-md-6' : 'col-md-4'],
        ]);

        $builder->add('cronExpression', Type\TextType::class, [
            'label' => $this->translator->trans('Expression cron', [], 'admin'),
            'attr' => ['placeholder' => $this->translator->trans('*/10 * * * *', [], 'admin')],
            'row_attr' => ['class' => $isNew ? 'col-md-6' : 'col-md-4'],
            'help' => '<a href="http://www.abunchofutils.com/utils/developer/cron-expression-helper/" target="_blank">'.$this->translator->trans('Générer', [], 'admin').'</a>',
        ]);

        $builder->add('description', Type\TextType::class, [
            'label' => $this->translator->trans('Description', [], 'admin'),
            'attr' => ['placeholder' => $this->translator->trans('Saisissez une description*', [], 'admin')],
            'row_attr' => ['class' => $isNew ? 'col-md-6' : 'col-md-9'],
        ]);

        if (!$isNew) {
            $builder->add('logFile', Type\TextType::class, [
                'required' => false,
                'label' => $this->translator->trans('Nom du fichier de log', [], 'admin'),
                'attr' => ['placeholder' => $this->translator->trans('Saisissez un nom', [],
                    'admin')
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            ]);

            $builder->add('executeImmediately', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'app',
                'label' => $this->translator->trans('Exécuter maintenant', [], 'admin'),
                'attr' => ['class' => 'col-12 w-100'],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            ]);

            $builder->add('active', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'app',
                'label' => $this->translator->trans('Activer', [], 'admin'),
                'attr' => ['class' => 'col-12 w-100'],
                'row_attr' => ['class' => 'col-12 col-md-8 col-lg-2'],
            ]);

            $builder->add('locked', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'app',
                'label' => $this->translator->trans('Bloquée suite erreur', [], 'admin'),
                'attr' => ['class' => 'col-12 w-100'],
                'row_attr' => ['class' => 'col-12 col-md-8 col-lg-2'],
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
