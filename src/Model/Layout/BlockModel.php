<?php

declare(strict_types=1);

namespace App\Model\Layout;

use App\Entity\Layout\Block;
use App\Model;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;

/**
 * BlockModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class BlockModel extends Model\BaseModel
{
    private static array $cache = [];

    /**
     * BlockModel constructor.
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?Block $block = null,
        public readonly ?object $intl = null,
        public readonly ?object $media = null,
        public readonly ?object $mediaSecondary = null,
        public readonly ?bool $haveContent = null,
        public readonly ?bool $haveMedia = null,
        public readonly ?string $slug = null,
        public readonly ?string $customTemplate = null,
        public readonly ?string $style = null,
        public readonly ?string $color = null,
        public readonly ?string $fontSize = null,
        public readonly ?string $fontWeight = null,
        public readonly ?string $fontWeightSecondary = null,
        public readonly ?string $fontFamily = null,
        public readonly ?string $backgroundColor = null,
        public readonly ?string $icon = null,
        public readonly ?string $iconSize = null,
        public readonly ?string $iconPosition = null,
        public readonly ?string $script = null,
    ) {
    }

    /**
     * @throws NonUniqueResultException|MappingException
     */
    public static function fromEntity(Block $block, CoreLocatorInterface $coreLocator, ?string $locale = null): self
    {
        self::setLocator($coreLocator);

        $locale = $locale ?: self::$coreLocator->locale();

        if ($block->getId() && isset(self::$cache['response'][$block->getId()][$locale])) {
            return self::$cache['response'][$block->getId()][$locale];
        }

        $website = $coreLocator->website();
        $slug = self::getContent('slug', $block->getBlockType());
        $mediaBlocks = ['media'];
        $mediaIntlBlocks = ['card', 'title-header', 'video', 'modal'];
        $getMediaAndIntl = in_array($slug, $mediaIntlBlocks);
        $getMedia = in_array($slug, $mediaBlocks) || $getMediaAndIntl;
        $getIntl = !$getMedia || $getMediaAndIntl;
        $intl = $getIntl ? Model\IntlModel::fromEntity($block, $coreLocator, false) : null;
        $intl = self::intlForm($slug, $intl);
        $color = self::getContent('color', $block);
        $fontSize = self::getContent('fontSize', $block);
        $fontWeight = self::getContent('fontWeight', $block);
        $fontWeightSecondary = self::getContent('fontWeightSecondary', $block);
        $fontFamily = self::getContent('fontFamily', $block);

        if ('media' === $slug && $website->configuration->mediasSecondary) {
            $medias = Model\MediasModel::fromEntity($block, $coreLocator, $locale, false);
            $media = $medias->haveMain ? $medias->main : ($medias->haveFiles ? $medias->mainFile : null);
            $mediaSecondary = $medias->withoutMain ? $medias->withoutMain[array_key_first($medias->withoutMain)] : null;
        } else {
            $media = $getMedia ? Model\MediaModel::fromEntity($block, $coreLocator, false) : null;
        }

        self::$cache['response'][$block->getId()][$locale] = new self(
            id: $block->getId(),
            block: $block,
            intl: $intl,
            media: $media,
            mediaSecondary: !empty($mediaSecondary) ? $mediaSecondary : null,
            haveContent: $getIntl ? $intl->haveContent : false,
            haveMedia: $media instanceof Model\MediaModel ? $media->haveMedia : false,
            slug: $slug,
            customTemplate: self::getContent('customTemplate', $block),
            style: self::styleClass($block),
            color: $color ? 'text-'.$color : null,
            fontSize: $fontSize ? 'fz-'.$fontSize : null,
            fontWeight: $fontWeight ? 'fw-'.$fontWeight : null,
            fontWeightSecondary: $fontWeightSecondary ? 'fw-'.$fontWeightSecondary : null,
            fontFamily: $fontFamily ? 'ff-'.$fontFamily : null,
            backgroundColor: self::getContent('backgroundColor', $block),
            icon: self::getContent('icon', $block),
            iconSize: self::getContent('iconSize', $block),
            iconPosition: self::getContent('iconPosition', $block),
            script: self::getContent('script', $block),
        );

        return self::$cache['response'][$block->getId()][$locale];
    }

    /**
     * To set title by entity URL parameters.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private static function intlForm(?string $slug = null, ?Model\IntlModel $intl = null): ?object
    {
        if ($intl && 'title-header' === $slug && self::$coreLocator->request()->get('category') && self::$coreLocator->request()->get('code')) {
            $interface = self::$coreLocator->interfaceHelper()->interfaceByName(self::$coreLocator->request()->get('category'));
            $entity = !empty($interface['classname']) ? self::$coreLocator->em()->getRepository($interface['classname'])->find(self::$coreLocator->request()->get('code')) : null;
            $modelClassnames = [
                \App\Entity\Module\Catalog\Product::class => \App\Model\Module\ProductModel::class,
                \App\Entity\Module\Newscast\Newscast::class => \App\Model\Module\NewscastModel::class,
            ];
            $modelClassname = $entity && !empty($modelClassnames[get_class($entity)]) ? $modelClassnames[get_class($entity)] : Model\EntityModel::class;
            $model = $entity ? ($modelClassname)::fromEntity($entity, self::$coreLocator, [
                'disabledUrl' => true,
                'disabledMedias' => true,
                'disabledLayout' => true,
                'disabledCategories' => true,
                'disabledCategory' => true,
            ]) : null;
            $intl = $entity ? (array) $intl : $intl;
            if ($model && is_array($intl)) {
                $model = $model instanceof Model\EntityModel ? $model->response : $model;
                $intl['title'] = $model->intl->title;
                $intl = (object) $intl;
            }
        }

        return $intl;
    }
}
