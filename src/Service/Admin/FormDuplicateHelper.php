<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Entity\Core\Website;
use App\Form\Type\Core\DefaultType;
use App\Repository\Core\WebsiteRepository;
use App\Service\Core\InterfaceHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FormDuplicateHelper.
 *
 * To manage admin form duplication
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => FormDuplicateHelper::class, 'key' => 'form_duplicate_helper'],
])]
class FormDuplicateHelper
{
    private ?Request $request;
    private array $interface = [];
    private Website $website;
    private object $entityToDuplicate;
    private object $entity;
    private ?FormInterface $form;
    private bool $isSubmitted;
    private bool $isValid;

    /**
     * FormDuplicateHelper constructor.
     */
    public function __construct(
        private readonly InterfaceHelper $interfaceHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly FormFactoryInterface $formFactory,
        private readonly WebsiteRepository $websiteRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Execute FormDuplicateHelper.
     *
     * @throws NonUniqueResultException
     */
    public function execute(Request $request, ?string $formType = null, ?string $classname = null, array $options = [], mixed $formManager = null): void
    {
        $this->request = $request;

        $this->setInterface($classname);
        $this->setWebsite();
        $this->setEntityToDuplicate($classname);
        $this->setEntity();
        $this->setForm($formType, $options);
        $this->submit($formManager);
    }

    /**
     * Set Interface.
     *
     * @throws NonUniqueResultException
     */
    public function setInterface(string $classname): void
    {
        $this->interface = $this->interfaceHelper->generate($classname);
    }

    /**
     * Get Interface.
     */
    public function getInterface(): array
    {
        return $this->interface;
    }

    /**
     * Set WebsiteModel.
     */
    public function setWebsite(): void
    {
        $this->website = $this->websiteRepository->find($this->request->get('website'));
    }

    /**
     * Get Entity to duplicate.
     */
    public function getEntityToDuplicate(): object
    {
        return $this->entityToDuplicate;
    }

    /**
     * Set Entity to duplicate.
     */
    public function setEntityToDuplicate($classname): void
    {
        $this->entityToDuplicate = $this->entityManager->getRepository($classname)->find($this->request->get($this->interface['name']));
    }

    /**
     * Get Entity.
     */
    public function getEntity(): object
    {
        return $this->entity;
    }

    /**
     * Set Entity.
     */
    public function setEntity(): void
    {
        $this->entity = new $this->interface['entity']();
    }

    /**
     * Get isSubmitted.
     */
    public function isSubmitted(): bool
    {
        return $this->isSubmitted;
    }

    /**
     * Set isSubmit.
     */
    public function setIsSubmitted(bool $isSubmitted): void
    {
        $this->isSubmitted = $isSubmitted;
    }

    /**
     * Get isValid.
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * Set isValid.
     */
    public function setIsValid(bool $isValid): void
    {
        $this->isValid = $isValid;
    }

    /**
     * Get Form.
     */
    public function getForm(): ?FormInterface
    {
        return $this->form;
    }

    /**
     * Set Form.
     */
    public function setForm(?string $formType = null, array $options = []): void
    {
        $formType = !empty($formType) ? $formType : DefaultType::class;
        $options['duplicate_entity'] = $this->entityToDuplicate;
        $this->form = $this->formFactory->create($formType, $this->entity, $options);
    }

    /**
     * Form submission process.
     */
    public function submit($formManager = null): void
    {
        $this->form->handleRequest($this->request);
        $this->setIsSubmitted(false);
        $this->setIsValid(false);
        if ($this->form->isSubmitted() && $this->form->isValid()) {
            if (!$formManager) {
                throw new HttpException(500, $this->translator->trans('Manager non renseigné !!', [], 'admin'));
            }
            if (method_exists($formManager, 'execute')) {
                $formManager->execute($this->form->getData(), $this->website, $this->form);
            } else {
                throw new HttpException(500, $this->translator->trans("La fonction execute() n'existe pas dans votre manager", [], 'admin'));
            }
            $this->setIsSubmitted(true);
            $this->setIsValid(true);
        } elseif ($this->form->isSubmitted() && !$this->form->isValid()) {
            $this->setIsSubmitted(true);
            $this->setIsValid(false);
        }
    }
}
