<?php

declare(strict_types=1);

namespace App\Form\Type\Core;

use App\Entity\Core\Entity;
use App\Entity\Core\Website;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * EntityType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class EntityType extends AbstractType
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;
    private ?Request $request;
    private Website $website;

    /**
     * EntityType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
        $this->entityManager = $this->coreLocator->em();
        $this->request = $coreLocator->request();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();
        $data = $builder->getData();
        $properties = $this->getProperties($data);
        $this->website = $options['website'];

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, ['adminNameGroup' => 'col-md-4']);

        $builder->add('className', Type\ChoiceType::class, [
            'label' => $this->translator->trans('Classe', [], 'admin'),
            'display' => 'search',
            'choices' => $this->getNamespaces(),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'attr' => ['group' => 'col-md-4'],
        ]);

        if (!$isNew) {
            $builder->add('entityId', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Filtre', [], 'admin'),
                'required' => false,
                'display' => 'search',
                'choices' => $this->getFilters($data),
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'attr' => ['group' => 'col-md-4'],
            ]);

            $builder->add('card', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Type fiche', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
            ]);

            $builder->add('mediaMulti', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Multi médias', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
            ]);

            $builder->add('uniqueLocale', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Langue unique', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
            ]);

            $builder->add('inFieldConfiguration', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Ajouter au sélecteur du module formulaire', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
            ]);

            $builder->add('orderBy', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Ordonner par', [], 'admin'),
                'display' => 'search',
                'choices' => $properties,
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'attr' => ['group' => 'col-md-4'],
            ]);

            $builder->add('orderSort', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Trier par ordre', [], 'admin'),
                'display' => 'search',
                'choices' => [
                    $this->translator->trans('Croissant', [], 'admin') => 'ASC',
                    $this->translator->trans('Décroissant', [], 'admin') => 'DESC',
                ],
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'attr' => ['group' => 'col-md-4'],
            ]);

            $builder->add('adminLimit', Type\IntegerType::class, [
                'label' => $this->translator->trans('Admin limite', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez une limite', [], 'admin'),
                    'group' => 'col-md-4',
                ],
            ]);

            $builder->add('columns', Type\ChoiceType::class, [
                'label' => $this->translator->trans("Colonnes de l'index", [], 'admin'),
                'attr' => [
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                ],
                'display' => 'search',
                'multiple' => true,
                'required' => false,
                'choices' => $properties,
            ]);

            $builder->add('searchFields', Type\ChoiceType::class, [
                'label' => $this->translator->trans("Colonnes à rechercher dans l'index", [], 'admin'),
                'attr' => [
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                ],
                'display' => 'search',
                'multiple' => true,
                'required' => false,
                'choices' => $properties,
                'help' => $this->translator->trans('Moteur de recherche administration', [], 'admin'),
            ]);

            $builder->add('searchFilters', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Colonnes à rechercher par filtres', [], 'admin'),
                'attr' => [
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                ],
                'display' => 'search',
                'multiple' => true,
                'required' => false,
                'choices' => $properties,
                'help' => $this->translator->trans('Formulaire de recherche des index (modal)', [], 'admin'),
            ]);

            $builder->add('exports', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Colonnes à Exporter', [], 'admin'),
                'attr' => [
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                ],
                'multiple' => true,
                'required' => false,
                'display' => 'search',
                'choices' => $properties,
            ]);

            $builder->add('saveArea', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Affichage des boutons', [], 'admin'),
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'required' => false,
                'display' => 'search',
                'choices' => [
                    $this->translator->trans('En haut', [], 'admin') => 'top',
                    $this->translator->trans('En bas', [], 'admin') => 'bottom',
                    $this->translator->trans('Les deux', [], 'admin') => 'both',
                ],
            ]);

            $builder->add('showView', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Afficher dans la visualisation', [], 'admin'),
                'attr' => [
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                ],
                'display' => 'search',
                'multiple' => true,
                'required' => false,
                'choices' => $properties,
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
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
                $namespaces[$this->translator->trans($namespace, [], 'entity')] = $namespace;
            }
        }

        return $namespaces;
    }

    /**
     * Get entities to filter.
     */
    private function getFilters(Entity $configuration): array
    {
        $choices = [];
        $className = $configuration->getClassName();
        $defaultLocale = $this->request->get('locale');

        if (!empty($className) && class_exists($configuration->getClassName())) {
            $repository = $this->entityManager->getRepository($configuration->getClassName());
            $entity = new $className();
            $masterField = method_exists($entity, 'getMasterfield') ? $entity::getMasterfield() : null;
            $masterFieldSetter = $masterField ? 'get'.ucfirst($masterField) : null;

            if (!$masterField) {
                $entities = $repository->findAll();
            } elseif ('website' === $masterField || method_exists($entity, 'getWebsite')) {
                $entities = $repository->findByWebsite($this->website);
            } elseif ($entity::getMasterfield() && is_object($entity->$masterFieldSetter()) && method_exists($entity->$masterFieldSetter(), 'getWebsite')) {
                $entities = $repository->createQueryBuilder('e')
                    ->join('e.'.$masterField, $masterField)
                    ->where($masterField.'.website = :website')
                    ->setParameter(':website', $this->website)
                    ->addSelect($masterField)
                    ->getQuery()
                    ->getResult();
            }

            if (!empty($entities)) {
                foreach ($entities as $entity) {
                    $title = method_exists($entity, 'getAdminName') ? $entity->getAdminName() : $entity->getId();
                    if (empty($title) && method_exists($entity, 'getIntls')) {
                        foreach ($entity->getIntls() as $intl) {
                            if ($intl->getLocale() === $defaultLocale) {
                                $title = $intl->getTitle();
                            }
                        }
                    }
                    if (empty($title)) {
                        $title = $this->translator->trans('ID :', [], 'admin').$entity->getId();
                    }
                    $choices[$title] = $entity->getId();
                }
            }
        }

        return $choices;
    }

    /**
     * Get properties.
     */
    private function getProperties(Entity $configuration): array
    {
        $excludes = ['entityConfiguration', 'createdBy', 'updatedBy', 'mediaRelations', 'icon'];
        $choices = ['infos' => 'infos'];
        $className = $configuration->getClassName();

        if (!empty($className) && class_exists($className)) {
            $fieldsMapping = $this->entityManager->getClassMetadata($configuration->getClassName())->getFieldNames();
            foreach ($fieldsMapping as $key => $field) {
                $choices[$field] = $field;
            }

            $associationsMapping = $this->entityManager->getClassMetadata($configuration->getClassName())->getAssociationNames();
            foreach ($associationsMapping as $field) {
                $associationClass = $this->entityManager->getClassMetadata($configuration->getClassName())->getAssociationTargetClass($field);
                $fieldsMapping = $this->entityManager->getClassMetadata($associationClass)->getFieldNames();
                $choices[$field] = $field;
                foreach ($fieldsMapping as $fieldMapping) {
                    if (!in_array($field, $excludes)) {
                        $choices[$field.'.'.$fieldMapping] = $field.'.'.$fieldMapping;
                    }
                    if ('mediaRelations' === $field) {
                        $choices['media'] = 'media';
                    }
                }
            }
        }

        if (!isset($choices['adminName'])) {
            $choices['adminName'] = 'adminName';
        }

        return $choices;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Entity::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
