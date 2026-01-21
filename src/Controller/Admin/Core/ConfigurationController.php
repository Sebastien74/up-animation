<?php

declare(strict_types=1);

namespace App\Controller\Admin\Core;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Configuration;
use App\Form\Interface\CoreFormManagerInterface;
use App\Form\Type\Core\Configuration\ConfigurationType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ConfigurationController.
 *
 * ConfigurationModel management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/website/configuration', schemes: '%protocol%')]
class ConfigurationController extends AdminController
{
    protected ?string $class = Configuration::class;
    protected ?string $formType = ConfigurationType::class;

    /**
     * ConfigurationController constructor.
     */
    public function __construct(
        protected CoreFormManagerInterface $coreFormInterface,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $coreFormInterface->configuration();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Edit ConfigurationModel style.
     *
     * {@inheritdoc}
     *
     * @throws NonUniqueResultException
     */
    #[Route('/edit', name: 'admin_website_style', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        $website = $this->getWebsite();
        $locale = $request->get('form_locale') ? $request->get('form_locale') : $website->configuration->locale;
        $configuration = $this->coreLocator->em()->getRepository(Configuration::class)->findOptimizedAdmin($website, $locale);
        if (!$configuration) {
            throw $this->createNotFoundException($this->coreLocator->translator()->trans("Cette configuration n'existe pas !!", [], 'front'));
        }
        $this->formManager->synchronizeLocales($configuration);
        $this->template = 'admin/page/website/configuration.html.twig';
        $this->entity = $configuration;

        return parent::edit($request);
    }
}
