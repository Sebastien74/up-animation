<?php

declare(strict_types=1);

namespace App\Form\Type\Seo;

use App\Entity\Core\Configuration;
use App\Entity\Seo\NotFoundUrl;
use App\Entity\Seo\Redirection;
use App\Form\Validator\UniqOldRedirection;
use App\Form\Widget\SubmitType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Languages;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * RedirectionType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class RedirectionType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * RedirectionType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var NotFoundUrl $notFound */
        $notFound = $options['not_found_url'];
        /** @var Configuration $configuration */
        $configuration = $options['website']->getConfiguration();
        $locales = $this->getLocales($configuration);
        $multiLocales = count($locales) > 1;

        if ($multiLocales) {
            $builder->add('locale', Type\ChoiceType::class, [
                'label' => $options['labels'] ? $this->translator->trans('Langue', [], 'admin') : false,
                'choices' => $locales,
                'choice_translation_domain' => false,
                'attr' => ['class' => 'select-icons', 'group' => $options['groups'] ?: 'col-md-3'],
                'choice_attr' => function ($iso, $key, $value) {
                    return [
                        'data-image' => '/medias/icons/flags/'.strtolower($iso).'.svg',
                        'data-class' => 'flag mt-min',
                        'data-text' => true,
                        'data-height' => 14,
                        'data-width' => 19,
                    ];
                },
                'constraints' => [new Assert\NotBlank()],
            ]);
        } else {
            $builder->add('locale', Type\HiddenType::class, [
                'data' => $configuration->getLocale(),
                'attr' => ['class' => 'form-control'],
            ]);
        }

        $oldArguments = [
            'label' => $options['labels'] ? $this->translator->trans('Ancienne URI / URL', [], 'admin') : false,
            'attr' => [
                'group' => $options['groups'] ?: ($multiLocales ? 'col-md-4' : 'col-md-6'),
                'placeholder' => $this->translator->trans('Saisissez une URI', [], 'admin'),
            ],
            'constraints' => [
                new Assert\NotBlank(),
                new UniqOldRedirection(),
            ],
        ];

        if ($notFound) {
            $oldArguments['data'] = $notFound->getUri();
        }

        $builder->add('old', Type\TextType::class, $oldArguments);

        $builder->add('new', Type\TextType::class, [
            'label' => $options['labels'] ? $this->translator->trans('Nouvelle URL', [], 'admin') : false,
            'attr' => [
                'group' => $options['groups'] ?: ($multiLocales ? 'col-md-4' : 'col-md-6'),
                'placeholder' => $this->translator->trans('Saisissez une nouvelle', [], 'admin'),
            ],
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Url(),
            ],
        ]);

        if ($notFound) {
            $save = new SubmitType($this->coreLocator);
            $save->add($builder, [
                'only_save' => true,
                'as_ajax' => true,
                'class' => 'btn-info close-modal refresh standard',
            ]);
        }
    }

    /**
     * Get WebsiteModel locales.
     */
    private function getLocales(Configuration $configuration): array
    {
        $defaultLocale = $configuration->getLocale();
        $name = empty($locales[Languages::getName($defaultLocale)]) ? Languages::getName($defaultLocale) : Languages::getName($defaultLocale).' ('.strtoupper($defaultLocale).')';
        $locales[$name] = $defaultLocale;
        foreach ($configuration->getLocales() as $locale) {
            $name = empty($locales[Languages::getName($locale)]) ? Languages::getName($locale) : Languages::getName($locale).' ('.strtoupper($locale).')';
            $locales[$name] = $locale;
        }

        return $locales;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Redirection::class,
            'website' => null,
            'not_found_url' => null,
            'labels' => true,
            'groups' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
