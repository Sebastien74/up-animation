<?php

declare(strict_types=1);

namespace App\Form\Manager\Module;

use App\Entity\Core\Website;
use App\Entity\Module\Form\Configuration;
use App\Entity\Module\Form\StepForm;
use App\Service\Interface\CoreLocatorInterface;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * StepFormManager.
 *
 * Manage admin StepForm form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => StepFormManager::class, 'key' => 'module_step_form_form_manager'],
])]
readonly class StepFormManager
{
    /**
     * StepFormManager constructor.
     */
    public function __construct(private CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * @prePersist
     *
     * @throws Exception
     */
    public function prePersist(StepForm $stepForm, Website $website): void
    {
        $host = str_replace(['www.'], [''], $this->coreLocator->request()->getHost());
        $configuration = new Configuration();
        $configuration->setSecurityKey($this->coreLocator->alphanumericKey(10));
        $configuration->setReceivingEmails(['contact@'.$host]);
        $configuration->setSendingEmail('no-reply@'.$host);
        $configuration->setAjax(true);
        $stepForm->setConfiguration($configuration);
        $this->coreLocator->em()->persist($stepForm);
    }

    /**
     * @preUpdate
     *
     * @throws Exception
     */
    public function preUpdate(StepForm $stepForm, Website $website): void
    {
        foreach ($stepForm->getForms() as $form) {
            $configuration = $form->getConfiguration();
            if (!$configuration) {
                $configuration = new Configuration();
                $form->setConfiguration($configuration);
                $this->coreLocator->em()->persist($form);
            }
            $configuration->setDynamic($stepForm->getConfiguration()->isDynamic());
            $this->coreLocator->em()->persist($configuration);
        }
    }
}
