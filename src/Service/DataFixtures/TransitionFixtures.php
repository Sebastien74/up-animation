<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core as CoreEntities;
use App\Entity\Layout\BlockType;
use App\Entity\Security\User;
use App\Repository\Core\TransitionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * TransitionFixtures.
 *
 * Transition Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => TransitionFixtures::class, 'key' => 'transition_fixtures'],
])]
class TransitionFixtures
{
    private const array BLOCKS_TYPES_CATEGORIES = ['global', 'content'];
    private const array BLOCKS_TYPES_TRANSITIONS = [
        'title' => [
            'title-h2' => ['lib' => 'aos', 'effect' => 'fade-right', 'delay' => 250],
            'subtitle' => ['lib' => 'aos', 'effect' => 'fade-up', 'delay' => 400],
        ],
        'title-header' => [
            'title' => ['lib' => 'aos', 'effect' => 'fade-right', 'delay' => 250],
            'subtitle' => ['lib' => 'aos', 'effect' => 'fade-up', 'delay' => 400],
        ],
        'media' => [
            'media' => ['lib' => 'aos', 'effect' => 'fade-right', 'delay' => 250],
        ],
        'text' => [
            'text' => ['lib' => 'aos', 'effect' => 'fade-up', 'delay' => 250],
        ],
    ];
    private const array ACTIVES_TRANSITIONS = [
        'aos-fade-up',
        'aos-fade-down',
        'aos-fade-right',
        'aos-fade-left',
        'aos-zoom-in',
        'animate-headShake',
        'animate-heartBeat',
        'animate-backInDown',
        'animate-backInLeft',
        'animate-backInRight',
        'animate-backInUp',
        'up-vertical-parallax' => 'up-vertical-parallax',
        'down-vertical-parallax' => 'down-vertical-parallax',
    ];
    private const array AOS_TRANSITIONS = [
        'fade-up' => 'fade-up',
        'fade-down' => 'fade-down',
        'fade-right' => 'fade-right',
        'fade-left' => 'fade-left',
        'fade-up-right' => 'fade-up-right',
        'fade-up-left' => 'fade-up-left',
        'fade-down-right' => 'fade-down-right',
        'fade-down-left' => 'fade-down-left',
        'flip-left' => 'flip-left',
        'flip-right' => 'flip-right',
        'flip-up' => 'flip-up',
        'flip-down' => 'flip-down',
        'zoom-in' => 'zoom-in',
        'zoom-in-up' => 'zoom-in-up',
        'zoom-in-down' => 'zoom-in-down',
        'zoom-in-left' => 'zoom-in-left',
        'zoom-in-right' => 'zoom-in-right',
        'zoom-out' => 'zoom-out',
        'zoom-out-up' => 'zoom-out-up',
        'zoom-out-down' => 'zoom-out-down',
        'zoom-out-right' => 'zoom-out-right',
        'zoom-out-left' => 'zoom-out-left',
    ];
    private const array ANIMATE_TRANSITIONS = [
        'bounce' => 'bounce',
        'flash' => 'flash',
        'pulse' => 'pulse',
        'rubberBand' => 'rubberBand',
        'shakeX' => 'shakeX',
        'shakeY' => 'shakeY',
        'headShake' => 'headShake',
        'swing' => 'swing',
        'tada' => 'tada',
        'wobble' => 'wobble',
        'jello' => 'jello',
        'heartBeat' => 'heartBeat',
        'backInDown' => 'backInDown',
        'backInLeft' => 'backInLeft',
        'backInRight' => 'backInRight',
        'backInUp' => 'backInUp',
        'backOutDown' => 'backOutDown',
        'backOutLeft' => 'backOutLeft',
        'backOutRight' => 'backOutRight',
        'backOutUp' => 'backOutUp',
        'bounceIn' => 'bounceIn',
        'bounceInDown' => 'bounceInDown',
        'bounceInLeft' => 'bounceInLeft',
        'bounceInRight' => 'bounceInRight',
        'bounceInUp' => 'bounceInUp',
        'bounceOut' => 'bounceOut',
        'bounceOutDown' => 'bounceOutDown',
        'bounceOutLeft' => 'bounceOutLeft',
        'bounceOutUp' => 'bounceOutUp',
        'bounceOutRight' => 'bounceOutRight',
        'fadeIn' => 'fadeIn',
        'fadeInDown' => 'fadeInDown',
        'fadeInDownBig' => 'fadeInDownBig',
        'fadeInLeft' => 'fadeInLeft',
        'fadeInLeftBig' => 'fadeInLeftBig',
        'fadeInRight' => 'fadeInRight',
        'fadeInRightBig' => 'fadeInRightBig',
        'fadeInUp' => 'fadeInUp',
        'fadeInUpBig' => 'fadeInUpBig',
        'fadeInTopLeft' => 'fadeInTopLeft',
        'fadeInTopRight' => 'fadeInTopRight',
        'fadeInBottomLeft' => 'fadeInBottomLeft',
        'fadeInBottomRight' => 'fadeInBottomRight',
        'fadeOut' => 'fadeOut',
        'fadeOutDown' => 'fadeOutDown',
        'fadeOutDownBig' => 'fadeOutDownBig',
        'fadeOutLeft' => 'fadeOutLeft',
        'fadeOutLeftBig' => 'fadeOutLeftBig',
        'fadeOutRight' => 'fadeOutRight',
        'fadeOutRightBig' => 'fadeOutRightBig',
        'fadeOutUp' => 'fadeOutUp',
        'fadeOutUpBig' => 'fadeOutUpBig',
        'fadeOutTopLeft' => 'fadeOutTopLeft',
        'fadeOutTopRight' => 'fadeOutTopRight',
        'fadeOutBottomRight' => 'fadeOutBottomRight',
        'fadeOutBottomLeft' => 'fadeOutBottomLeft',
        'flip' => 'flip',
        'flipInX' => 'flipInX',
        'flipInY' => 'flipInY',
        'flipOutX' => 'flipOutX',
        'flipOutY' => 'flipOutY',
        'lightSpeedInRight' => 'lightSpeedInRight',
        'lightSpeedInLeft' => 'lightSpeedInLeft',
        'lightSpeedOutRight' => 'lightSpeedOutRight',
        'lightSpeedOutLeft' => 'lightSpeedOutLeft',
        'rotateIn' => 'rotateIn',
        'rotateInDownLeft' => 'rotateInDownLeft',
        'rotateInDownRight' => 'rotateInDownRight',
        'rotateInUpLeft' => 'rotateInUpLeft',
        'rotateInUpRight' => 'rotateInUpRight',
        'rotateOut' => 'rotateOut',
        'rotateOutDownLeft' => 'rotateOutDownLeft',
        'rotateOutDownRight' => 'rotateOutDownRight',
        'rotateOutUpLeft' => 'rotateOutUpLeft',
        'rotateOutUpRight' => 'rotateOutUpRight',
        'hinge' => 'hinge',
        'jackInTheBox' => 'jackInTheBox',
        'rollIn' => 'rollIn',
        'rollOut' => 'rollOut',
        'zoomIn' => 'zoomIn',
        'zoomInDown' => 'zoomInDown',
        'zoomInLeft' => 'zoomInLeft',
        'zoomInRight' => 'zoomInRight',
        'zoomInUp' => 'zoomInUp',
        'zoomOut' => 'zoomOut',
        'zoomOutDown' => 'zoomOutDown',
        'zoomOutLeft' => 'zoomOutLeft',
        'zoomOutRight' => 'zoomOutRight',
        'zoomOutUp' => 'zoomOutUp',
        'slideInDown' => 'slideInDown',
        'slideInLeft' => 'slideInLeft',
        'slideInRight' => 'slideInRight',
        'slideInUp' => 'slideInUp',
        'slideOutDown' => 'slideOutDown',
        'slideOutLeft' => 'slideOutLeft',
        'slideOutRight' => 'slideOutRight',
        'slideOutUp' => 'slideOutUp',
    ];
    private const array PARALLAX_TRANSITIONS = [
        'up-vertical-parallax' => 'up-vertical-parallax',
        'down-vertical-parallax' => 'down-vertical-parallax',
        'left-horizontal-parallax' => 'left-horizontal-parallax',
        'right-horizontal-parallax' => 'right-horizontal-parallax',
    ];

