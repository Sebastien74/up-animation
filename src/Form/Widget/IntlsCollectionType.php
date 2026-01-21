<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Form\EventListener\Translation\IntlsListener;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * IntlsCollectionType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class IntlsCollectionType extends AbstractType
{
    /**
     * IntlsCollectionType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * Add type.
     */
    public function add(FormBuilderInterface $builder, array $options = []): void
    {
        $disableTitle = !empty($options['disableTitle']) ? $options['disableTitle'] : false;
        if (!empty($options['disableTitle'])) {
            unset($options['disableTitle']);
        }

        $options['data_class'] = !empty($options['data_class']) ? $options['data_class']
            : $this->coreLocator->metadata($builder->getData(), 'intls')->targetEntity;

        if ($options['data_class'] || $builder->getData() && method_exists($builder->getData(), 'getIntls')) {
            $builder->add('intls', CollectionType::class, [
                'label' => false,
                'entry_type' => IntlType::class,
                'entry_options' => $options,
                'attr' => [
                    'data-config' => $options['data_config'] ?? null,
                    'disableTitle' => $disableTitle,
                ],
            ])->addEventSubscriber(new IntlsListener($this->coreLocator, $options));
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'admin',
            'website' => null,
            'disableTitle' => false,
            'data_config' => false,
        ]);
    }
}
