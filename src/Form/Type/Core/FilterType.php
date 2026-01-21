<?php

declare(strict_types=1);

namespace App\Form\Type\Core;

use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FilterType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FilterType extends AbstractType
{
    private const array FIELD_TYPES = [
        'text' => Filters\TextFilterType::class,
        'string' => Filters\TextFilterType::class,
        'datetime' => Filters\DateFilterType::class,
        'integer' => Filters\NumberFilterType::class,
        'boolean' => Filters\BooleanFilterType::class,
    ];

    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;

    /**
     * FilterType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
        $this->entityManager = $this->coreLocator->em();
    }

    /**
     * @throws MappingException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $configuration = !empty($options['interface']['configuration']) ? $options['interface']['configuration'] : null;
        $filterName = $options['filterName'];
        $filterFields = $configuration && $configuration->$filterName ? $configuration->$filterName : [];
        $metadata = $this->entityManager->getClassMetadata($options['interface']['classname']);
        $transDomain = 'entity_'.$options['interface']['name'];
        $referEntity = new $options['interface']['classname']();

        $fieldsCount = 0;
        foreach ($filterFields as $filterField) {
            $existing = method_exists($referEntity, 'get'.ucfirst($filterField)) || method_exists($referEntity, 'is'.ucfirst($filterField));
            if ($existing) {
                $mapping = $metadata->getFieldMapping($filterField);
                if (!empty(self::FIELD_TYPES[$mapping['type']])) {
                    ++$fieldsCount;
                }
            }
        }

        $groupClass = $this->getGroupClass($fieldsCount);

        foreach ($filterFields as $filterField) {
            $existing = method_exists($referEntity, 'get'.ucfirst($filterField)) || method_exists($referEntity, 'is'.ucfirst($filterField));
            if ($existing) {
                $mapping = $metadata->getFieldMapping($filterField);
                if (!empty(self::FIELD_TYPES[$mapping['type']])) {
                    $labelTranslation = $this->translator->trans($filterField, [], $transDomain);
                    $label = $labelTranslation && $labelTranslation !== $filterField ? $labelTranslation : ucfirst($filterField);
                    $arguments = [
                        'label' => $label,
                        'attr' => [
                            'group' => $groupClass,
                            'placeholder' => $this->translator->trans('Saisissez votre recherche', [], 'admin'),
                        ],
                        'required' => false,
                    ];
                    if (Filters\BooleanFilterType::class === self::FIELD_TYPES[$mapping['type']]) {
                        $arguments['display'] = 'search';
                        $arguments['placeholder'] = $this->translator->trans('Sélectionnez', [], 'admin');
                    }
                    $builder->add($filterField, self::FIELD_TYPES[$mapping['type']], $arguments);
                }
            }
        }
    }

    /**
     * Get group class.
     */
    private function getGroupClass(int $fieldsCount): string
    {
        if ($fieldsCount >= 4) {
            return 'col-md-3';
        } elseif (3 === $fieldsCount) {
            return 'col-md-4';
        } elseif (2 === $fieldsCount) {
            return 'col-md-6';
        } else {
            return 'col-12';
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'admin',
            'interface' => [],
            'filterName' => [],
            'website' => null,
            'data_class' => null,
            'csrf_protection' => false,
        ]);
    }
}
