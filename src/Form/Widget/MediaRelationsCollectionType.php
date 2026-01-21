<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Form\EventListener\Media\MediaRelationListener;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * MediaRelationsCollectionType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MediaRelationsCollectionType extends AbstractType
{
    private EntityManagerInterface $entityManager;

    /**
     * ActionService constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->entityManager = $this->coreLocator->em();
    }

    /**
     * Add fields.
     */
    public function add(FormBuilderInterface $builder, array $options = []): void
    {
        $entryOptions = !empty($options['entry_options']) ? $options['entry_options'] : [];
        $entryOptions['data_class'] = !empty($entryOptions['data_class']) ? $entryOptions['data_class']
            : $this->coreLocator->metadata($builder->getData(), 'mediaRelations')->targetEntity;
        $builder->add('mediaRelations', CollectionType::class, [
            'label' => false,
            'entry_type' => MediaRelationType::class,
            'entry_options' => $entryOptions,
            'attr' => [
                'data-config' => !empty($options['data_config']) ? $options['data_config'] : null,
                'disable-multiple' => isset($entryOptions['onlyOne']) ?? null,
            ],
        ])->addEventSubscriber(new MediaRelationListener($this->coreLocator, ['entityManager' => $this->entityManager]));
    }
}
