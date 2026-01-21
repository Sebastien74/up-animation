<?php

declare(strict_types=1);

namespace App\Form\Type\Seo;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ImportRedirectionType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ImportRedirectionType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * ImportRedirectionType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('xls_file', Type\FileType::class, [
            'label' => false,
            'attr' => ['accept' => '.xlsx', 'placeholder' => $this->translator->trans('Votre fichier', [], 'admin')],
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\File(mimeTypes: ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']),
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
