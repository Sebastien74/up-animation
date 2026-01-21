<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * AdminNameType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AdminNameType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * AdminNameType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    /**
     * Generate AdminName Type.
     */
    public function add(FormBuilderInterface $builder, array $options = []): void
    {
        $isNew = $builder->getData() ? !$builder->getData()->getId() : null;
        $haveSlug = isset($options['slug-internal']) && $options['slug-internal']
            || isset($options['slug']) && $options['slug']
            || !empty($options['slug-force']);
        $referSlugClass = ' refer-code admin-name';
        $constraints = !empty($options['constraints']) && is_array($options['constraints'])
            ? $options['constraints'] : [new Assert\NotBlank()];

        $adminNameGroup = !empty($options['adminNameGroup']) ? $options['adminNameGroup'] : 'col-12';
        if (empty($options['adminNameGroup']) && str_contains($referSlugClass, 'refer-code')) {
            $adminNameGroup = 'col-sm-9';
        }

        $fieldType = !empty($options['fieldType']) ? $options['fieldType'] : TextType::class;
        $builder->add('adminName', $fieldType, [
            'label' => !empty($options['label']) || isset($options['label'])
                ? $options['label']
                : $this->translator->trans('Intitulé', [], 'admin'),
            'attr' => [
                'placeholder' => !empty($options['placeholder'])
                    ? $options['placeholder']
                    : $this->translator->trans('Saisissez un intitulé', [], 'admin'),
                'class' => isset($options['class'])
                    ? $options['class'].$referSlugClass
                    : $referSlugClass,
                'data-disabled-help' => $options['disabled_help'] ?? false,
                'data-height' => !empty($options['data_height']) ? $options['data_height'] : 250,
                'data-config' => !empty($options['data_config']) ? $options['data_config'] : false,
                'data-hide-not-new' => !empty($options['hide_not_new']) ? $options['hide_not_new'] : false,
            ],
            'row_attr' => ['class' => $adminNameGroup.' admin-name-group'],
            'translation_domain' => 'admin',
            'constraints' => $constraints,
        ]);

        $slugGroup = isset($options['slugGroup']) ? $options['slugGroup'] : 'col-sm-3';
        if (!$isNew && $haveSlug || !empty($options['slug-force'])) {
            $builder->add('slug', TextType::class, [
                'label' => $this->translator->trans('Code', [], 'admin'),
                'attr' => [
                    'code' => true,
                    'placeholder' => 'Saisissez un code',
                ],
                'row_attr' => ['class' => $slugGroup],
                'constraints' => [new Assert\NotBlank()],
            ]);
        }
    }
}
