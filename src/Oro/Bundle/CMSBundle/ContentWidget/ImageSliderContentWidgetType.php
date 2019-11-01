<?php

namespace Oro\Bundle\CMSBundle\ContentWidget;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\CMSBundle\Form\Type\ImageSlideCollectionType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Type;
use Twig\Environment;

/**
 * Type for the image slider widgets.
 */
class ImageSliderContentWidgetType implements ContentWidgetTypeInterface
{
    /** @var ManagerRegistry */
    private $registry;

    /** @var int */
    private $pointer = 0;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /** {@inheritdoc} */
    public static function getName(): string
    {
        return 'image_slider';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'oro.cms.content_widget_type.image_slider.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getBackOfficeViewSubBlocks(ContentWidget $contentWidget, Environment $twig): array
    {
        $data = $this->getWidgetData($contentWidget);

        return [
            [
                'title' => 'oro.cms.contentwidget.sections.slider_options.label',
                'subblocks' => [
                    [
                        'data' => [
                            $twig->render('@OroCMS/ImageSliderContentWidget/slider_options.html.twig', $data),
                        ]
                    ],
                ]
            ],
            [
                'title' => 'oro.cms.contentwidget.sections.image_slides.label',
                'subblocks' => [
                    [
                        'data' => [
                            $twig->render('@OroCMS/ImageSliderContentWidget/image_slides.html.twig', $data),
                        ]
                    ],
                ]
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * {@inheritdoc}
     */
    public function getSettingsForm(ContentWidget $contentWidget, FormFactoryInterface $formFactory): ?FormInterface
    {
        return $formFactory->create(FormType::class)
            ->add(
                'slidesToShow',
                IntegerType::class,
                [
                    'label' => 'oro.cms.content_widget_type.slider_options.slides_to_show.label',
                    'required' => true,
                    'block' => 'slider_options',
                    'block_config' => [
                        'slider_options' => [
                            'title' => 'oro.cms.contentwidget.sections.slider_options.label'
                        ]
                    ],
                    'constraints' => [
                        new NotBlank(),
                        new Type('integer'),
                        new Range(['min' => 1]),
                    ]
                ]
            )
            ->add(
                'slidesToScroll',
                IntegerType::class,
                [
                    'label' => 'oro.cms.content_widget_type.slider_options.slides_to_scroll.label',
                    'required' => true,
                    'block' => 'slider_options',
                    'constraints' => [
                        new NotBlank(),
                        new Type('integer'),
                        new Range(['min' => 1]),
                    ]
                ]
            )
            ->add(
                'autoplay',
                CheckboxType::class,
                [
                    'label' => 'oro.cms.content_widget_type.slider_options.autoplay.label',
                    'required' => true,
                    'block' => 'slider_options',
                    'constraints' => [
                        new Type('boolean'),
                    ]
                ]
            )
            ->add(
                'autoplaySpeed',
                IntegerType::class,
                [
                    'label' => 'oro.cms.content_widget_type.slider_options.autoplay_speed.label',
                    'required' => true,
                    'block' => 'slider_options',
                    'constraints' => [
                        new NotBlank(),
                        new Type('integer'),
                        new Range(['min' => 1]),
                    ]
                ]
            )
            ->add(
                'arrows',
                CheckboxType::class,
                [
                    'label' => 'oro.cms.content_widget_type.slider_options.arrows.label',
                    'required' => true,
                    'block' => 'slider_options',
                    'constraints' => [
                        new Type('boolean'),
                    ]
                ]
            )
            ->add(
                'dots',
                CheckboxType::class,
                [
                    'label' => 'oro.cms.content_widget_type.slider_options.dots.label',
                    'required' => true,
                    'block' => 'slider_options',
                    'constraints' => [
                        new Type('boolean'),
                    ]
                ]
            )
            ->add(
                'infinite',
                CheckboxType::class,
                [
                    'label' => 'oro.cms.content_widget_type.slider_options.infinite.label',
                    'required' => true,
                    'block' => 'slider_options',
                    'constraints' => [
                        new Type('boolean'),
                    ]
                ]
            )
            ->add(
                'imageSlides',
                ImageSlideCollectionType::class,
                [
                    'data' => $this->getImageSlides($contentWidget),
                    'block' => 'image_slides',
                    'block_config' => [
                        'image_slides' => [
                            'title' => 'oro.cms.contentwidget.sections.image_slides.label'
                        ]
                    ]
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgetData(ContentWidget $contentWidget): array
    {
        return array_merge(
            $contentWidget->getSettings(),
            [
                'pageComponentName' => $contentWidget->getName() . ($this->pointer++ ?: ''),
                'imageSlides' => $this->getImageSlides($contentWidget)
            ]
        );
    }

    /**
     * @param ContentWidget $contentWidget
     * @return array
     */
    private function getImageSlides(ContentWidget $contentWidget): array
    {
        return $this->registry->getManagerForClass(ImageSlide::class)
            ->getRepository(ImageSlide::class)
            ->findBy(['contentWidget' => $contentWidget], ['slideOrder' => 'ASC']);
    }

    /**
     * {@inheritdoc}
     */
    public function isInline(): bool
    {
        return false;
    }
}
