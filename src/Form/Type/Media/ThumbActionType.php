<?php

declare(strict_types=1);

namespace App\Form\Type\Media;

use App\Entity\Layout\BlockType;
use App\Entity\Media\ThumbAction;
use App\Entity\Module\Search\Search;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ThumbActionType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ThumbActionType extends AbstractType
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;
    private string $projectDir;

    /**
     * ThumbActionType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
        $this->entityManager = $this->coreLocator->em();
        $this->projectDir = $this->coreLocator->projectDir();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('adminName', Type\TextType::class, [
            'label' => $this->translator->trans('Intitulé', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un intitulé', [], 'admin'),
                'group' => 'col-md-8',
            ],
        ]);

        $builder->add('namespace', Type\ChoiceType::class, [
            'label' => $this->translator->trans('Namespace', [], 'admin'),
            'choices' => $this->getNamespaces(),
            'display' => 'search',
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'attr' => [
                'group' => 'col-md-4',
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('action', Type\ChoiceType::class, [
            'label' => $this->translator->trans('Action', [], 'admin'),
            'required' => false,
            'display' => 'search',
            'choices' => $this->getActions(),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'attr' => [
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('actionFilter', Type\TextType::class, [
            'label' => $this->translator->trans('Filtre (id ou slug)', [], 'admin'),
            'required' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un id', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('blockType', EntityType::class, [
            'label' => $this->translator->trans('Type de bloc', [], 'admin'),
            'required' => false,
            'display' => 'search',
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'attr' => ['group' => 'col-md-4'],
            'class' => BlockType::class,
            'query_builder' => function (EntityRepository $er) {
                $slugs = ['title-header', 'modal', 'media', 'card', 'video'];
                $statement = $er->createQueryBuilder('b');
                foreach ($slugs as $key => $slug) {
                    $condition = 0 === $key ? 'andWhere' : 'orWhere';
                    $statement->$condition('b.slug = :slug'.$key)
                        ->setParameter('slug'.$key, $slug);
                }

                return $statement->orderBy('b.adminName', 'ASC');
            },
            'choice_label' => function ($entity) {
                return strip_tags($entity->getAdminName());
            },
        ]);
    }

    /**
     * Get namespaces.
     */
    private function getNamespaces(): array
    {
        $namespaces = [];
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        foreach ($metadata as $data) {
            $namespace = $data->getName();
            if (0 === $data->getReflectionClass()->getModifiers()) {
                $entity = new $namespace();
                if (method_exists($entity, 'getMediaRelations') || str_contains($namespace, 'Listing') || Search::class === $namespace) {
                    $namespaces[$this->translator->trans($namespace, [], 'entity')] = $namespace;
                }
            }
        }

        return $namespaces;
    }

    /**
     * Get actions.
     */
    private function getActions(): array
    {
        $options = [];
        $excludes = ['getSubscribedServices', '__construct', 'setContainer'];
        $projectDir = $this->projectDir;
        $frontDirCore = $projectDir.'/src/Controller/Front/Action';
        $finderControllers = new Finder();
        $finderControllers->files()->in($frontDirCore);
        foreach ($finderControllers as $file) {
            $relativeName = $file->getRelativePathname();
            $controllerName = str_replace('.php', '', $relativeName);
            $methods = get_class_methods('\App\\Controller\Front\\Action\\'.$controllerName);
            foreach ($methods as $method) {
                if (!in_array($method, $excludes)) {
                    $options[$method] = $method;
                }
            }
        }

        return $options;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ThumbAction::class,
            'legend' => $this->translator->trans('Actions', [], 'admin'),
            'button' => $this->translator->trans('Ajouter une action', [], 'admin'),
            'translation_domain' => 'admin',
            'website' => null,
        ]);
    }
}
