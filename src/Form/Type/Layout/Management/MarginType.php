<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Management;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * MarginType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MarginType
{
    private TranslatorInterface $translator;

    /**
     * MarginType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    /**
     * Add field.
     */
    public function add(FormBuilderInterface $builder, array $options = []): void
    {
        $fields = [];
        $fields['marginTop'] = ['sizes' => ['type' => 'm', 'position' => 't'], 'group' => 'col-md-6 mb-md-0'];
        $fields['marginRight'] = ['sizes' => ['type' => 'm', 'position' => 'e'], 'group' => 'col-md-6 mb-md-0'];
        $fields['marginBottom'] = ['sizes' => ['type' => 'm', 'position' => 'b'], 'group' => 'col-md-6 mb-md-0'];
        $fields['marginLeft'] = ['sizes' => ['type' => 'm', 'position' => 's'], 'group' => 'col-md-6 mb-md-0'];
        $fields['paddingTop'] = ['sizes' => ['type' => 'p', 'position' => 't'], 'group' => 'col-md-3 disable-asterisk'];
        $fields['paddingRight'] = ['sizes' => ['type' => 'p', 'position' => 'e'], 'group' => 'col-md-3 disable-asterisk'];
        $fields['paddingBottom'] = ['sizes' => ['type' => 'p', 'position' => 'b'], 'group' => 'col-md-3 disable-asterisk'];
        $fields['paddingLeft'] = ['sizes' => ['type' => 'p', 'position' => 's'], 'group' => 'col-md-3 disable-asterisk'];

        foreach (['', 'MiniPc', 'Tablet', 'Mobile'] as $screen) {
            foreach ($fields as $name => $config) {
                $builder->add($name.$screen, Type\ChoiceType::class, [
                    'required' => false,
                    'display' => 'search',
                    'placeholder' => $this->translator->trans('NULL', [], 'admin'),
                    'choices' => $this->getSizes($config['sizes']['type'], $config['sizes']['position']),
                    'label' => false,
                    'attr' => ['group' => $config['group'], 'class' => 'disable-search'],
                ]);
            }
        }
    }

    /**
     * Get padding sizes.
     */
    private function getSizes(string $type, string $position): array
    {
        $margins = [
            $this->translator->trans('0', [], 'admin') => $type.$position.'-0',
            'XS' => $type.$position.'-xs',
            'S' => $type.$position.'-sm',
            'M' => $type.$position.'-md',
            'L' => $type.$position.'-lg',
            'XL' => $type.$position.'-xl',
            'XXL' => $type.$position.'-xxl',
        ];

        if ('m' === $type) {
            $margins = array_merge($margins, [
                'NXS' => $type.$position.'-xs-neg',
                'NS' => $type.$position.'-sm-neg',
                'NM' => $type.$position.'-md-neg',
                'NL' => $type.$position.'-lg-neg',
                'NXL' => $type.$position.'-xl-neg',
                'NXXL' => $type.$position.'-xxl-neg',
            ]);
        }

        return $margins;
    }
}
