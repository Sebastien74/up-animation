<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Table;

use App\Entity\Module\Table\Cell;
use App\Entity\Module\Table\CellIntl;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * CellType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CellType extends AbstractType
{
    /**
     * CellType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
        $intls->add($builder, [
            'label' => false,
            'fields' => ['title', 'introduction', 'body'],
            'fields_type' => ['introduction' => TextType::class],
            'title_force' => true,
            'data_class' => CellIntl::class,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cell::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