    private ?User $user;
    private TransitionRepository $repository;
    private int $position;

    /**
     * TransitionFixtures constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Add Transitions.
     */
    public function add(CoreEntities\Configuration $configuration, ?User $user = null, ?CoreEntities\Website $websiteToDuplicate = null): void
    {
        $this->user = $user;
        $this->repository = $this->entityManager->getRepository(CoreEntities\Transition::class);
        $this->position = count($this->repository->findBy(['configuration' => $configuration])) + 1;

        if ($websiteToDuplicate instanceof CoreEntities\Website) {
            $this->generateDbTransitions($configuration, $websiteToDuplicate);
        } else {
            $this->generateAos($configuration);
            $this->generateAnimate($configuration);
            $this->generateParallax($configuration);
            $this->generateBlocksTypes($configuration);
        }
    }

    /**
     * Generate AOS transitions.
     */
    private function generateDbTransitions(CoreEntities\Configuration $configuration, CoreEntities\Website $websiteToDuplicate): void
    {
        foreach ($websiteToDuplicate->getConfiguration()->getTransitions() as $referTransition) {
            $transition = new CoreEntities\Transition();
            $transition->setAdminName($referTransition->getAdminName());
            $transition->setSlug($referTransition->getSlug());
            $transition->setAosEffect($referTransition->getAosEffect());
            $transition->setAnimateEffect($referTransition->getAnimateEffect());
            $transition->setLaxPreset($referTransition->getLaxPreset());
            $transition->setActive($referTransition->isActive());
            $transition->setDelay($referTransition->getDelay());
            $transition->setSection($referTransition->getSection());
            $transition->setElement($referTransition->getElement());
            $transition->setPosition($referTransition->getPosition());
            $transition->setConfiguration($configuration);
            $transition->setCreatedBy($this->user);
            $this->entityManager->persist($transition);
        }
    }

