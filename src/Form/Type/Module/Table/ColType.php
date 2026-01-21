<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Table;

use App\Entity\Module\Table\Col;
use App\Entity\Module\Table\ColIntl;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * ColType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ColType extends AbstractType
{
    /**
     * ColType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
        $intls->add($builder, [
            'label' => false,
            'fields' => ['title'],
            'label_fields' => ['title' => false],
            'title_force' => true,
            'data_class' => ColIntl::class,
        ]);

        $builder->add('cells', CollectionType::class, [
            'label' => false,
            'entry_type' => CellType::class,
            'entry_options' => ['website' => $options['website']],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Col::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
