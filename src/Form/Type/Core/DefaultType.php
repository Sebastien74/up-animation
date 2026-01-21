<?php

declare(strict_types=1);

namespace App\Form\Type\Core;

use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * DefaultType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class DefaultType extends AbstractType
{
    private EntityManagerInterface $entityManager;

    /**
     * DefaultType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->entityManager = $this->coreLocator->em();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $excludes = ['id'];
        $properties = $this->entityManager->getClassMetadata($options['data_class'])->getReflectionProperties();
        $associationMappings = $this->entityManager->getClassMetadata($options['data_class'])->getAssociationMappings();

        foreach ($properties as $property => $reflexionProperty) {
            if (empty($associationMappings[$property]) && !in_array($property, $excludes)) {
                $builder->add($property, null, [
                    'label' => ucfirst($property),
                ]);
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
