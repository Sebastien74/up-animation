<?php

declare(strict_types=1);

namespace App\Form\Type\Core\Website;

use App\Entity\Core\Website;
use App\Form\Widget\AdminNameType;
use App\Form\Widget\SubmitType;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * WebsiteType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class WebsiteType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * WebsiteType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();

        $adminName = new AdminNameType($this->coreLocator);
        $adminName->add($builder);

        $builder->add('configuration', ConfigurationType::class, [
            'label' => false,
            'isNew' => $isNew,
            'website_edit' => $builder->getData(),
            'website' => $options['website'],
        ]);

        if (!$isNew) {
            $builder->add('api', ApiType::class, [
                'label' => false,
                'website' => $options['website'],
            ]);

            $builder->add('security', SecurityType::class, [
                'label' => false,
                'website' => $options['website'],
            ]);
        } else {
            $builder->add('yaml_config', ChoiceType::class, [
                'label' => $this->translator->trans('Fichier de configuration', [], 'admin'),
                'mapped' => false,
                'display' => 'search',
                'attr' => [
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                    'group' => 'col-md-3',
                ],
                'choices' => $this->getConfigFiles(),
                'constraints' => [new Assert\NotBlank()],
            ]);

            $builder->add('website_to_duplicate', EntityType::class, [
                'mapped' => false,
                'required' => false,
                'label' => $this->translator->trans('Site à dupliquer', [], 'admin'),
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'display' => 'search',
                'class' => Website::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('w')
                        ->orderBy('w.id', 'ASC');
                },
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getAdminName());
                },
                'attr' => ['group' => 'col-md-4 mx-auto'],
            ]);
        }

        $save = new SubmitType($this->coreLocator);
        $save->add($builder);
    }

    /**
     * Get yaml configuration files.
     */
    private function getConfigFiles(): array
    {
        $configs = [];
        $configDirname = $this->coreLocator->projectDir().'/bin/data/config';
        $configDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $configDirname);
        $finder = Finder::create();
        $finder->in($configDirname)->name('*.yaml');
        foreach ($finder as $file) {
            if (!$file->isDir()) {
                $filename = str_replace('.yaml', '', $file->getFilename());
                $configs[$filename.'.yaml'] = strtolower($filename);
            }
        }
        ksort($configs);

        return $configs;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Website::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
