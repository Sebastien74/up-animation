<?php

declare(strict_types=1);

namespace App\Form\Manager\Module;

use App\Entity\Core\Website;
use App\Entity\Layout;
use App\Entity\Module\Form\Configuration;
use App\Entity\Module\Form\Form;
use App\Service\Interface\CoreLocatorInterface;
use Exception;
use Random\RandomException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * FormManager.
 *
 * Manage admin Form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => FormManager::class, 'key' => 'module_form_form_manager'],
])]
class FormManager
{
    private ?Website $website;

    /**
     * FormManager constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * @prePersist
     *
     * @throws Exception
     */
    public function prePersist(Form $form, Website $website): void
    {
//        dd('générer un mail de confirmation automatique si pas existant.');

        $this->website = $website;

        if (!$form->getStepform()) {
            $host = str_replace(['www.'], [''], $this->coreLocator->request()->getHost());
            $configuration = new Configuration();
            $configuration->setSecurityKey($this->coreLocator->alphanumericKey(10));
            $configuration->setReceivingEmails(['contact@'.$host]);
            $configuration->setSendingEmail('no-reply@'.$host);
            $configuration->setForm($form);
            $form->setConfiguration($configuration);
        }

        $this->addLayout($form);

        $this->coreLocator->em()->persist($form);
    }

    /**
     * @preUpdate
     */
    public function preUpdate(Form $form, Website $website): void
    {
        $this->website = $website;
        $configuration = $form->getConfiguration();
        if (!$form->getStepform() && $configuration instanceof Configuration && !$configuration->getSecurityKey()) {
            $configuration->setSecurityKey($this->coreLocator->alphanumericKey(10));
            $this->coreLocator->em()->persist($configuration);
        }

        foreach ($form->getIntls() as $intl) {
            if ((!$intl->getBody() && 'fr' === $intl->getLocale()) || ($intl->getBody() && strlen(strip_tags($intl->getBody())) === 0 && 'fr' === $intl->getLocale())) {
                $message = "<p>Bonjour %firstname% %lastname%,</p>";
                $message .= "<p>Nous vous remercions d’avoir pris le temps de nous contacter via notre formulaire en ligne. Votre message a bien été reçu et sera traité dans les meilleurs délais.</p>";
                $message .= "<p>Voici un récapitulatif des informations que vous nous avez transmises :</p>";
                $message .= "<p><strong>Nom : </strong>%lastname%</p>";
                $message .= "<p><strong>Prénom : </strong>%firstname%</p>";
                $message .= "<p><strong>Email : </strong>%email%</p>";
                $message .= "<p><strong>Téléphone : </strong>%phone%</p>";
                $message .= "<p><strong>Message envoyé: </strong>%message%</p>";
                $message .= "<p>Nous mettons tout en œuvre pour vous répondre rapidement. Si vous avez des précisions à apporter ou des questions supplémentaires, n’hésitez pas à nous écrire à nouveau.</p>";
                $message .= "<p>Encore merci pour votre confiance,</p>";
                $message .= "<p>L’équipe %companyName%.</p>";
                $intl->setBody($message);
                $this->coreLocator->em()->persist($intl);
            }
        }
    }

    /**
     * Add Layout & GDPR field.
     */
    private function addLayout(Form $form): void
    {
        $layout = new Layout\Layout();
        $layout->setAdminName($form->getAdminName());
        $layout->setWebsite($this->website);
        $form->setLayout($layout);

        if (!$form->getStepform()) {
            $this->addZone($layout);
        }
    }

    /**
     * Add Zone Layout.
     */
    private function addZone(Layout\Layout $layout): void
    {
        $zone = new Layout\Zone();
        $zone->setFullSize(true);
        $layout->addZone($zone);

        $this->addCol($zone);
    }

    private function addCol(Layout\Zone $zone): void
    {
        $col = new Layout\Col();
        $zone->addCol($col);

        $this->addBlock($col, 'Nom', 'lastname', 'form-text', 'Nom', 'Saisissez votre nom', true, 1, 6);
        $this->addBlock($col, 'Prénom', 'firstname', 'form-text', 'Prénom', 'Saisissez votre prénom', true, 2, 6);
        $this->addBlock($col, 'Email', 'email', 'form-email', 'Email', 'Saisissez votre email', true, 3, 6);
        $this->addBlock($col, 'Téléphone', 'phone', 'form-phone', 'Téléphone', 'Saisissez votre numéro de téléphone', true, 4, 6);
        $this->addBlock($col, 'Message', 'message', 'form-textarea', 'Message', 'Saisissez votre message', false, 5);
        $this->addBlock($col, 'RGPD', 'gdpr-field', 'form-gdpr', null, null, false, 6);
        $this->addBlock($col, 'Bouton de soumission', 'submit-field', 'form-submit', 'Envoyer', null, false, 7);
    }

    /**
     * Add Col Block.
     */
    private function addBlock(
        Layout\Col $col,
        string $adminName,
        string $slug,
        string $field,
        ?string $label = null,
        ?string $placeholder = null,
        ?bool $anonymize = false,
        ?int $position = 1,
        ?int $size = 12,
    ): void {

        $block = new Layout\Block();
        $block->setAdminName($adminName);
        $block->setSlug($slug);
        $block->setPosition($position);
        $block->setSize($size);

        if ('form-submit' === $field) {
            $block->setColor('btn-primary');
        }

        $col->addBlock($block);

        $this->addBlockType($block, $field);
        $this->addBlockIntl($block, $label, $placeholder);
        $this->addField($block, $field, $slug, $anonymize);
    }

    /**
     * Add Block BlockType.
     */
    private function addBlockType(Layout\Block $block, string $field): void
    {
        $blockType = $this->coreLocator->em()->getRepository(Layout\BlockType::class)->findOneBy(['slug' => $field]);
        $block->setBlockType($blockType);
    }

    /**
     * Add Block intl.
     */
    private function addBlockIntl(Layout\Block $block, ?string $label = null, ?string $placeholder = null): void
    {
        $intl = new Layout\BlockIntl();
        $intl->setLocale($this->website->getConfiguration()->getLocale());
        $intl->setWebsite($this->website);
        $intl->setTitle($label);
        $intl->setPlaceholder($placeholder);
        $block->addIntl($intl);
    }

    /**
     * Add Block field.
     */
    private function addField(Layout\Block $block, string $field, string $slug, bool $anonymize = false): void
    {
        $configuration = new Layout\FieldConfiguration();
        $configuration->setSlug($slug);
        $configuration->setRequired(true);
        $configuration->setBlock($block);
        $configuration->setAnonymize($anonymize);

        if ('form-gdpr' === $field) {
            $configuration->setExpanded(true);
            $configuration->setMultiple(true);
            $configuration->setSmallSize(true);
            $label = "J'accepte que mes données soient utilisées pour me recontacter dans le cadre de cette demande.";
            $valueIntl = new Layout\FieldValueIntl();
            $valueIntl->setLocale($this->website->getConfiguration()->getLocale());
            $valueIntl->setIntroduction($label);
            $valueIntl->setBody('1');
            $valueIntl->setWebsite($this->website);
            $value = new Layout\FieldValue();
            $value->setAdminName($label);
            $value->addIntl($valueIntl);
            $configuration->addFieldValue($value);
        }

        $block->setFieldConfiguration($configuration);
    }
}
