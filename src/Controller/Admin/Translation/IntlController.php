<?php

declare(strict_types=1);

namespace App\Controller\Admin\Translation;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Website;
use App\Form\Type\Translation\ImportType;
use App\Service\Translation\ExportService;
use App\Service\Translation\ImportService;
use Doctrine\ORM\Mapping\MappingException;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * IntlController.
 *
 * Translation management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/translations/intls', schemes: '%protocol%')]
class IntlController extends AdminController
{
    /**
     * Export translations.
     *
     * @throws Exception|MappingException|\PhpOffice\PhpSpreadsheet\Exception
     */
    #[Route('/export', name: 'admin_intls_export', methods: 'GET')]
    public function exportation(Request $request, Website $website, ExportService $exportService): Response
    {
        $exportService->execute($website);
        $zipName = $exportService->zip();

        if (!$zipName) {
            $session = new Session();
            $session->getFlashBag()->add('info', $this->coreLocator->translator()->trans("Vous n'avez aucun contenu à traduire.", [], 'admin'));

            return $this->redirect($request->headers->get('referer'));
        }

        $response = new Response(file_get_contents($zipName));
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', 'attachment;filename="'.$zipName.'"');
        $response->headers->set('Content-length', strval(filesize($zipName)));

        @unlink($zipName);

        return $response;
    }

    /**
     * Import translations.
     *
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    #[Route('/import', name: 'admin_intls_import', methods: 'GET|POST')]
    public function import(Request $request, ImportService $importService, Website $website): RedirectResponse|Response
    {
        $form = $this->createForm(ImportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !empty($form->getData()['files'])) {
            $importService->execute($form->getData()['files']);
            $session = new Session();
            $session->getFlashBag()->add('success', $this->coreLocator->translator()->trans('Importation réussie.', [], 'admin'));

            return $this->redirect($request->headers->get('referer'));
        }

        return $this->render('admin/page/translation/import.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
