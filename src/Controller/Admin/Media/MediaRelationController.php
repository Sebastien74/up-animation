<?php

declare(strict_types=1);

namespace App\Controller\Admin\Media;

use App\Controller\Admin\AdminController;
use App\Entity\Media\Media;
use App\Entity\Media\MediaRelation;
use App\Entity\Module\Slider\Slider;
use App\Form\Manager\Media\MediaManager;
use App\Form\Widget\MediaRelationType;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\PersistentCollection;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * MediaRelationController.
 *
 * Media MediaRelation management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_MEDIA')]
#[Route('/admin-%security_token%/{website}/medias/relations', schemes: '%protocol%')]
class MediaRelationController extends AdminController
{
    protected ?string $class = MediaRelation::class;
    protected ?string $formType = MediaRelationType::class;

    /**
     * Reset Media null on MediaRelation (Use on dropify delete action).
     */
    #[IsGranted('ROLE_DELETE')]
    #[Route('/reset-media', name: 'admin_mediarelation_reset_media', options: ['expose' => true], methods: 'DELETE')]
    public function resetMedia(Request $request): JsonResponse
    {
        $mediaRelation = $this->coreLocator->em()->getRepository(urldecode($request->get('mediaClassname')))->find($request->get('mediaRelationId'));

        if ($mediaRelation) {
            $media = $mediaRelation->getMedia();
            if ($media instanceof Media) {
                $exist = false;
                foreach ($media->getMediaScreens() as $mediaScreen) {
                    if ($mediaScreen->getFilename()) {
                        $exist = true;
                        break;
                    }
                }
                $media->setHaveMediaScreens($exist);
                $this->coreLocator->em()->persist($media);
            }
            if ($media instanceof Media && 'poster' === $media->getScreen()) {
                $media->setName(null);
                $media->setFilename(null);
                $media->setExtension(null);
                $media->setCopyright(null);
                $this->coreLocator->em()->persist($media);
            } else {
                $media = new Media();
                $media->setWebsite($this->getWebsite()->entity);
                $mediaRelation->setMedia($media);
                $this->coreLocator->em()->persist($media);
                $this->coreLocator->em()->persist($mediaRelation);
            }
            $this->coreLocator->em()->flush();
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * Edit Locales MediaRelation.
     *
     * @throws Exception
     */
    #[IsGranted('ROLE_EDIT')]
    #[Route('/edit/locales', name: 'admin_mediarelations_edit', options: ['expose' => true], methods: 'GET|POST')]
    public function relationsEdit(
        Request $request,
        MediaManager $mediaManager,
    ): JsonResponse {
        $website = $this->getWebsite();
        $classname = urldecode($request->get('entityNamespace'));
        $interface = $this->getInterface($classname);
        $repository = $this->coreLocator->em()->getRepository($classname);
        $entity = $repository->find($request->get('entityId'));

        if (!$entity) {
            throw $this->createNotFoundException($this->coreLocator->translator()->trans("Ce média n'existe pas !!", [], 'front'));
        }

        $this->coreLocator->em()->refresh($entity);
        $metadata = $this->coreLocator->metadata($entity, 'mediaRelations');
        $mediaRelation = $this->coreLocator->em()->getRepository($metadata->targetEntity)->find($request->get('mediaRelation'));
        $mediaRelations = $this->coreLocator->em()->getRepository($metadata->targetEntity)
            ->createQueryBuilder('mr')->select('mr')
            ->andWhere('mr.'.$metadata->mappedBy.' = :mappedBy')
            ->andWhere('mr.position = :position')
            ->setParameter('position', $mediaRelation->getPosition())
            ->setParameter('mappedBy', $entity)
            ->orderBy('mr.locale', 'ASC')
            ->getQuery()
            ->getResult();

        $mediaManager->synchronizeLocales($website->entity, $interface, $entity, $mediaRelation);

        return new JsonResponse(['html' => $this->adminRender('admin/core/form/media-relations-multi.html.twig', [
            'mediaRelations' => $mediaRelations,
            'screen' => $mediaRelation->getMedia()->getScreen(),
            'entityNamespace' => $request->get('entityNamespace'),
            'referEntityId' => $entity->getId(),
            'formOptions' => $request->get('formOptions'),
        ], $request)]);
    }

    /**
     * Edit MediaRelation.
     *
     * {@inheritdoc}
     *
     * @throws Exception
     */
    #[Route('/edit/{mediarelation}', name: 'admin_mediarelation_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        $referClassname = $request->get('entityNamespace') ? urldecode($request->get('entityNamespace')) : null;
        $referClass = $referClassname ? new $referClassname() : null;
        $referEntityId = $request->get('referEntityId') ? $request->get('referEntityId') : null;
        $mediaRelationData = $referClassname ? $this->coreLocator->metadata(new $referClassname(), 'mediaRelations') : null;
        $this->entity = $mediaRelationData ? $this->coreLocator->em()->getRepository($mediaRelationData->targetEntity)->find($request->get('mediarelation')) : null;
        $formOptions = $request->get('formOptions') ? (array) json_decode($request->get('formOptions')) : [];
        $excludesFields = isset($formOptions['excludes_fields']) ? (array) $formOptions['excludes_fields'] : [];
        $screen = $request->get('screen') ? $request->get('screen') : null;
        $asVideo = 'poster' === $screen;
        $this->arguments['mediaRelationClassname'] = $this->class = get_class($this->entity);
        $this->arguments['entityNamespace'] = $request->get('entityNamespace');
        $this->arguments['referEntityId'] = $request->get('referEntityId');
        $this->arguments['screen'] = $screen;
        $this->arguments['customOptions'] = $request->get('formOptions');
        $this->arguments['intlCardTitle'] = $formOptions['intlCardTitle'] ?? true;
        $this->arguments['linkCardTitle'] = $formOptions['linkCardTitle'] ?? true;

        if (!$this->entity) {
            throw $this->createNotFoundException($this->coreLocator->translator()->trans("Ce média n'existe pas !!", [], 'front'));
        }

        $this->template = 'poster' === $this->arguments['screen'] ? 'admin/core/form/video.html.twig' : 'admin/core/form/media-relation-full.html.twig';
        if (!empty($formOptions['intls'])) {
            $intlFields = (array) $formOptions['intls'];
        } elseif (!empty($excludesFields['intl'][0]) && '*' === $excludesFields['intl'][0]) {
            $intlFields = [];
        } else {
            $intlFields = $asVideo ? ['title' => 'col-md-5', 'placeholder' => 'col-md-5', 'subTitle' => 'col-md-9', 'pictogram' => 'col-md-3', 'body', 'introduction', 'video', 'targetLink', 'targetLabel', 'targetPage', 'newTab' => 'col-md-6']
                : ['title' => 'col-md-5', 'placeholder' => 'col-md-5', 'subTitle' => 'col-md-9', 'pictogram' => 'col-md-3', 'body', 'introduction', 'targetLink', 'targetLabel' => 'col-md-4', 'targetPage' => 'col-md-4', 'targetStyle' => 'col-md-4', 'newTab' => 'col-md-4'];
        }
        $onlyMedia = $asVideo || (isset($formOptions['onlyMedia']) && $formOptions['onlyMedia']);
        $save = $asVideo || (isset($formOptions['save']) && $formOptions['save']);
        $copyright = !$asVideo && !isset($formOptions['copyright']) || (isset($formOptions['copyright']) && $formOptions['copyright']);
        $forceIntl = $asVideo || (isset($formOptions['forceIntl']) && $formOptions['forceIntl']);
        $this->formOptions = ['copyright' => $copyright, 'categories' => !$asVideo, 'video' => $asVideo, 'hideHover' => !$asVideo, 'onlyMedia' => $onlyMedia, 'forceIntl' => $forceIntl, 'active' => false, 'save' => $save,
            'fields' => ['submit', 'intl' => $intlFields],
            'excludes_fields' => $excludesFields,
            'form_name' => 'media_relation_'.$this->entity->getId(),
        ];
        if (Slider::class !== $referClassname && !$asVideo) {
            $this->formOptions['fields']['intl'] = isset($formOptions['intls']) ? $intlFields : ['title' => 'col-md-8', 'targetLink', 'targetLabel' => 'col-md-4', 'targetPage' => 'col-md-4', 'targetStyle' => 'col-md-4', 'newTab' => 'col-md-6'];
            $this->formOptions['intlTitleForce'] = false;
            $this->template = 'admin/core/form/media-relation-medium.html.twig';
        }
        if ($this->formOptions['forceIntl']) {
            $this->formOptions['intlTitleForce'] = true;
        }
        if (is_object($referClass) && method_exists($referClass, 'getLayout')) {
            $this->formOptions['header'] = true;
        }

        if ($request->isMethod('POST') && $referClassname && $referEntityId) {
            $parentEntity = $this->coreLocator->em()->getRepository($referClassname)->find($referEntityId);
            if (is_object($parentEntity) && method_exists($parentEntity, 'setUpdatedAt')) {
                $parentEntity->setUpdatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                $this->coreLocator->em()->persist($parentEntity);
            }
        }

        return parent::edit($request);
    }

    /**
     * Set MediaRelation position.
     */
    #[IsGranted('ROLE_EDIT')]
    #[Route('/position/{position}/{entityId}', name: 'admin_mediarelation_position', options: ['expose' => true], methods: 'GET')]
    public function relationPosition(Request $request, int $position, int $entityId): JsonResponse
    {
        $mediaRelationId = $request->get('mediaRelation');

        if ($entityId && $request->get('entityNamespace')) {
            $entity = $this->coreLocator->em()->getRepository(urldecode($request->get('entityNamespace')))->find($entityId);
            $metadata = $entity ? $this->coreLocator->metadata($entity, 'mediaRelations')->targetEntity : null;
            $mediaRelation = $metadata && $mediaRelationId ? $this->coreLocator->em()->getRepository($metadata)->find($mediaRelationId) : null;
            if ($mediaRelation) {
                $mediaRelation->setPosition($position);
                $this->coreLocator->em()->persist($mediaRelation);
                $this->coreLocator->em()->flush();
            }
            if (is_object($entity)) {
                $this->coreLocator->cacheService()->clearCaches($entity, true);
            }
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * Delete MediaRelation.
     *
     * @throws NonUniqueResultException
     */
    #[IsGranted('ROLE_DELETE')]
    #[Route('/relation/delete/{entityId}', name: 'admin_mediarelation_delete', methods: 'DELETE')]
    public function relationDelete(Request $request, int $entityId): JsonResponse
    {
        $classname = urldecode($request->get('entityNamespace'));
        $metadata = $this->coreLocator->metadata(new $classname(), 'mediaRelations');
        $mediaRelation = $this->coreLocator->em()->getRepository($metadata->targetEntity)->find($request->get('mediaRelation'));
        $positionToRemove = $mediaRelation->getPosition();
        $localesRelations = $this->getAllLocalesRelations($classname, $entityId);
        if ($localesRelations) {
            foreach ($localesRelations as $relation) {
                if ($relation->getPosition() === $positionToRemove) {
                    $this->coreLocator->em()->remove($relation);
                } elseif ($relation->getPosition() > $positionToRemove) {
                    $relation->setPosition($relation->getPosition() - 1);
                }
            }
            $this->coreLocator->em()->flush();
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * Get locales relations.
     *
     * @throws NonUniqueResultException
     */
    private function getAllLocalesRelations(string $classname, int $entityId): PersistentCollection|array
    {
        $repository = $this->coreLocator->em()->getRepository($classname);
        $entity = $repository->createQueryBuilder('e')->select('e')
            ->leftJoin('e.mediaRelations', 'm')
            ->andWhere('e.id = :id')
            ->setParameter('id', $entityId)
            ->addSelect('m')
            ->getQuery()
            ->getOneOrNullResult();

        return $entity ? $entity->getMediaRelations() : [];
    }
}
