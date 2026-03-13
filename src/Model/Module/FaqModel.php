<?php

declare(strict_types=1);

namespace App\Model\Module;

use App\Controller\Front\Action\FaqController;
use App\Entity\Layout\Block;
use App\Entity\Module\Faq\Faq;
use App\Model\BaseModel;
use App\Model\Core\WebsiteModel;
use App\Model\EntityModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;

/**
 * FaqModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class FaqModel extends BaseModel
{
    private static bool $microdata = false;

    /**
     * fromEntity.
     *
     * @throws MappingException|NonUniqueResultException
     */
    public static function fromEntity(CoreLocatorInterface $coreLocator, bool $promote, ?Block $block = null, mixed $filter = null, string $type = 'faq'): object
    {
        $website = self::$coreLocator->website();
        $faq = self::$coreLocator->em()->getRepository(Faq::class)->findOneByFilter($website->entity, self::$coreLocator->locale(), $promote, $filter);
        $model = $faq ? (array) EntityModel::fromEntity($faq, self::$coreLocator)->response : [];
        $questions = $faq ? self::questions($faq, $type) : [];

        return (object) array_merge($model, [
            'entity' => $faq,
            'asTeaser' => 'teaser' === $type,
            'questions' => $questions,
            'microdata' => $faq ? self::microdata($website, $faq, $promote, $questions, $block) : [],
        ]);
    }

    /**
     * microdata.
     *
     * @throws MappingException|NonUniqueResultException
     */
    private static function microdata(WebsiteModel $website, Faq $faq, bool $promote, array $questions, ?Block $block = null): array
    {
        $microdata = [];

        if ($promote) {
            return $microdata;
        }

        if (!self::$microdata && $block instanceof Block) {
            $microdata = !$faq->isDisabledMicrodata() ? $questions : [];
            $layout = $block->getCol()->getZone()->getLayout();
            foreach ($layout->getZones() as $zone) {
                foreach ($zone->getCols() as $col) {
                    foreach ($col->getBlocks() as $block) {
                        foreach ($block->getActionIntls() as $actionIntl) {
                            if ($block->getAction() && FaqController::class === $block->getAction()->getController()
                                && self::$coreLocator->locale() === $actionIntl->getLocale()) {
                                self::$microdata = true;
                                $faqAction = self::$coreLocator->em()->getRepository(Faq::class)->findOneByFilter($website->entity, self::$coreLocator->locale(), false, $actionIntl->getActionFilter());
                                if ($faqAction instanceof Faq && $faqAction->getId() !== $faq->getId() && !$faqAction->isDisabledMicrodata()) {
                                    $microdata = array_merge($microdata, self::questions($faq));
                                }
                            }
                        }
                    }
                }
            }
        }

        return $microdata;
    }

    /**
     * questions.
     *
     * @throws MappingException|NonUniqueResultException
     */
    private static function questions(Faq $faq, string $type = 'faq'): array
    {
        $display = 'teaser' === $type ? $faq->getDisplayTeaser() : $faq->getDisplay();
        $limit = 'teaser' === $type ? $faq->getLimitTeaser() : -1;

        $count = 1;
        $questions = [];
        foreach ($faq->getQuestions() as $key => $question) {
            if ($count <= $limit || -1 === $limit) {
                $questions[$key] = (array) EntityModel::fromEntity($question, self::$coreLocator)->response;
                $questions[$key]['open'] = 'all-opened' === $display || (0 === $key && 'first-opened' === $display);
                ++$count;
            }
        }

        return $questions;
    }
}
