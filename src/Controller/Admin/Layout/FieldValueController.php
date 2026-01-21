<?php

declare(strict_types=1);

namespace App\Controller\Admin\Layout;

use App\Controller\Admin\AdminController;
use App\Entity\Layout\FieldValue;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * FieldValueController.
 *
 * Layout FieldValue management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_FORM')]
#[Route('/admin-%security_token%/{website}/layouts/fields/values', schemes: '%protocol%')]
class FieldValueController extends AdminController
{
    protected ?string $class = FieldValue::class;

    /**
     * Delete FieldValue.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{fieldvalue}', name: 'admin_fieldvalue_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        /** @var FieldValue $fieldValue */
        $fieldValue = $this->coreLocator->em()->getRepository($this->class)->find($request->get('fieldvalue'));
        $this->entities = $fieldValue ? $fieldValue->getConfiguration()->getFieldValues() : [];
        return parent::delete($request);
    }

    /**
     * Position FieldValue.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{fieldvalue}', name: 'admin_fieldvalue_position', methods: 'GET')]
    public function position(Request $request)
    {
        return parent::position($request);
    }
}
