<?php

declare(strict_types=1);

namespace App\Form\Validator;

use App\Entity\Seo as SeoEntities;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * UniqOldRedirectionValidator.
 *
 * Check if old Redirection URL already exist
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UniqOldRedirectionValidator extends ConstraintValidator
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;

    /**
     * UniqUrlValidator constructor.
     */
    public function __construct(TranslatorInterface $translator, EntityManagerInterface $entityManager)
    {
        $this->translator = $translator;
        $this->entityManager = $entityManager;
    }

    /**
     * Validate.
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        /** @var $form Form */
        $form = $this->context->getRoot();
        $website = $form->getConfig()->getOption('website');

        $redirection = $form->getData() instanceof SeoEntities\Redirection ? $form->getData() : null;
        $redirections = !empty($_POST['website_redirection']['redirections']) ? $_POST['website_redirection']['redirections'] : null;
        if (!$redirection && $redirections) {
            $matches = explode('.children[', $this->context->getPropertyPath());
            $key = !empty($matches[1]) && str_contains($matches[1], ']') ? str_replace(']', '', $matches[1]) : null;
            $postRedirection = !empty($redirections[$key]) ? $redirections[$key] : null;
            if ($postRedirection) {
                $redirection = new SeoEntities\Redirection();
                $redirection->setLocale($postRedirection['locale']);
                $redirection->setOld($postRedirection['old']);
                $redirection->setNew($postRedirection['new']);
            }
        }

        /* To check if new is different than new */
        if ($redirection instanceof SeoEntities\Redirection && $redirection->getOld() === $redirection->getNew()) {
            $this->context->buildViolation(rtrim($this->translator->trans('Vos URL sont identiques !!', [], 'validators_cms'), '<br/>'))
                ->addViolation();
        }

        /* To check if already exist */
        if ($redirection instanceof SeoEntities\Redirection && str_contains($value, 'http') || !$redirection) {
            $existing = $this->entityManager->getRepository(SeoEntities\Redirection::class)->findOneBy([
                'website' => $website,
                'old' => $value,
            ]);
        } else {
            $existing = $this->entityManager->getRepository(SeoEntities\Redirection::class)->findOneBy([
                'website' => $website,
                'locale' => $redirection->getLocale(),
                'old' => $redirection->getOld(),
            ]);
        }

        if ((!$redirection && $existing) || ($existing instanceof SeoEntities\Redirection && $existing->getId() !== $redirection->getId())) {
            $this->context->buildViolation(rtrim($this->translator->trans('Cette URL existe déjà !!', [], 'validators_cms'), '<br/>'))
                ->addViolation();
        }
    }
}
