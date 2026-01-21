<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Entity\Core\Website;
use App\Entity\Layout\Block;
use App\Entity\Layout\Col;
use App\Entity\Layout\FieldConfiguration;
use App\Entity\Layout\FieldValue;
use App\Entity\Layout\Layout;
use App\Entity\Layout\Zone;
use App\Entity\Module\Form\Configuration;
use App\Entity\Module\Form\Form;
use App\Service\Core\Urlizer;
use App\Service\Interface\CoreLocatorInterface;
use Exception;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * FormDuplicateService.
 *
 * To duplicate form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FormDuplicateService implements FormDuplicateInterface
{
    private Form $form;
    private \DateTime $datetime;
    private Website $website;
    private UserInterface $user;

    /**
     * FormDuplicateService constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * Execute service.
     *
     * @throws Exception
     */
    public function execute(Form $form, UserInterface $user): Form
    {
        $this->datetime = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $this->website = $form->getWebsite();
        $this->user = $user;

        $this->form($form);
        $this->formPosition($form);
        $this->configuration($form);
        $this->layout($form);

        $this->coreLocator->em()->persist($this->form);
        $this->coreLocator->em()->flush();

        return $this->form;
    }

    /**
     * To set Form.
     */
    private function form(Form $form): void
    {
        $adminName = $form->getAdminName();

        $this->form = new Form();
        $this->duplicateFields($this->form, $form);

        $this->form->setAdminName($adminName.' (Duplication)');
        $this->form->setSlug(Urlizer::urlize($adminName.'-duplicate-'.uniqid()));
    }

    /**
     * To set Form position.
     */
    private function formPosition(Form $form): void
    {
        $formsCount = [];
        $forms = $this->coreLocator->em()->getRepository(Form::class)->findBy(['website' => $this->website]);
        foreach ($forms as $existingForm) {
            $keyName = $existingForm->getStepform() ? 'steps' : 'basic';
            $formsCount[$keyName][] = $existingForm;
        }

        $lastPosition = $form->getStepform() && !empty($formsCount['steps'])
            ? count($formsCount['steps']) : (!$form->getStepform() && !empty($formsCount['basic'])
                ? count($formsCount['basic']) : 0);

        $this->form->setPosition($lastPosition + 1);
    }

    /**
     * To set Form ConfigurationModel.
     */
    private function configuration(Form $form): void
    {
        $configuration = new Configuration();
        $configuration->setForm($this->form);

        $this->duplicateFields($configuration, $form->getConfiguration(), ['securityKey']);
        $this->form->setConfiguration($configuration);
    }

    /**
     * To set Form Layout.
     */
    private function layout(Form $form): void
    {
        $referLayout = $form->getLayout();

        $layout = new Layout();
        $this->duplicateFields($layout, $referLayout, []);
        $this->form->setLayout($layout);
        $this->zones($referLayout, $layout);
    }

    /**
     * To set Form Layout Zone[].
     */
    private function zones(Layout $referLayout, Layout $layout): void
    {
        foreach ($referLayout->getZones() as $referZone) {
            $zone = new Zone();
            $this->duplicateFields($zone, $referZone, []);
            $zone->setLayout($layout);
            $layout->addZone($zone);
            $this->cols($referZone, $zone);
        }
    }

    /**
     * To set Form Layout Col[].
     */
    private function cols(Zone $referZone, Zone $zone): void
    {
        foreach ($referZone->getCols() as $referCol) {
            $col = new Col();
            $this->duplicateFields($col, $referCol, []);
            $col->setZone($zone);
            $zone->addCol($col);
            $this->blocks($referCol, $col);
        }
    }

    /**
     * To set Form Layout Block[].
     */
    private function blocks(Col $referCol, Col $col): void
    {
        foreach ($referCol->getBlocks() as $referBlock) {
            $block = new Block();
            $this->duplicateFields($block, $referBlock, []);
            $block->setCol($col);
            $block->setBlockType($referBlock->getBlockType());
            $col->addBlock($block);

            $referConfiguration = $referBlock->getFieldConfiguration();
            if ($referConfiguration instanceof FieldConfiguration) {
                $configuration = new FieldConfiguration();
                $this->duplicateFields($configuration, $referConfiguration, []);
                $configuration->setBlock($block);
                $block->setFieldConfiguration($configuration);
                foreach ($referConfiguration->getFieldValues() as $referValue) {
                    $value = new FieldValue();
                    $this->duplicateFields($value, $referValue, []);
                    $value->setConfiguration($configuration);
                }
            }
        }
    }

    /**
     * To duplicate entity fields.
     */
    private function duplicateFields(mixed $entity, mixed $referEntity, array $excludedFieldsAndTypes = []): void
    {
        $metadata = $this->coreLocator->em()->getClassMetadata(get_class($entity));
        $fields = $metadata->getFieldNames();
        $excludedFieldsAndTypes = array_merge(['id', 'datetime'], $excludedFieldsAndTypes);

        foreach ($fields as $field) {
            $type = $metadata->getTypeOfField($field);
            if (!in_array($type, $excludedFieldsAndTypes) && !in_array($field, $excludedFieldsAndTypes)) {
                $setter = 'set'.ucfirst($field);
                $getter = 'get'.ucfirst($field);
                $getter = method_exists($entity, $getter) ? $getter : 'is'.ucfirst($field);
                $entity->$setter($referEntity->$getter());
            }
        }

        if (method_exists($entity, 'setCreatedAt')) {
            $entity->setCreatedAt($this->datetime);
        }

        if (method_exists($entity, 'setCreatedBy')) {
            $entity->setCreatedBy($this->user);
        }

        if (method_exists($entity, 'setWebsite')) {
            $entity->setWebsite($this->website);
        }

        $this->intls($entity, $referEntity);
        $this->mediaRelations($entity, $referEntity);
    }

    /**
     * To set intl[].
     */
    private function intls(mixed $entity, mixed $referEntity): void
    {
        if (is_object($entity) && method_exists($entity, 'getIntls')) {
            foreach ($referEntity->getIntls() as $referIntl) {
                $intlData = $this->coreLocator->metadata($entity, 'intls');
                $intl = new ($intlData->targetEntity)();
                $this->duplicateFields($intl, $referIntl, []);
                $entity->addIntl($intl);
                $this->coreLocator->em()->persist($entity);
            }
        } elseif (is_object($entity) && method_exists($entity, 'getIntl') && $referEntity->getIntl()) {
            $intlData = $this->coreLocator->metadata($entity, 'intl');
            $intl = new ($intlData->targetEntity)();
            $this->duplicateFields($intl, $referEntity->getIntl(), []);
            $entity->setIntl($intl);
            $this->coreLocator->em()->persist($entity);
        }
    }

    /**
     * To set MediaRelation[].
     */
    private function mediaRelations(mixed $entity, mixed $referEntity): void
    {
        if (is_object($entity) && method_exists($entity, 'getMediaRelations')) {
            foreach ($referEntity->getMediaRelations() as $referMediaRelation) {
                $data = $this->coreLocator->metadata($entity, 'mediaRelations');
                $mediaRelation = new ($data->targetEntity)();
                $this->duplicateFields($mediaRelation, $referMediaRelation, []);
                $mediaRelation->setMedia($referMediaRelation->getMedia());
                $entity->addMediaRelation($mediaRelation);
                $this->coreLocator->em()->persist($entity);
            }
        } elseif (is_object($entity) && method_exists($entity, 'getMediaRelation')) {
            $referMediaRelation = $referEntity->getMediaRelation();
            $data = $this->coreLocator->metadata($entity, 'mediaRelation');
            $mediaRelation = new ($data->targetEntity)();
            $this->duplicateFields($mediaRelation, $referMediaRelation, []);
            $entity->setMediaRelation($mediaRelation);
            $mediaRelation->setMedia($referMediaRelation->getMedia());
            $this->coreLocator->em()->persist($entity);
        }
    }
}
