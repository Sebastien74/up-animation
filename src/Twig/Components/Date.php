<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Date.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsLiveComponent]
class Date
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?\DateTime $date = null;

    #[LiveProp]
    public ?\DateTime $startDate = null;

    #[LiveProp]
    public ?\DateTime $endDate = null;

    #[LiveProp]
    public ?string $formatDate = 'dd MMM Y';
    #[LiveProp]
    public ?bool $disabledHours = false;

    #[LiveProp]
    public ?bool $between = false;

    #[LiveProp]
    public ?bool $strict = false;

    #[LiveProp]
    public ?bool $prefix = true;

    #[LiveProp]
    public ?bool $asHours = false;

    #[LiveProp]
    public ?string $class = null;

    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    public function localize(): array|bool
    {
        if (!$this->date) {
            return false;
        }

        if (!$this->formatDate) {
            $this->formatDate = 'dd MMM y';
        }

        if ($this->between && !$this->startDate) {
            $this->startDate = $this->date;
        }

        return [
            'date' => $this->date,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'formatDate' => str_replace('Y', 'y', $this->formatDate),
            'disabledHours' => $this->disabledHours,
            'between' => $this->between,
            'strict' => $this->strict,
            'prefix' => $this->prefix,
            'asHours' => $this->asHours,
            'class' => $this->class,
        ];
    }
}
