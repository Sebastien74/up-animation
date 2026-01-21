<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Search;

use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use App\Entity\Module\Search\Search;
use App\Form\Widget as WidgetType;
use App\Repository\Layout\PageRepository;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * SearchType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SearchType extends AbstractType
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;
    private bool $isInternalUser;
    private Website $website;

    /**
     * SearchType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $this->translator = $this->coreLocator->translator();
        $this->entityManager = $this->coreLocator->em();
        $user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isInternalUser = $user && in_array('ROLE_INTERNAL', $user->getRoles());
    }

    /**
     * @throws NonUniqueResultException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $data = $builder->getData();
        $isNew = !$data->getId();
        $this->website = $options['website'];

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'adminNameGroup' => 'col-sm-6',
            'slug-internal' => $this->isInternalUser,
        ]);

        if (!$isNew) {
            $builder->add('resultsPage', EntityType::class, [
                'required' => false,
                'display' => 'search',
                'label' => $this->translator->trans('Page de resultats', [], 'admin'),
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'class' => Page::class,
                'query_builder' => function (PageRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->andWhere('p.website = :website')
                        ->setParameter('website', $this->website);
                },
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getAdminName());
                },
                'attr' => ['group' => 'col-md-3'],
            ]);

            $builder->add('itemsPerPage', Type\IntegerType::class, [
                'label' => $this->translator->trans('Nombre de résultats par page', [], 'admin'),
                'attr' => ['group' => 'col-md-3'],
            ]);

            $builder->add('orderBy', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Ordonner par', [], 'admin'),
                'display' => 'search',
                'attr' => ['group' => 'col-md-3'],
                'choices' => [
                    $this->translator->trans('Pertinence', [], 'admin') => 'score',
                    $this->translator->trans('Dates (croissantes)', [], 'admin') => 'date-asc',
                    $this->translator->trans('Dates (décroissantes)', [], 'admin') => 'date-desc',
                ],
            ]);

            $builder->add('searchType', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Type de recherche', [], 'admin'),
                'display' => 'search',
                'attr' => ['group' => 'col-md-3'],
                'choices' => [
                    $this->translator->trans('Phrase saisie', [], 'admin') => 'sentence',
                    $this->translator->trans('Tous les mots', [], 'admin') => 'words',
                ],
            ]);

            $builder->add('mode', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Mode de recherche', [], 'admin'),
                'display' => 'search',
                'attr' => ['group' => 'col-md-3'],
                'choices' => [
                    $this->translator->trans('Boolean', [], 'admin') => 'boolean',
                    $this->translator->trans('Language', [], 'admin') => 'language',
                ],
            ]);

            $builder->add('entities', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Entité(s) recherchée(s)', [], 'admin'),
                'multiple' => true,
                'display' => 'search',
                'choices' => $this->getIntlsEntities(),
                'attr' => [
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                ],
            ]);

            $builder->add('filterGroup', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Afficher les résulats par groupes', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
            ]);

            $builder->add('modal', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Afficher une modal', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
            ]);

            $builder->add('registerSearch', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Enregistrer les recherches', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
            ]);

            $builder->add('scrollInfinite', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Scroll infinite', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
            ]);

            $builder->add('counter', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Activer le compteur de résultats', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    /**
     * Get intl entities relations.
     */
    private function getIntlsEntities(): array
    {
        $entities = [];
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        foreach ($metadata as $data) {
            if (0 === $data->getReflectionClass()->getModifiers()) {
                $classname = $data->getName();
                $entity = new $classname();
                $interface = method_exists($entity, 'getInterface') ? $entity::getInterface() : [];
                $asIntl = method_exists($entity, 'getIntls') || method_exists($entity, 'getIntl');
                $inSearch = !empty($interface['search']) && true === $interface['search'];
                if ($asIntl && $inSearch) {
                    $interfaceName = method_exists($entity, 'getInterface') && !empty($entity::getInterface()['name'])
                        ? $entity::getInterface()['name'] : null;
                    $translation = $interfaceName
                        ? $this->translator->trans('singular', [], 'entity_'.$entity::getInterface()['name'])
                        : $classname;
                    $label = $interfaceName && 'singular' !== $translation ? $translation : $classname;
                    $entities[$label] = $classname;
                }
            }
        }
        $entities['PDF'] = 'pdf';

        return $entities;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Search::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
