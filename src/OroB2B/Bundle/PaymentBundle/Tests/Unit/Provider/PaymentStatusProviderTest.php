<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Provider;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use OroB2B\Bundle\PaymentBundle\Method\PayflowGateway;
use OroB2B\Bundle\PaymentBundle\Method\PayPalPaymentsPro;
use OroB2B\Bundle\PaymentBundle\Method\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentStatusProvider;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class PaymentStatusProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var PaymentTransactionProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentTransactionProvider;

    /** @var PaymentStatusProvider */
    protected $provider;

    /** @var TotalProcessorProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $totalProcessorProvider;

    /** @var PaymentMethodRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentMethodRegistry;

    protected function setUp()
    {
        $this->paymentTransactionProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->totalProcessorProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentMethodRegistry = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry');

        $this->provider = new PaymentStatusProvider(
            $this->paymentTransactionProvider,
            $this->totalProcessorProvider,
            $this->paymentMethodRegistry
        );
    }

    /**
     * @param array $transactions
     * @param float $amount
     * @param string $expectedStatus
     *
     * @dataProvider statusDataProvider
     */
    public function testStatus(array $transactions, $amount, $expectedStatus)
    {
        $object = new \stdClass();

        $this->paymentTransactionProvider
            ->expects($this->once())
            ->method('getPaymentTransactions')
            ->with($object)
            ->willReturn($transactions);

        $total = new Subtotal();
        $total->setAmount($amount);

        $this->totalProcessorProvider
            ->expects($this->once())
            ->method('getTotal')
            ->with($object)
            ->willReturn($total);

        $this->assertEquals($expectedStatus, $this->provider->getPaymentStatus($object));
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function statusDataProvider()
    {
        return [
            'full if has successful capture' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CAPTURE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatusProvider::FULL,
            ],
            'partial if has successful capture but less amount' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CAPTURE)
                        ->setAmount(50),
                ],
                100,
                PaymentStatusProvider::PARTIALLY,
            ],
            'declined if has unsuccessful capture' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CAPTURE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatusProvider::DECLINED,
            ],
            'full if has successful charge' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CHARGE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatusProvider::FULL,
            ],
            'partial if has successful charge but less amount' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CHARGE)
                        ->setAmount(40),
                ],
                100,
                PaymentStatusProvider::PARTIALLY,
            ],
            'declined if has unsuccessful charge' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CHARGE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatusProvider::DECLINED,
            ],
            'full if has successful purchase' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::PURCHASE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatusProvider::FULL,
            ],
            'partial  if has successful purchase but less amount' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::PURCHASE)
                        ->setAmount(60),
                ],
                100,
                PaymentStatusProvider::PARTIALLY,
            ],
            'declined if has unsuccessful purchase' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::PURCHASE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatusProvider::DECLINED,
            ],
            'authorize if has active and successful authorize' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatusProvider::AUTHORIZED,
            ],
            'pending if has active but unsuccessful authorize' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(true)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatusProvider::PENDING,
            ],
            'pending if has successful but not active authorize' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatusProvider::PENDING,
            ],
            'declined if has unsuccessful and not active authorize' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatusProvider::DECLINED,
            ],
            'full has higher priority than declined' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CHARGE)
                        ->setAmount(100),
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatusProvider::FULL,
            ],
            'full has higher priority than authorized' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CHARGE)
                        ->setAmount(100),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatusProvider::FULL,
            ],
            'partial has higher priority than authorized' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CHARGE)
                        ->setAmount(40),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatusProvider::PARTIALLY,
            ],
            'partial has higher priority than declined' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CHARGE)
                        ->setAmount(40),
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatusProvider::PARTIALLY,
            ],
            'authorize has higher priority than declined' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatusProvider::AUTHORIZED,
            ],
            'full has top priority' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CHARGE)
                        ->setAmount(100),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatusProvider::FULL,
            ],
            'full with few successful' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::PURCHASE)
                        ->setAmount(40),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::PURCHASE)
                        ->setAmount(60),
                ],
                100,
                PaymentStatusProvider::FULL,
            ],
            'full with few successful and amount more than required' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::PURCHASE)
                        ->setAmount(70),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::PURCHASE)
                        ->setAmount(60),
                ],
                100,
                PaymentStatusProvider::FULL,
            ],
            'partial with few successful' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::PURCHASE)
                        ->setAmount(40),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::PURCHASE)
                        ->setAmount(20),
                ],
                100,
                PaymentStatusProvider::PARTIALLY,
            ],
            'pending if has validation' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setAction(PaymentMethodInterface::VALIDATE)
                        ->setAmount(0),
                ],
                100,
                PaymentStatusProvider::PENDING,
            ],
            'pending if has not any transactions' => [
                [],
                100,
                PaymentStatusProvider::PENDING,
            ],
        ];
    }

    /**
     * @param array $transactions
     * @param bool $methodIsAuthorizeOnly
     * @param float $amount
     * @param string $expectedStatus
     *
     * @dataProvider statusPendingOnAuthorizeDataProvider
     */
    public function testStatusPendingOnAuthorize(array $transactions, $methodIsAuthorizeOnly, $amount, $expectedStatus)
    {
        $object = new \stdClass();

        $this->paymentTransactionProvider
            ->expects($this->once())
            ->method('getPaymentTransactions')
            ->with($object)
            ->willReturn($transactions);

        $total = new Subtotal();
        $total->setAmount($amount);

        $this->totalProcessorProvider
            ->expects($this->once())
            ->method('getTotal')
            ->with($object)
            ->willReturn($total);

        /** @var PayflowGateway|\PHPUnit_Framework_MockObject_MockObject $paymentMethod */
        $paymentMethod = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Method\PayflowGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMethod->expects($this->any())->method('isAuthorizeOnly')
            ->willReturn($methodIsAuthorizeOnly);

        $this->paymentMethodRegistry->expects($this->any())->method('getPaymentMethod')
            ->willReturn($paymentMethod);

        $this->assertEquals($expectedStatus, $this->provider->getPaymentStatus(new \stdClass()));
    }

    /**
     * @return array
     */
    public function statusPendingOnAuthorizeDataProvider()
    {
        return [
            'pending on authorize only' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::AUTHORIZE),
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setAction(PaymentMethodInterface::CHARGE),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setPaymentMethod(PayPalPaymentsPro::TYPE)
                        ->setAction(PaymentMethodInterface::AUTHORIZE),
                ],
                true,
                100,
                PaymentStatusProvider::PENDING,
            ],
            'not pending on not authorize only' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::AUTHORIZE),
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setAction(PaymentMethodInterface::CHARGE),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setPaymentMethod(PayPalPaymentsPro::TYPE)
                        ->setAction(PaymentMethodInterface::AUTHORIZE),
                ],
                false,
                100,
                PaymentStatusProvider::AUTHORIZED,
            ],
            'another method' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::AUTHORIZE),
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setAction(PaymentMethodInterface::CHARGE),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setPaymentMethod(PaymentTerm::TYPE)
                        ->setAction(PaymentMethodInterface::AUTHORIZE),
                ],
                true,
                100,
                PaymentStatusProvider::AUTHORIZED,
            ],
        ];
    }
}
