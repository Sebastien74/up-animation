<?php

declare(strict_types=1);

namespace App\Form\Type\Development;

use App\Service\Development\CommandParser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * CommandChoiceType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CommandChoiceType extends AbstractType
{
    /**
     * CommandChoiceType constructor.
     */
    public function __construct(private readonly CommandParser $commandParser)
    {
    }

    /**
     * @throws \Exception
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => $this->commandParser->getCommands(),
        ]);
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
