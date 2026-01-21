<?php

declare(strict_types=1);

namespace App\Form\Type\Gdpr;

use App\Entity\Gdpr\Category;
use App\Form\Widget\AdminNameType;
use App\Form\Widget\SubmitType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * CategoryType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CategoryType extends AbstractType
{
    /**
     * CategoryType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $adminName = new AdminNameType($this->coreLocator);
        $adminName->add($builder, ['slug' => true]);

        $save = new SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
