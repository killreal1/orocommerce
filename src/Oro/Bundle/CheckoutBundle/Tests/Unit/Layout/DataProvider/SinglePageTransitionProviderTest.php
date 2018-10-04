<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\SinglePageTransitionProvider;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionProviderInterface;
use Oro\Bundle\CheckoutBundle\Model\TransitionData;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;

class SinglePageTransitionProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TransitionProviderInterface
     */
    protected $baseProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SinglePageTransitionProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->baseProvider = $this->createMock(TransitionProviderInterface::class);
        $this->provider = new SinglePageTransitionProvider($this->baseProvider);
    }

    /**
     * @dataProvider continueTransitionDataProvider
     * @param bool $isAllowed
     */
    public function testGetContinueTransition(bool $isAllowed)
    {
        $workflowItem = new WorkflowItem();
        $transitionName = 'testName';

        /** @var Transition $transition */
        $transition = $this->createMock(Transition::class);
        $errors = new ArrayCollection(['some' => 'error']);
        $expectedTransitionData = new TransitionData($transition, $isAllowed, $errors);

        $this->baseProvider
            ->expects($this->once())
            ->method('getContinueTransition')
            ->with($workflowItem, $transitionName)
            ->willReturn($expectedTransitionData);

        $this->assertEquals(
            $expectedTransitionData,
            $this->provider->getContinueTransition($workflowItem, $transitionName)
        );
    }

    public function testGetContinueTransitionWhenNullReturned()
    {
        $workflowItem = new WorkflowItem();
        $transitionName = 'testName';

        $this->baseProvider
            ->expects($this->once())
            ->method('getContinueTransition')
            ->with($workflowItem, $transitionName)
            ->willReturn(null);

        $this->assertNull($this->provider->getContinueTransition($workflowItem, $transitionName));
    }

    /**
     * @return array
     */
    public function continueTransitionDataProvider()
    {
        return [
            'transition is allowed' => [
                'isAllowed' => true
            ],
            'transition is not allowed' => [
                'isAllowed' => true
            ]
        ];
    }

    public function testGetBackTransitions()
    {
        $workflowItem = new WorkflowItem();
        $transitions = [$this->createMock(TransitionData::class)];
        $this->baseProvider
            ->expects($this->once())
            ->method('getBackTransitions')
            ->with($workflowItem)
            ->willReturn($transitions);

        $this->assertEquals($transitions, $this->provider->getBackTransitions($workflowItem));
    }

    public function testGetBackTransition()
    {
        $transitionData = $this->createMock(TransitionData::class);
        $workflowItem = new WorkflowItem();
        $this->baseProvider
            ->expects($this->once())
            ->method('getBackTransition')
            ->with($workflowItem)
            ->willReturn($transitionData);

        $this->assertEquals($transitionData, $this->provider->getBackTransition($workflowItem));
    }

    public function testClearCache()
    {
        $this->baseProvider
            ->expects($this->once())
            ->method('clearCache');

        $this->provider->clearCache();
    }
}
