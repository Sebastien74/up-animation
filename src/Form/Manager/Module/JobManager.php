<?php

declare(strict_types=1);

namespace App\Form\Manager\Module;

use App\Entity\Core\Website;
use App\Entity\Module\Form as FormEntity;
use App\Entity\Module\Recruitment\Job;
use App\Entity\Seo\Model;
use App\Form\Manager\Layout\LayoutManager;
use App\Service\DataFixtures\PageFixtures;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\Form;

/**
 * JobManager.
 *
 * Manage Job form.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => JobManager::class, 'key' => 'module_job_form_manager'],
])]
class JobManager
{
    /**
     * SectorManager constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly PageFixtures $pageFixtures,
        private readonly LayoutManager $layoutManager,
    ) {
    }

    /**
     * @prePersist
     */
    public function prePersist(Job $job, Website $website, array $interface, Form $form): void
    {
        $frontForm = $this->coreLocator->em()->getRepository(\App\Entity\Module\Form\Form::class)->findOneBy(['website' => $website, 'slug' => 'recruitment']);
        if (!$frontForm instanceof FormEntity\Form) {
            $this->defaultForm($website);
        }

        foreach ($website->getConfiguration()->getAllLocales() as $locale) {
            $model = $this->coreLocator->em()->getRepository(Model::class)->findByLocaleClassnameAndWebsite($locale, Job::class, $website);
            if (!$model instanceof Model) {
                $model = new Model();
                $model->setLocale($locale)
                    ->setClassName(Job::class)
                    ->setAdminName("Offre d'emploi")
                    ->setMetaTitle("[intl.title]")
                    ->setMetaOgTitle("[intl.title]")
                    ->setMetaDescription("[intl.body]")
                    ->setMetaOgDescription("[intl.body]")
                    ->setWebsite($website);
                $this->coreLocator->em()->persist($model);
                $this->coreLocator->em()->flush();
            }
        }
    }

    /**
     * To set default Form
     */
    private function defaultForm(Website $website): void
    {
        $adminName = 'Formulaire de recrutement';
        $this->pageFixtures->setWebsite($website);
        $this->pageFixtures->setLocale($this->coreLocator->website()->configuration->locale);
        $position = count($this->coreLocator->em()->getRepository(FormEntity\Form::class)->findBy(['website' => $website])) + 1;
        $form = $this->pageFixtures->setForm($adminName, 'recruitment');
        $form->setPosition($position);

        $formLayout = $this->pageFixtures->addLayout($adminName, true);
        $zoneLayout = $this->pageFixtures->addZone($formLayout, 1, false, true);
        $zoneLayout->setAsSection(false);
        $col = $this->pageFixtures->addCol($zoneLayout);
        $name = $this->pageFixtures->addBlock($col, 'form-text');
        $this->pageFixtures->addFieldConfiguration($name, 'Nom', 'Saisissez votre nom', true, false, 'Veuillez renseigner votre nom.', 'lastname');
        $firstName = $this->pageFixtures->addBlock($col, 'form-text', null, null, 2);
        $this->pageFixtures->addFieldConfiguration($firstName, 'Prénom', 'Saisissez votre prénom', true, false, 'Veuillez renseigner votre prénom.', 'firstname');
        $email = $this->pageFixtures->addBlock($col, 'form-email', null, null, 3);
        $this->pageFixtures->addFieldConfiguration($email, 'Email', 'Saisissez votre email', true, false, 'Veuillez renseigner votre email.', 'email');
        $phone = $this->pageFixtures->addBlock($col, 'form-phone', null, null, 4);
        $this->pageFixtures->addFieldConfiguration($phone, 'Téléphone', 'Saisissez votre numéro de téléphone', true, false, 'Veuillez renseigner votre numéro de téléphone.', 'phone');
        $message = $this->pageFixtures->addBlock($col, 'form-textarea', null, null, 5);
        $this->pageFixtures->addFieldConfiguration($message, 'Vos motivations', "En quelques mots, vos motivations pour rejoindre nos équipes :", true, false, 'Veuillez exposer vos motivations.', 'motivations');
        $file = $this->pageFixtures->addBlock($col, 'form-file', null, null, 6);
        $file->setControls(true);
        $file->setColor('btn-primary');
        $this->pageFixtures->addFieldConfiguration($file, 'Votre cv', null,true, false, 'Veuillez joindre votre CV..', 'curriculum')->setFilesTypes(['.doc', '.docx', '.pdf']);
        $gdpr = $this->pageFixtures->addBlock($col, 'form-gdpr', null, null, 7);
        $this->pageFixtures->addFieldConfiguration($gdpr, 'RGPD', "J'accepte que mes données soient utilisées pour me recontacter dans le cadre de cette demande.", true, true, 'Veuillez accépter.', 'gdpr');
        $submit = $this->pageFixtures->addBlock($col, 'form-submit', null, null, 8);
        $this->pageFixtures->addFieldConfiguration($submit, 'Envoyer', 'Saisissez votre message', true, false, null, 'submit');

        $form->setLayout($formLayout);

        $this->coreLocator->em()->persist($form);
        $this->layoutManager->setGridZone($formLayout);
    }
}
