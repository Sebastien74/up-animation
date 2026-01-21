<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Form\EventListener\Seo\UrlListener;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * UrlsCollectionType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UrlsCollectionType extends AbstractType
{
    /**
     * UrlsCollectionType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * Add fields.
     */
    public function add(FormBuilderInterface $builder, array $options = []): void
    {
        $disableTitle = !empty($options['disableTitle']) ? $options['disableTitle'] : false;
        if (!empty($options['disableTitle'])) {
            unset($options['disableTitle']);
        }

        $builder->add('urls', CollectionType::class, [
            'label' => false,
            'entry_type' => UrlType::class,
            'entry_options' => ['display_seo' => !empty($options['display_seo']) ? $options['display_seo'] : false],
            'attr' => ['disableTitle' => $disableTitle],
        ])->addEventSubscriber(new UrlListener($this->coreLocator));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'admin',
            'website' => null,
            'display_seo' => false,
            'disableTitle' => false,
        ]);
    }
}
