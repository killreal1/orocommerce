<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\CMSBundle\EventListener\ContentWidgetFormEventListener;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\Form;

class ContentWidgetFormEventListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ObjectRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var ContentWidgetFormEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ObjectRepository::class);

        $this->manager = $this->createMock(ObjectManager::class);
        $this->manager->expects($this->any())
            ->method('getRepository')
            ->with(ImageSlide::class)
            ->willReturn($this->repository);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(ImageSlide::class)
            ->willReturn($this->manager);

        $this->listener = new ContentWidgetFormEventListener($registry);
    }

    public function testOnBeforeFlush(): void
    {
        $imageSlide1 = $this->getEntity(ImageSlide::class, ['id' => 1001]);
        $imageSlide2 = $this->getEntity(ImageSlide::class, ['id' => 2002]);

        $data = new ContentWidget();
        $data->setSettings(['imageSlides' => [$imageSlide2], 'param' => 'value']);

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['contentWidget' => $data])
            ->willReturn([$imageSlide1]);

        $this->manager->expects($this->once())
            ->method('persist')
            ->with($imageSlide2);

        $this->manager->expects($this->once())
            ->method('remove')
            ->with($imageSlide1);

        $this->listener->onBeforeFlush(new AfterFormProcessEvent($this->createMock(Form::class), $data));

        $this->assertEquals(['param' => 'value'], $data->getSettings());
    }

    public function testOnBeforeFlushInvalidData(): void
    {
        $data = new \stdClass();

        $this->repository->expects($this->never())
            ->method($this->anything());

        $this->manager->expects($this->never())
            ->method($this->anything());

        $this->listener->onBeforeFlush(new AfterFormProcessEvent($this->createMock(Form::class), $data));
    }
}
