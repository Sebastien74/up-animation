<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Faq;

use App\Entity\Module\Faq\Question;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * QuestionType.
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
class QuestionType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * QuestionType constructor.
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
            'adminNameGroup' => 'col-12'
        ]);

        if (!$isNew) {

            $mediaRelations = new WidgetType\MediaRelationsCollectionType($this->coreLocator);
            $mediaRelations->add($builder, [
                'data_config' => true,
                'entry_options' => ['onlyMedia' => true],
            ]);

            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'fields' => ['title' => 'col-lg-6', 'subTitle' => 'col-lg-6', 'introduction', 'body'],
                'label_fields' => [
                    'title' => $this->translator->trans('Question', [], 'admin'),
                    'body' => $this->translator->trans('Réponse', [], 'admin'),
                ],
                'fields_data' => ['titleForce' => 3],
            ]);

            $builder->add('pictogram', WidgetType\PictogramType::class, [
                'attr' => ['group' => 'col-12'],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder, ['btn_both' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Question::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
