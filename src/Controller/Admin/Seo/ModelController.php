<?php

declare(strict_types=1);

namespace App\Controller\Admin\Seo;

use App\Entity\Module\Catalog\Catalog;
use App\Entity\Module\Catalog\Feature;
use App\Entity\Module\Catalog\Product;
use App\Entity\Seo\Model;
use App\Form\Type\Seo\ModelType;
use App\Service\Content\SeoInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ModelController.
 *
 * SEO Model management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_SEO')]
#[Route('/admin-%security_token%/{website}/seo/models', schemes: '%protocol%')]
class ModelController extends BaseController
{
    /** @var Model entity */
    protected mixed $entity;
    protected ?string $class = Model::class;
    protected ?string $formType = ModelType::class;

    /**
     * Edit Seo Model.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{model}/{entitylocale}', name: 'admin_seo_model_edit', methods: 'GET|POST')]
    public function editModel(Request $request, SeoInterface $seoInterface)
    {
        $this->entity = $this->coreLocator->em()->getRepository(Model::class)->find($request->get('model'));
        if (!$this->entity) {
            throw $this->createNotFoundException($this->coreLocator->translator()->trans("Ce modèle n'existe pas !!", [], 'admin'));
        }

        $website = $this->getWebsite();
        $this->getKeyWords($request);
        $this->template = 'admin/page/seo/edit-model.html.twig';
        $this->getEntities($request, $website->entity, $seoInterface);
        $this->setPagesError();
        $this->arguments['currentCategory'] = 'models';
        $this->arguments['currentUrl'] = null;

        return parent::edit($request);
    }

    /**
     * Get keywords.
     */
    private function getKeyWords(Request $request): void
    {
        $metadata = $this->coreLocator->em()->getClassMetadata($this->entity->getClassName());
        $classname = $metadata->getName();
        $entity = new $classname();
        $interface = $this->getInterface($classname);
        $this->arguments['modelInterface'] = $interface;

        $childInterface = [];
        $haveChildAndConfiguration = false;
        if ($this->entity->getEntityId()) {
            $childInterface = $this->getInterface($this->entity->getChildClassName());
            if (!empty($childInterface['seo']) && !empty($childInterface['name'])) {
                $haveChildAndConfiguration = true;
            }
        }

        if ($haveChildAndConfiguration) {
            $childEntityLabel = $this->coreLocator->translator()->trans('singular', [], 'entity_'.$childInterface['name'])
                ? $this->coreLocator->translator()->trans('singular', [], 'entity_'.$childInterface['name']) : $entity->getChildClassName();
            foreach ($childInterface['seo'] as $keyword) {
                $this->arguments['keywords'][$childEntityLabel][] = $childInterface['name'].'.'.$keyword;
            }
            $entityKeywords = !empty($interface['seo']) ? $interface['seo'] : $childInterface['seo'];
            $entityLabel = $this->coreLocator->translator()->trans('singular', [], 'entity_'.$childInterface['name'])
                ? $this->coreLocator->translator()->trans('singular', [], 'entity_'.$interface['name']) : $entity->getClassName();
            foreach ($entityKeywords as $keyword) {
                $this->arguments['keywords'][$entityLabel][] = str_replace($childInterface['name'], '', $interface['name']).'.'.$keyword;
            }
            $this->arguments['haveCaption'] = false;
        } elseif (!empty($interface['seo'])) {
            $this->arguments['labels'] = method_exists($entity, 'getLabels') && !empty($entity::getLabels())
                ? $entity::getLabels() : null;
            $entityLabel = $this->coreLocator->translator()->trans('singular', [], 'entity_'.$interface['name'])
                ? $this->coreLocator->translator()->trans('singular', [], 'entity_'.$interface['name']) : 'models';
            $this->arguments['keywords'][$entityLabel] = $interface['seo'];
            $this->arguments['haveCaption'] = true;
        } else {
            $explodeEntity = explode('\\', $classname);
            $keywords[end($explodeEntity)] = $metadata->getFieldNames();
            $fieldsMapping = $metadata->getAssociationNames();
            foreach ($fieldsMapping as $key => $field) {
                $subEntity = $metadata->getAssociationTargetClass($field);
                $subEntityFields = $this->coreLocator->em()->getClassMetadata($subEntity)->getFieldNames();
                foreach ($subEntityFields as $subEntityKey => $subEntityField) {
                    $subEntityFields[$subEntityKey] = $field.'.'.$subEntityField;
                }
                $keywords[$field] = $subEntityFields;
            }
            $this->arguments['keywords'] = $keywords;
            $this->arguments['haveCaption'] = false;
        }

        if (Product::class === $this->entity->getClassName() || Catalog::class === $this->entity->getClassName()) {
            $featuresLabel = $this->coreLocator->translator()->trans('Caractéristiques', [], 'admin');
            $website = $this->getWebsite();
            $this->arguments['haveCaption'] = false;
            $this->arguments['keywords'][$featuresLabel] = [];
            $features = $this->coreLocator->em()->getRepository(Feature::class)->findBy(['website' => $website->entity], ['adminName' => 'ASC']);
            foreach ($features as $feature) {
                $this->arguments['keywords'][$featuresLabel][] = 'feature.'.$feature->getSlug();
            }
        }
    }
}
