<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Entity\Module\Form\Calendar;
use App\Entity\Module\Form\Form;
use App\Repository\Module\Form\CalendarRepository;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FormCalendarsType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FormCalendarsType extends AbstractType
{
    private OptionsResolver $resolver;
    private ?CalendarRepository $repository = null;
    private TranslatorInterface $translator;

    /**
     * FormCalendarsType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $this->resolver = $resolver;

        $resolver->setDefaults([
            'label' => false,
            'required' => false,
            'form' => null,
            'display' => 'search',
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'class' => Calendar::class,
            'query_builder' => function (CalendarRepository $repository) {
                $this->repository = $repository;
                $this->resolver->setNormalizer('form', function (Options $options, Form $form) {
                    return $this->repository->createQueryBuilder('c')
                        ->leftJoin('c.form', 'f')
                        ->andWhere('c.form = :form')
                        ->setParameter('form', $form)
                        ->orderBy('c.position', 'ASC')
                        ->addSelect('c');
                });
            },
            'choice_label' => 'adminName',
        ]);

        $this->resolver->setNormalizer('form', function (Options $options, Form $form) {
            if (1 === $form->getCalendars()->count()) {
                $this->resolver->setDefaults([
                    'attr' => ['group' => 'd-none'],
                ]);
            }
        });
    }

    public function getParent(): ?string
    {
        return EntityType::class;
    }
}
