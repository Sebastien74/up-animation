<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Entity\Module\Form\ContactForm;
use App\Entity\Module\Form\ContactStepForm;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * FormRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FormRuntime implements RuntimeExtensionInterface
{
    /**
     * Get contact values send.
     */
    public function contactValues(ContactForm|ContactStepForm|null $contact = null): array
    {
        $fields = [];
        if ($contact && method_exists($contact, 'getContactValues')) {
            foreach ($contact->getContactValues() as $value) {
                $configuration = $value->getConfiguration();
                if ($configuration && $configuration->getSlug()) {
                    $fields[$value->getConfiguration()->getSlug()] = $value;
                }
            }
        }

        return $fields;
    }
}
