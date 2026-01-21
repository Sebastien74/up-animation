<?php

declare(strict_types=1);

namespace App\Controller\Admin\Media;

use App\Controller\Admin\AdminController;
use App\Entity\Media\ThumbAction;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ThumbActionController.
 *
 * Media ThumbAction management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/medias/thumbs-actions', schemes: '%protocol%')]
class ThumbActionController extends AdminController
{
    protected ?string $class = ThumbAction::class;

    /**
     * Delete ThumbAction.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{thumbaction}', name: 'admin_thumbaction_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }
}
