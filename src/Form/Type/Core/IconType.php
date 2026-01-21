<?php

declare(strict_types=1);

namespace App\Form\Type\Core;

use App\Form\Validator\File;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * IconType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class IconType extends AbstractType
{
    private const string MAX_SIZE = '200M';
    private const string ACCEPT = '.svg';
    private const array MIME_TYPES = ['image/svg+xml'];
    private TranslatorInterface $translator;


    /**
     * IconType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('uploadedFile', FileType::class, [
            'label' => false,
            'mapped' => false,
            'multiple' => true,
            'required' => false,
            'attr' => [
                'accept' => self::ACCEPT,
                'data-max-size' => self::MAX_SIZE,
                'placeholder' => $this->translator->trans('Séléctionnez une image', [], 'admin'),
                'class' => 'dropzone-field',
                'group' => 'd-none',
                'data-height' => 250,
            ],
            'constraints' => [
                new File(maxSize: self::MAX_SIZE, mimeTypes: self::MIME_TYPES),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
