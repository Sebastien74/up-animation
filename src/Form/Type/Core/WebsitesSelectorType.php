<?php

declare(strict_types=1);

namespace App\Form\Type\Core;

use App\Entity\Core\Website;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * WebsitesSelectorType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class WebsitesSelectorType extends AbstractType
{
    private EntityManagerInterface $entityManager;
    private bool $isInternalUser;
    private ?UserInterface $user;

    /**
     * WebsitesSelectorType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $this->entityManager = $this->coreLocator->em();
        $this->user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isInternalUser = $this->user && in_array('ROLE_INTERNAL', $this->user->getRoles());
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $website = $options['website'];

        $builder->add('websites', ChoiceType::class, [
            'label' => false,
            'display' => 'search',
            'attr' => ['class' => 'websites-selector', 'group' => 'col-12 mb-0 mt-2'],
            'data' => $website->getId(),
            'choices' => $this->getUserWebsites(),
        ]);
    }

    /**
     * Get user Websites.
     */
    private function getUserWebsites(): array
    {
        $choices = [];
        $websites = $this->isInternalUser ?
            $this->entityManager->getRepository(Website::class)->findBy(['active' => true])
            : $this->user->getWebsites();

        foreach ($websites as $website) {
            if ($website->isActive()) {
                $choices[$website->getAdminName()] = $website->getId();
            }
        }

        return $choices;
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
