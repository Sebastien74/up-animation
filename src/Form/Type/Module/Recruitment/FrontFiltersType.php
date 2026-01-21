<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Recruitment;

use App\Entity\Core\Website;
use App\Entity\Module\Recruitment\Category;
use App\Entity\Module\Recruitment\Contract;
use App\Entity\Module\Recruitment\Job;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FrontFiltersType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FrontFiltersType extends AbstractType
{
    private const bool ACTIVE_DOMAIN = true;
    private const bool ACTIVE_LOCALITY = true;
    private TranslatorInterface $translator;
    private ?Website $website;
    private array $domainIds = [];
    private array $contractIds = [];
    private array $places = [];

    /**
     * FrontFiltersType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    /**
     * @throws \Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->website = $this->coreLocator->website()->entity;
        $entities = $this->coreLocator->em()->getRepository(Job::class)->findOnlineByWebsiteAndLocale($this->website, $this->coreLocator->locale());

        $this->domainIds = [];
        $this->contractIds = [];
        $this->places = [];
        foreach ($entities as $entity) {
            if ($entity->getCategory() instanceof Category) {
                $this->domainIds[] = $entity->getCategory()->getId();
            }
            if ($entity->getContract() instanceof Contract) {
                $this->contractIds[] = $entity->getContract()->getId();
            }
            if ($entity->getPlace() && empty($this->places[$entity->getPlace()])) {
                $this->places[$entity->getPlace()] = $entity->getPlace();
            }
        }

        if (self::ACTIVE_LOCALITY && !empty($this->places)) {
            $builder->add('place', ChoiceType::class, [
                'label' => false,
                'required' => false,
                'choices' => $this->places,
                'display' => 'search',
                'placeholder' => $this->translator->trans('Lieux', [], 'front_form'),
                'attr' => [
                    'data-floating' => false,
                    'reset-btn' => true,
                ],
            ]);
        }

        $zipcode = $this->getZipCodes($entities);
        if (!empty($zipcode)) {
            $builder->add('zipcode', ChoiceType::class, [
                'label' => false,
                'required' => false,
                'choices' => $zipcode,
                'display' => 'search',
                'placeholder' => $this->translator->trans('Code postal', [], 'front_form'),
                'attr' => [
                    'data-floating' => false,
                    'reset-btn' => true,
                ],
            ]);
        }

        if (self::ACTIVE_DOMAIN && !empty($this->domainIds)) {
            $builder->add('domain', EntityType::class, [
                'label' => false,
                'required' => false,
                'class' => Category::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->andWhere('c.website = :website')
                        ->andWhere('c.id IN (:ids)')
                        ->setParameter('website', $this->website)
                        ->setParameter('ids', $this->domainIds)
                        ->orderBy('c.adminName', 'ASC');
                },
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getAdminName());
                },
                'placeholder' => $this->translator->trans('Domaines', [], 'front_form'),
                'display' => 'search',
                'attr' => [
                    'data-floating' => false,
                    'reset-btn' => true,
                ],
            ]);
        }

        $zipcode = $this->getZipCodes($entities);
        if (!empty($zipcode)) {
            $builder->add('zipcode', ChoiceType::class, [
                'label' => false,
                'required' => false,
                'choices' => $zipcode,
                'display' => 'search',
                'placeholder' => $this->translator->trans('Code postal', [], 'front_form'),
                'attr' => [
                    'data-floating' => false,
                    'reset-btn' => true,
                ],
            ]);
        }

        if (!empty($this->contractIds)) {
            $builder->add('contract', EntityType::class, [
                'label' => false,
                'required' => false,
                'class' => Contract::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->andWhere('c.website = :website')
                        ->andWhere('c.id IN (:ids)')
                        ->setParameter('website', $this->website)
                        ->setParameter('ids', $this->contractIds)
                        ->orderBy('c.adminName', 'ASC');
                },
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getAdminName());
                },
                'display' => 'search',
                'placeholder' => $this->translator->trans('Type de contrat', [], 'front_form'),
                'attr' => [
                    'data-floating' => false,
                    'reset-btn' => true,
                ],
            ]);
        }
    }

    /**
     * To get zipCodes.
     */
    public function getZipCodes(array $jobs = []): array
    {
        $codes = [];
        foreach ($jobs as $job) {
            if (!isset($codes[$job->getZipCode()]) && $job->getZipCode()) {
                $codes[$job->getZipCode()] = $job->getZipCode();
            }
        }

        return $codes;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'website' => null,
            'listing' => null,
            'teaser' => null,
            'arguments' => [],
            'csrf_protection' => false,
            'translation_domain' => 'front_form',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}