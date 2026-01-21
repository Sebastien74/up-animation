<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Newscast;

use App\Entity\Module\Newscast\Newscast;
use App\Form\Validator\File;
use App\Form\Widget\IntlsCollectionType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FrontType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FrontType extends AbstractType
{
    private const string MAX_SIZE = '200M';
    private const string ACCEPT = '.xlsx, .xls, image/*, .doc, .docx, .ppt, .pptx, .txt, .pdf, .webmanifest, .mp4, .m4v, .mov, .webm, .vtt';
    private const array MIME_TYPES = [
        'image/*',
    ];

    private TranslatorInterface $translator;

    /**
     * FrontType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $intls = new IntlsCollectionType($this->coreLocator);
        $intls->add($builder, [
            'website' => $options['website'],
            'target_config' => false,
            'required_fields' => ['title', 'body'],
            'constraints_fields' => ['targetLink' => [new Assert\Url()]],
            'placeholder_fields' => [
                'title' => false,
                'body' => false,
                'targetLink' => false,
            ],
            'label_fields' => [
                'title' => $this->translator->trans('Titre', [], 'front_form'),
                'body' => $this->translator->trans('Description', [], 'front_form'),
                'targetLink' => $this->translator->trans('Lien', [], 'front_form'),
            ],
            'fields' => [
                'title' => 'col-12',
                'body' => 'col-12',
                'targetLink' => 'col-12',
            ],
        ]);

        $builder->add('author', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Auteur', [], 'front_form'),
        ]);

        $builder->add('uploadedFile', FileType::class, [
            'label' => $this->translator->trans('Image', [], 'front_form'),
            'mapped' => false,
            'multiple' => false,
            'required' => false,
            'attr' => [
                'onlyMedia' => true,
                'accept' => self::ACCEPT,
                'data-max-size' => self::MAX_SIZE,
            ],
            'constraints' => [
                new File(maxSize: self::MAX_SIZE, mimeTypes: self::MIME_TYPES),
            ],
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => $this->translator->trans('Soumettre', [], 'front_form'),
            'attr' => [
                'group' => 'col-lg-12',
                'class' => 'btn btn-primary text-uppercase mt-3',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Newscast::class,
            'website' => null,
            'translation_domain' => 'front_form',
        ]);
    }
}
