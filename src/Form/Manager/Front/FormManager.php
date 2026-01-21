<?php

declare(strict_types=1);

namespace App\Form\Manager\Front;

use App\Entity\Layout\Block;
use App\Entity\Layout\BlockIntl;
use App\Entity\Layout\FieldConfiguration;
use App\Entity\Module\Form;
use App\Message\SendEmail;
use App\Model\Core\WebsiteModel;
use App\Model\EntityModel;
use App\Service\Content\RecaptchaService;
use App\Service\Core\MailerService;
use App\Service\Core\MessengerWorkerService;
use App\Service\Core\Urlizer;
use App\Service\Interface\CoreLocatorInterface;
use App\Twig\Translation\IntlRuntime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form as Component;
use Symfony\Component\Form\Button;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * FormManager.
 *
 * Manage front Form Action
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FormManager
{
    private const bool MESSENGER = true;

    private Session $session;
    private array $fields = [];
    private string $sender = '';
    private array $receivers = [];
    private bool $senderInForm = true;
    private ?string $phone = null;
    private array $configurations = [];
    private array $attachments = [];
    private ?string $error = null;

    /**
     * FormManager constructor.
     */
    public function __construct(
        private readonly MessengerWorkerService $messengerWorkerService,
        private readonly MessageBusInterface $bus,
        private readonly CoreLocatorInterface $coreLocator,
        private readonly RecaptchaService $recaptcha,
        private readonly MailerService $mailer,
        private readonly IntlRuntime $intlRuntime,
    ) {
        $this->session = new Session();
    }

    /**
     * Set errors flashBags.
     */
    public function errors(FormInterface $form): void
    {
        foreach ($form->all() as $child) {
            if (!$child instanceof SubmitButton && !$child instanceof Button) {
                $data = $child->getData();
                if ($data instanceof UploadedFile || (is_array($data) && !empty($data[0]) && $data[0] instanceof UploadedFile)) {
                    $this->session->getFlashBag()->add($child->getName().'_message_uploaded_file', $this->coreLocator->translator()->trans('Veuillez recharger votre fichier', [], 'front_form'));
                } elseif (is_object($data) && method_exists($data, 'getId')) {
                    $this->session->getFlashBag()->add($child->getName().'_value', $data->getId());
                } else {
                    $this->session->getFlashBag()->add($child->getName().'_value', $data);
                }
                if ($child->isSubmitted() && !$child->isValid()) {
                    foreach ($child->getErrors() as $error) {
                        if ($this->session->getFlashBag()->get($child->getName().'_message_uploaded_file')) {
                            $this->session->getFlashBag()->set($child->getName().'_message_uploaded_file', '1');
                        }
                        $this->session->getFlashBag()->add($child->getName().'_message', $error->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Get Contact by request Token.
     */
    public function getContact(): mixed
    {
        $contact = null;
        $token = !empty($_GET['token']) ? $_GET['token'] : null;

        if ($token) {
            $contact = $this->coreLocator->em()->getRepository(Form\ContactForm::class)->findOneBy(['token' => $token, 'tokenExpired' => false]);
            if (!$contact) {
                $contact = $this->coreLocator->em()->getRepository(Form\ContactStepForm::class)->findOneBy(['token' => $token, 'tokenExpired' => false]);
            }
            if (!$contact) {
                header('Status: 301 Moved Permanently', false, 301);
                header('Location:'.$this->coreLocator->requestStack()->getCurrentRequest()->getSchemeAndHttpHost());
                exit;
            }
        }

        $form = $contact instanceof Form\ContactForm ? $contact->getForm() : ($contact instanceof Form\ContactStepForm ? $contact->getStepform() : null);
        $removeToken = $form instanceof Form\Form ? $form->getCalendars()->isEmpty() : true;

        if ($contact && $removeToken) {
            $contact->setTokenExpired(true);
            $this->coreLocator->em()->persist($contact);
            $this->coreLocator->em()->flush();
        }

        return $contact;
    }

    /**
     * Process if form is valid.
     *
     * @throws Exception|InvalidArgumentException|ExceptionInterface
     */
    public function success(Form\StepForm|Form\Form $form, FormInterface $formPost): bool|Form\ContactForm|Form\ContactStepForm
    {
        $configuration = $form->getConfiguration();
        $website = $form->getWebsite();
        $website = WebsiteModel::fromEntity($website, $this->coreLocator);
        $data = $formPost->getData();
        $haveCalendars = $configuration->isCalendarsActive() && $form->getCalendars()->count() > 0;

        if ($form instanceof Form\Form) {
            $this->getFields($form, $data);
        } elseif ($form instanceof Form\StepForm) {
            foreach ($form->getForms() as $stepForm) {
                $this->getFields($stepForm, $data);
            }
        }

        $this->getConfigurations();

        if (!$this->sender) {
            $formSender = $form->getConfiguration()->getSendingEmail();
            $this->sender = $formSender ?: 'noreply@agence-felix.fr';
            $this->senderInForm = false;
        }

        if (!$this->recaptcha->execute($website->entity, $configuration, $formPost, $this->sender)) {
            return false;
        }

        $intl = $this->getIntl($form);

        if (!$this->checkContact($form)) {
            return false;
        }

        $contact = $this->addContact($form);
        $this->setAttachments($form, $contact);

        if (!$haveCalendars) {
            $this->sendEmail($website, $form, $intl);
            if ($configuration->isConfirmEmail() && $intl->confirmation) {
                $this->sendConfirm($website, $form, $intl);
            }
            if ($this->error) {
                $this->session->getFlashBag()->add('error_form', $this->error);
                return false;
            }
        }

        return $contact;
    }

    /**
     * Get all fields data.
     *
     * @throws NonUniqueResultException
     */
    private function getFields(Form\Form $form, array $data): void
    {
        $excludes = ['form-submit', 'form-password'];

        foreach ($form->getLayout()->getZones() as $zone) {
            foreach ($zone->getCols() as $col) {
                foreach ($col->getBlocks() as $block) {
                    $blockType = $block->getBlockType();
                    $blockTypeSlug = $blockType->getSlug();
                    $fieldConfiguration = $blockType->getFieldType();
                    $fieldType = $fieldConfiguration ? $block->getBlockType()->getFieldType() : null;
                    foreach ($block->getIntls() as $intl) {
                        if ($intl->getLocale() === $this->coreLocator->requestStack()->getCurrentRequest()->getLocale() && !in_array($blockTypeSlug, $excludes)) {
                            $fieldData = $this->getFieldData($block, $intl, $data, $blockTypeSlug);
                            $valueIntl = $fieldData->value;
                            if (Component\Extension\Core\Type\CountryType::class === $fieldType) {
                                $valueIntl = $this->intlRuntime->countryName($fieldData->value);
                            }
                            $this->fields['field_'.$block->getId()] = [
                                'slug' => $block->getFieldConfiguration() ? $block->getFieldConfiguration()->getSlug() : $block->getSlug(),
                                'label' => $fieldData->label,
                                'value' => $fieldData->value,
                                'email' => $fieldData->email,
                                'valueIntl' => $valueIntl,
                                'blockTypeSlug' => $blockTypeSlug,
                            ];
                            if ('form-emails' === $blockTypeSlug) {
                                $emails = explode(';', $this->fields['field_'.$block->getId()]['email']);
                                foreach ($emails as $email) {
                                    $matches = explode('-email-', $email);
                                    $this->receivers[] = trim(end($matches));
                                }
                            }
                            if ('form-email' === $blockTypeSlug && !empty($this->fields['field_'.$block->getId()]['value'])) {
                                $this->sender = $this->fields['field_'.$block->getId()]['value'];
                            }
                            if ('form-phone' === $blockTypeSlug) {
                                $this->phone = $this->fields['field_'.$block->getId()]['value'];
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Get form field sConfigurations.
     */
    private function getConfigurations(): void
    {
        $blockRepository = $this->coreLocator->em()->getRepository(Block::class);
        foreach ($this->fields as $keyName => $field) {
            $matches = explode('_', $keyName);
            $this->configurations[$keyName] = $blockRepository->find(end($matches))->getFieldConfiguration();
        }
    }

    /**
     * Get field data.
     *
     * @throws NonUniqueResultException
     */
    private function getFieldData(Block $block, BlockIntl $intl, array $data, string $blockTypeSlug): object
    {
        $label = $this->getFieldLabel($block, $intl, $blockTypeSlug);
        $value = !empty($data['field_'.$block->getId()]) ? $data['field_'.$block->getId()] : null;
        $email = null;

        if ($block->getBlockType() && 'form-emails' === $block->getBlockType()->getSlug() && is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $fieldValues = $block->getFieldConfiguration()->getFieldValues();
            $valueMatches = explode('-email-', $value);
            $email = end($valueMatches);
            foreach ($fieldValues as $fieldValue) {
                foreach ($fieldValue->getIntls() as $intl) {
                    if ($intl->getLocale() === $this->coreLocator->locale()) {
                        if ($intl->getBody() === $email) {
                            $value = $intl->getIntroduction();
                            break;
                        }
                    }
                }
            }
        }

        if (is_array($value) && 1 === count($value) && !$label) {
            $fieldValues = $block->getFieldConfiguration()->getFieldValues();
            foreach ($fieldValues as $fieldValue) {
                foreach ($fieldValue->getIntls() as $intl) {
                    if ($intl->getLocale() === $this->coreLocator->locale()) {
                        $label = $intl->getIntroduction();
                        break;
                    }
                }
            }
        }

        return (object) [
            'label' => $label,
            'value' => $value,
            'email' => $email,
        ];
    }

    /**
     * Get field label.
     *
     * @throws NonUniqueResultException
     */
    private function getFieldLabel(Block $block, BlockIntl $intl, string $blockTypeSlug): ?string
    {
        $label = null;

        if ('form-choice-entity' === $blockTypeSlug) {
            $classname = $block->getFieldConfiguration()->getClassName();
            $interface = $classname ? $this->coreLocator->interfaceHelper()->generate($classname) : [];
            $masterFieldConfig = $block->getFieldConfiguration()->getMasterField();
            $masterField = $masterFieldConfig && !empty($interface['masterField']) ? $interface['masterField'] : [];
            $masterFieldSlug = $masterFieldConfig ? str_replace($masterField.'-', '', $masterFieldConfig) : null;
            if ($classname && $masterField && $masterFieldSlug) {
                $website = $block->getCol()->getZone()->getLayout()->getWebsite();
                $referEntity = new $classname();
                $qb = $this->coreLocator->em()->createQueryBuilder()->select('e')
                    ->from($classname, 'e')
                    ->leftJoin('e.'.$masterField, $masterField)
                    ->andWhere($masterField.'.slug = :slug')
                    ->setParameter('slug', $masterFieldSlug)
                    ->addSelect($masterField);
                if (method_exists($referEntity, 'getWebsite')) {
                    $qb = $qb->andWhere('e.website = :website')
                        ->setParameter('website', $website);
                }
                $entity = $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();
                $getter = 'get'.ucfirst($masterField);
                $label = is_object($entity) ? $entity->$getter()->getAdminName() : null;
            }
        }

        if (!$label) {
            $label = $intl->getTitle() && strlen(strip_tags($intl->getTitle())) > 0
                ? $intl->getTitle() : ($intl->getPlaceholder() && strlen(strip_tags($intl->getPlaceholder())) > 0
                    ? $intl->getPlaceholder() : str_replace(' (form)', '', $block->getBlockType()->getAdminName()));
        }

        return $label;
    }

    /**
     * Get messages to alert & email confirmation.
     *
     * @throws NonUniqueResultException|MappingException
     */
    public function getIntl(mixed $entity): object
    {
        $alert = $this->coreLocator->translator()->trans('Merci pour votre message !!', [], 'front_form');
        $intl = EntityModel::fromEntity($entity, $this->coreLocator)->response->intl;

        return (object) [
            'alert' => $intl->placeholder ?: $alert,
            'title' => $intl->title,
            'subject' => $intl->title ?: $entity->getAdminName(),
            'confirmation' => $intl->body,
            'confirmationSubject' => $intl->title,
            'webmasterEmail' => $intl->introduction,
        ];
    }

    /**
     * If Form as unique contact check if email already existing.
     */
    private function checkContact(Form\StepForm|Form\Form $form): bool
    {
        if ($form->getConfiguration()->isUniqueContact() && ($this->senderInForm || $this->phone)) {
            if ($this->senderInForm) {
                $existing = $this->coreLocator->em()->getRepository(Form\ContactForm::class)->findOneBy([
                    'form' => $form,
                    'email' => $this->sender,
                ]);
            } else {
                $existing = $this->coreLocator->em()->getRepository(Form\ContactForm::class)->findOneBy([
                    'form' => $form,
                    'phone' => $this->phone,
                ]);
            }
            if ($existing) {
                $message = $this->senderInForm
                    ? $this->coreLocator->translator()->trans('Cet email existe déjà', [], 'front_form')
                    : $this->coreLocator->translator()->trans('Ce téléphone existe déjà', [], 'front_form');
                $this->session->getFlashBag()->add('error_form', $message);

                return false;
            }
        }

        return true;
    }

    /**
     * Add Contact to DB.
     *
     * @throws Exception
     */
    private function addContact(Form\StepForm|Form\Form $form): Form\ContactForm|Form\ContactStepForm|null
    {
        $registrationValid = $this->checkRegistration($form);

        if ($registrationValid) {
            $contact = $form instanceof Form\Form ? new Form\ContactForm() : new Form\ContactStepForm();

            $requestCalendar = !empty($_GET['calendar']) ? $_GET['calendar'] : null;
            if ($requestCalendar) {
                $contact->setCalendar($this->coreLocator->em()->getRepository(Form\Calendar::class)->find($requestCalendar));
            }

            $email = $this->senderInForm ? $this->sender : null;
            $token = $email ? bin2hex(random_bytes(45).md5($email)) : null;
            $contact->setToken($token);
            $contact->setEmail($email);
            $contact->setPhone($this->phone);
            $contact->setCreatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
            $form->addContact($contact);

            foreach ($this->fields as $keyName => $field) {
                $setField = false;
                $value = new Form\ContactValue();
                $fieldValue = is_array($field['value']) ? json_encode($field['value']) : $field['value'];
                $fieldConfiguration = !empty($this->configurations[$keyName]) ? $this->configurations[$keyName] : null;
                $fieldType = $fieldConfiguration instanceof FieldConfiguration ? $fieldConfiguration->getBlock()->getBlockType()->getFieldType() : null;
                $originalValue = $fieldValue;
                if ($fieldValue instanceof \DateTime) {
                    $setField = true;
                    $fieldValue = Component\Extension\Core\Type\DateType::class === $fieldType ? $fieldValue->format('Y-m-d')
                        : (Component\Extension\Core\Type\TimeType::class === $fieldType ? $fieldValue->format('H:m') : $fieldValue->format('Y-m-d H:m:i'));
                    $this->fields[$keyName]['value'] = $this->fields[$keyName]['valueIntl'] = $fieldValue;
                } elseif (Component\Extension\Core\Type\CheckboxType::class === $fieldType || is_bool($fieldValue)) {
                    $setField = true;
                    $fieldValue = $fieldValue ? 'true' : 'false';
                } elseif (!is_object($fieldValue) && !empty($this->configurations[$keyName])) {
                    $setField = true;
                } elseif (EntityType::class === $fieldType) {
                    $setField = true;
                    if ($fieldValue && Form\Calendar::class === $fieldConfiguration->getClassName() && $fieldValue->getId()) {
                        $contact->setCalendar($this->coreLocator->em()->getRepository(Form\Calendar::class)->find($fieldValue->getId()));
                    }
                    if ($fieldValue instanceof ArrayCollection) {
                        $collectionValues = '';
                        foreach ($fieldValue as $item) {
                            $collectionValues .= ', '.$item->getAdminName();
                        }
                        $fieldValue = trim($collectionValues, ', ');
                    } else {
                        $fieldValue = method_exists($fieldValue, 'getAdminName') ? $fieldValue->getAdminName() : $fieldValue->getId();
                    }
                } elseif (is_object($fieldValue) && method_exists($fieldValue, 'getAdminName')) {
                    $setField = true;
                    $fieldValue = $fieldValue->getAdminName();
                }
                if ($setField) {
                    $value->setLabel($field['label']);
                    $value->setValue((string) $fieldValue);
                    $value->setType($fieldType);
                    $value->setConfiguration($this->configurations[$keyName]);
                    if ($originalValue instanceof \DateTime) {
                        $value->setDate($originalValue);
                    }
                    $contact->addContactValue($value);
                }
            }

            if ($form->getConfiguration()->isDbRegistration()) {
                $this->coreLocator->em()->persist($form);
                $this->coreLocator->em()->flush();
            }

            return $contact;
        }

        return null;
    }

    /**
     * Check if registration is not close.
     *
     * @throws Exception
     */
    private function checkRegistration(Form\StepForm|Form\Form $form): bool
    {
        $configuration = $form->getConfiguration();
        $maxShipments = $configuration->getMaxShipments();
        if ($configuration->getPublicationEnd() && new \DateTime('now', new \DateTimeZone('Europe/Paris')) > $configuration->getPublicationEnd()) {
            return false;
        }
        if ($maxShipments && $form->getContacts()->count() >= $maxShipments) {
            return false;
        }

        return true;
    }

    /**
     * Set attachments.
     */
    private function setAttachments(Form\StepForm|Form\Form $form, Form\ContactForm|Form\ContactStepForm|null $contact = null): void
    {
        $flushContact = false;

        foreach ($this->fields as $keyName => $field) {
            $isFilesArray = is_array($field['value']) && !empty($field['value'][0]) && $field['value'][0] instanceof UploadedFile;
            $fieldValue = is_array($field['value']) ? json_encode($field['value']) : $field['value'];
            if ($fieldValue instanceof UploadedFile) {
                $flushContact = $this->uploadedFile($field, $keyName, $form, $fieldValue, $contact);
            } elseif ($isFilesArray) {
                foreach ($field['value'] as $file) {
                    if ($file instanceof UploadedFile) {
                        $flushContact = $this->uploadedFile($field, $keyName, $form, $file, $contact);
                    }
                }
                unset($this->fields[$keyName]);
            }
        }

        if ($flushContact) {
            $this->coreLocator->em()->persist($contact);
            $this->coreLocator->em()->flush();
        }
    }

    /**
     * To upload File.
     */
    private function uploadedFile(array $field, string $keyName, Form\StepForm|Form\Form $form, UploadedFile $fieldValue, Form\ContactForm|Form\ContactStepForm|null $contact = null): bool
    {
        $flushContact = false;

        if ($contact) {
            /** @var UploadedFile $fieldValue */
            $formType = $form instanceof Form\Form ? 'forms' : 'steps-forms';
            $publicDirname = '/public/uploads/emails/'.$formType.'/'.$form->getId().'/contacts/'.$contact->getId();
            $fileDirname = $this->coreLocator->projectDir().$publicDirname;
            $fileDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileDirname);
            $extension = $fieldValue->getClientOriginalExtension();
            $filename = Urlizer::urlize(str_replace('.'.$extension, '', $fieldValue->getClientOriginalName())).'.'.$extension;
            $fieldValue->move($fileDirname, $filename);
            $this->attachments[] = $fileDirname.'/'.$filename;
            $value = new Form\ContactValue();
            $value->setLabel($field['label']);
            $value->setValue(str_replace('/public', '', $publicDirname).'/'.$filename);
            $value->setConfiguration($this->configurations[$keyName]);
            $contact->addContactValue($value);
            $flushContact = true;
        }

        return $flushContact;
    }

    /**
     * Send email.
     *
     * @throws ExceptionInterface|Exception
     */
    private function sendEmail(WebsiteModel $website, Form\StepForm|Form\Form $form, mixed $intl): void
    {
        $filesystem = new Filesystem();
        $frontTemplate = $website->configuration->template;
        $formReceivers = array_merge($this->receivers, $form->getConfiguration()->getReceivingEmails());
        $templateEmailDirname = $this->coreLocator->projectDir().'/templates/front/'.$frontTemplate.'/actions/form/email/'.$form->getSlug().'.html.twig';
        $templateEmailDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $templateEmailDirname);
        $templateEmail = $filesystem->exists($templateEmailDirname)
            ? 'front/'.$frontTemplate.'/actions/form/email/'.$form->getSlug().'.html.twig'
            : 'front/'.$frontTemplate.'/actions/form/email/default.html.twig';

        $receivers = [];
        foreach ($formReceivers as $receiver) {
            $receivers = array_merge($receivers, explode(',', $receiver));
        }

        if ($receivers) {
            $mailer = self::MESSENGER ? new SendEmail() : $this->mailer;
            $mailer->setLocale($this->coreLocator->locale());
            $mailer->setSubject($intl->subject);
            $mailer->setTo($receivers);
            $mailer->setName($website->companyName);
            $mailer->setFrom($form->getConfiguration()->getSendingEmail());
            $mailer->setReplyTo($this->sender);
            $mailer->setWebsite($website);
            if ($intl->webmasterEmail) {
                $mailer->setTemplate('front/'.$frontTemplate.'/actions/form/email/default-confirmation.html.twig');
                $mailer->setArguments(['message' => $this->setMessage($website, $intl->webmasterEmail)]);
            } else {
                $mailer->setTemplate($templateEmail);
                $mailer->setArguments(['fields' => $this->fields, 'classname' => get_class($form), 'entityId' => $form->getId()]);
            }
            if ($form->getConfiguration()->isAttachmentsInMail()) {
                $mailer->setAttachments($this->attachments);
            }
            if (self::MESSENGER) {
                $this->bus->dispatch($mailer);
                $this->messengerWorkerService->workerInBackground();
            } else {
                $rsp = $mailer->send();
                if (!$rsp->success) {
                    $this->error = $rsp->message;
                }
            }
        }
    }

    /**
     * To send email confirmation.
     *
     * @throws ExceptionInterface|Exception
     */
    private function sendConfirm(WebsiteModel $website, Form\StepForm|Form\Form $form, mixed $intl): void
    {
        if (strlen(strip_tags($intl->confirmation)) > 0) {
            $mailer = self::MESSENGER ? new SendEmail() : $this->mailer;
            $filesystem = new Filesystem();
            $frontTemplate = $website->configuration->template;
            $templateEmailDirname = $this->coreLocator->projectDir().'/templates/front/'.$frontTemplate.'/actions/form/email/'.$form->getSlug().'-confirmation.html.twig';
            $templateEmail = $filesystem->exists($templateEmailDirname)
                ? 'front/'.$frontTemplate.'/actions/form/email/'.$form->getSlug().'-confirmation.html.twig'
                : 'front/'.$frontTemplate.'/actions/form/email/default-confirmation.html.twig';
            $mailer->setLocale($this->coreLocator->locale());
            $mailer->setSubject($intl->confirmationSubject);
            $mailer->setTo([$this->sender]);
            $mailer->setName($website->companyName);
            $mailer->setFrom($form->getConfiguration()->getSendingEmail());
            $mailer->setReplyTo($form->getConfiguration()->getSendingEmail());
            $mailer->setTemplate($templateEmail);
            $mailer->setArguments(['message' => $this->setMessage($website, $intl->confirmation)]);
            $mailer->setWebsite($website);
            if (self::MESSENGER) {
                $this->bus->dispatch($mailer);
                $this->messengerWorkerService->workerInBackground();
            } else {
                $rsp = $mailer->send();
                if (!$rsp->success) {
                    $this->error = $rsp->message;
                }
            }
        }
    }

    /**
     * Get field value.
     */
    private function setMessage(WebsiteModel $website, ?string $message = null): ?string
    {
        if ($message) {
            foreach ($this->fields as $field) {
                if (!empty($field['slug']) && str_contains($message, '%'.$field['slug'].'%') && is_string($field['valueIntl'])) {
                    $field['valueIntl'] = 'lastname' === $field['slug'] ? strtoupper($field['valueIntl']) : $field['valueIntl'];
                    $message = str_replace('%'.$field['slug'].'%', $field['valueIntl'], $message);
                }
            }
            if ($website->companyName && str_contains($message, '%companyName%')) {
                $message = str_replace('%companyName%', $website->companyName, $message);
            }
        }

        return $message;
    }
}
