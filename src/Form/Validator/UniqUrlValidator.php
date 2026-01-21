<?php

declare(strict_types=1);

namespace App\Form\Validator;

use App\Entity\Seo as SeoEntities;
use App\Form\Manager\Seo\UrlManager;
use App\Repository\Core\WebsiteRepository;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * UniqUrlValidator.
 *
 * Check if URL already exist
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UniqUrlValidator extends ConstraintValidator
{
    /**
     * UniqUrlValidator constructor.
     */
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly UrlManager $urlManager,
        private readonly WebsiteRepository $websiteRepository,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Validate.
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        /** @var $form Form */
        $form = $this->context->getRoot();
        $parentEntity = method_exists($form, 'getNormData') ? $form->getNormData() : null;
        $urlPost = method_exists($this->context->getObject()->getParent(), 'getNormData') ? $this->context->getObject()->getParent()->getNormData() : null;

        if ($urlPost instanceof SeoEntities\Url && $parentEntity && $value) {
            $existingUrl = true;
            $session = new Session();
            try {
                $request = $this->requestStack->getMainRequest();
                $website = $this->websiteRepository->find(intval($request->get('website')));
                $existingUrl = $this->urlManager->getExistingUrl($urlPost, $website, $parentEntity);
            } catch (\Exception $exception) {
                $session->getFlashBag()->add('error', $exception->getMessage());
            }
            if (is_bool($existingUrl) && $existingUrl || $existingUrl && $urlPost->getId() !== $existingUrl->getId()) {
                if ($existingUrl instanceof SeoEntities\Url && $existingUrl->isArchived()) {
                    $this->context->buildViolation(rtrim($this->translator->trans('Une URL archivée existe déjà !!', [], 'validators_cms'), '<br/>'))
                        ->addViolation();
                } else {
                    $this->context->buildViolation(rtrim($this->translator->trans('Cette URL existe déjà !!', [], 'validators_cms'), '<br/>'))
                        ->addViolation();
                }
            }
        }
    }
}
