<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Tab;

use App\Entity\Module\Tab\Content;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ContentType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ContentType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * ContentType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'data_config' => true,
            'adminNameGroup' => $isNew ? 'col-12' : 'col-md-9',
        ]);

        if (!$isNew) {

            $mediaRelations = new WidgetType\MediaRelationsCollectionType($this->coreLocator);
            $mediaRelations->add($builder, ['data_config' => true]);

            $builder->add('pictogram', WidgetType\PictogramType::class);

            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'fields' => [
                    'title',
                    'subTitle' => 'col-12',
                    'introduction',
                    'body',
                    'targetLink' => 'col-md-8',
                    'targetPage' => 'col-md-4',
                    'targetLabel' => 'col-md-8',
                    'targetStyle' => 'col-md-4',
                    'newTab' => 'col-md-6',
                    'externalLink' => 'col-md-6',
                ],
                'target_config' => true,
                'label_fields' => [
                    'title' => $this->translator->trans("Intitulé de l'onglet", [], 'admin'),
                    'subTitle' => $this->translator->trans("Titre", [], 'admin'),
                ],
            ]);

            $builder->add('largeBullets', CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Liste à puces checkbox', [], 'admin'),
                'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100 mb-0'],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder, ['btn_both' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Content::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
