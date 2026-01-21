<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Block;

use App\Entity\Core\Website;
use App\Entity\Information\Information;
use App\Entity\Layout\ActionIntl;
use App\Entity\Layout\Block;
use App\Service\Core\InterfaceHelper;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ActionIntlType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ActionIntlType extends AbstractType
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;
    private Website $website;
    private mixed $entity;

    /**
     * ActionIntlType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly InterfaceHelper $interfaceHelper,
    ) {
        $this->translator = $this->coreLocator->translator();
        $this->entityManager = $this->coreLocator->em();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Block $block */
        $block = $options['form_data'];
        $className = $block->getAction()->getEntity();

        if ($className) {
            $this->website = $options['website'];
            $this->entity = new $className();
            $this->interfaceHelper->setInterface($className);

            $builder->add('actionFilter', ChoiceType::class, [
                'required' => false,
                'display' => 'search',
                'attr' => ['displayTemplates' => $options['displayTemplates']],
                'label' => $this->translator->trans('Filtre', [], 'admin'),
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'choices' => $this->getChoices($className),
            ]);
        }
    }

    /**
     * Get choices values.
     */
    private function getChoices(string $className): array
    {
        $entity = new $className();
        $statement = $this->entityManager->getRepository($className)->createQueryBuilder('e');

        if (method_exists($this->entity, 'getUrls')) {
            $statement->join('e.urls', 'urls')
                ->andWhere('urls.archived = :archived')
                ->orWhere('urls is NULL')
                ->setParameter(':archived', false)
                ->addSelect('urls');
        }

        if (method_exists($entity, 'getWebsite')) {
            $statement->andWhere('e.website = :website')
                ->setParameter(':website', $this->website);
        }

        if (method_exists($entity, 'getAdminName')) {
            $statement->orderBy('e.adminName');
        }

        $result = $statement->getQuery()->getResult();

        $choices = [];
        foreach ($result as $entity) {
            $label = $this->entity instanceof Information ? 'Information' : $entity->getAdminName();
            $choices[$label] = $entity->getId();
        }

        return $choices;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ActionIntl::class,
            'translation_domain' => 'admin',
            'form_data' => null,
            'displayTemplates' => null,
            'website' => null,
        ]);
    }
}
