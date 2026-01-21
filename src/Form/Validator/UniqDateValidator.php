<?php

declare(strict_types=1);

namespace App\Form\Validator;

use App\Service\Core\InterfaceHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * UniqDateValidator.
 *
 * Check if URL already exist
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UniqDateValidator extends ConstraintValidator
{
    private ?Request $request;

    /**
     * UniqDateValidator constructor.
     */
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $entityManager,
        private readonly InterfaceHelper $interfaceHelper,
        private readonly RequestStack $requestStack)
    {
        $this->request = $this->requestStack->getMainRequest();
    }

    /**
     * Validate.
     *
     * @throws NonUniqueResultException
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($value instanceof \DateTime) {
            /** @var $form Form */
            $form = $this->context->getRoot();
            $parentEntity = is_object($form) && method_exists($form, 'getNormData') ? $form->getNormData() : null;

            if ($parentEntity) {
                $interface = is_object($parentEntity) ? $this->interfaceHelper->generate(get_class($parentEntity)) : [];
                $masterField = !empty($interface['masterField']) ? $interface['masterField']
                    : (is_object($parentEntity) && method_exists($parentEntity, 'getWebsite') ? 'website' : null);
                $masterFieldId = $masterField && $this->request->get($masterField) ? $this->request->get($masterField) : null;
                $existingStart = $this->existing($value, $parentEntity, $masterField, $masterFieldId);
                $existingEnd = $this->existing($value, $parentEntity, $masterField, $masterFieldId);

                if ($existingStart || $existingEnd) {
                    $this->context->buildViolation(rtrim($this->translator->trans('Cette date existe déjà !!', [], 'validators_cms'), '<br/>'))
                        ->addViolation();
                }
            }
        }
    }

    /**
     * Check if existing.
     *
     * @throws NonUniqueResultException
     */
    private function existing(\DateTime $date, mixed $parentEntity = null, $masterField = null, $masterFieldId = null): bool
    {
        $repository = $this->entityManager->getRepository(get_class($parentEntity));

        $queryBuilder = $repository->createQueryBuilder('e');
        $queryBuilder->andWhere('e.publicationStart BETWEEN :start AND :end');
        $queryBuilder->setParameter('start', $date->format('Y-m-d').' 00:00:00');
        $queryBuilder->setParameter('end', $date->format('Y-m-d').' 23:23:59');

        if ($masterField && $masterFieldId) {
            $queryBuilder->andWhere('e.'.$masterField.'= :masterFiled');
            $queryBuilder->setParameter('masterFiled', $masterFieldId);
        }

        $existing = $queryBuilder->getQuery()->getOneOrNullResult();

        if ($existing && $existing->getId() !== $parentEntity->getId()) {
            return true;
        }

        return false;
    }
}