    /**
     * Generate AOS transitions.
     */
    private function generateAos(CoreEntities\Configuration $configuration): void
    {
        foreach (self::AOS_TRANSITIONS as $name => $aosTransition) {
            $active = in_array('aos-'.$aosTransition, self::ACTIVES_TRANSITIONS);
            if (!$this->existing($configuration, $aosTransition) && $active) {
                $transition = new CoreEntities\Transition();
                $transition->setAdminName($name);
                $transition->setSlug($aosTransition);
                $transition->setAosEffect($aosTransition);
                $transition->setActive(true);
                $transition->setConfiguration($configuration);
                $transition->setPosition($this->position);
                $transition->setCreatedBy($this->user);
                $this->entityManager->persist($transition);
                ++$this->position;
            }
        }
    }

    /**
     * Generate Animate transitions.
     */
    private function generateAnimate(CoreEntities\Configuration $configuration): void
    {
        foreach (self::ANIMATE_TRANSITIONS as $name => $animateTransition) {
            $active = in_array('animate-'.$animateTransition, self::ACTIVES_TRANSITIONS);
            if (!$this->existing($configuration, $animateTransition) && $active) {
                $transition = new CoreEntities\Transition();
                $transition->setAdminName($name);
                $transition->setSlug($animateTransition);
                $transition->setAnimateEffect($animateTransition);
                $transition->setActive(true);
                $transition->setConfiguration($configuration);
                $transition->setPosition($this->position);
                $transition->setCreatedBy($this->user);
                $this->entityManager->persist($transition);
                ++$this->position;
            }
        }
    }

    /**
     * Generate Parallax transitions.
     */
    private function generateParallax(CoreEntities\Configuration $configuration): void
    {
        foreach (self::PARALLAX_TRANSITIONS as $name => $parallaxTransition) {
            $active = in_array($parallaxTransition, self::ACTIVES_TRANSITIONS);
            if (!$this->existing($configuration, $parallaxTransition) && $active) {
                $transition = new CoreEntities\Transition();
                $transition->setAdminName($name);
                $transition->setSlug($parallaxTransition);
                $transition->setAnimateEffect($parallaxTransition);
                $transition->setActive(true);
                $transition->setConfiguration($configuration);
                $transition->setPosition($this->position);
                $transition->setCreatedBy($this->user);
                $this->entityManager->persist($transition);
                ++$this->position;
            }
        }
    }

    /**
     * Generate BlockType transitions.
     */
    private function generateBlocksTypes(CoreEntities\Configuration $configuration): void
    {
        $blockTypes = $this->entityManager->getRepository(BlockType::class)->findAll();

        foreach ($blockTypes as $blockType) {
            /** @var string $slug */
            $slug = $blockType->getSlug();

            if (in_array($blockType->getCategory(), self::BLOCKS_TYPES_CATEGORIES)
                && isset(self::BLOCKS_TYPES_TRANSITIONS[$slug])
                && is_array(self::BLOCKS_TYPES_TRANSITIONS[$slug])) {
                foreach (self::BLOCKS_TYPES_TRANSITIONS[$slug] as $element => $config) {
                    $transitionSlug = 'block-'.$slug.'-'.$element;

                    if (!$this->existing($configuration, $transitionSlug)) {
                        $library = !empty($config['lib']) ? $config['lib'] : null;
                        $effect = !empty($config['effect']) ? $config['effect'] : null;
                        $delay = !empty($config['delay']) ? $config['delay'] : null;

                        $transition = new CoreEntities\Transition();
                        $transition->setAdminName($blockType->getAdminName().' '.$element);
                        $transition->setSlug($transitionSlug);
                        $transition->setSection('block-'.$slug);
                        $transition->setElement($element);
                        $transition->setActive(false);
                        $transition->setActiveForBlock(true);
                        $transition->setDelay(strval($delay));
                        $transition->setPosition($this->position);
                        $transition->setConfiguration($configuration);
                        $transition->setCreatedBy($this->user);

                        if ('aos' === $library) {
                            $transition->setAosEffect($effect);
                        } elseif ('lax' === $library) {
                            $transition->setLaxPreset($effect);
                        }

                        $this->entityManager->persist($transition);
                        $this->entityManager->flush();

                        ++$this->position;
                    }
                }
            }
        }
    }

    /**
     * Check if Transition already exist.
     */
    private function existing(CoreEntities\Configuration $configuration, string $slug): array
    {
        return $this->repository->findBy([
            'configuration' => $configuration,
            'slug' => $slug,
        ]);
    }
}
