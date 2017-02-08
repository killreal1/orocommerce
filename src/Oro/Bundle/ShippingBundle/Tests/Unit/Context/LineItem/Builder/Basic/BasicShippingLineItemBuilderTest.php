<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context\LineItem\Builder\Basic;

use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Basic\BasicShippingLineItemBuilder;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Tests\Unit\Context\AbstractShippingLineItemTest;

class BasicShippingLineItemBuilderTest extends AbstractShippingLineItemTest
{

    public function setUp()
    {
        parent::setUp();
        $this->productHolderMock
            ->expects($this->once())
            ->method('getEntityIdentifier')
            ->willReturn(self::TEST_ID);
    }

    public function testFullBuild()
    {
        $builder = new BasicShippingLineItemBuilder(
            $this->priceMock,
            $this->productUnitMock,
            self::TEST_CODE,
            self::TEST_QUANTITY,
            $this->productHolderMock
        );

        $builder
            ->setProduct($this->productMock)
            ->setProductSku(self::TEST_SKU)
            ->setDimensions($this->dimensionsMock)
            ->setWeight($this->weightMock);

        $shippingLineItem = $builder->getResult();

        $expectedShippingLineItem = new ShippingLineItem($this->getShippingLineItemParams());

        $this->assertEquals($expectedShippingLineItem, $shippingLineItem);
    }

    public function testOptionalBuild()
    {
        $builder = new BasicShippingLineItemBuilder(
            $this->priceMock,
            $this->productUnitMock,
            self::TEST_CODE,
            self::TEST_QUANTITY,
            $this->productHolderMock
        );

        $shippingLineItem = $builder->getResult();

        $expectedShippingLineItem = new ShippingLineItem([
            ShippingLineItem::FIELD_PRICE => $this->priceMock,
            ShippingLineItem::FIELD_PRODUCT_UNIT => $this->productUnitMock,
            ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => self::TEST_CODE,
            ShippingLineItem::FIELD_QUANTITY => self::TEST_QUANTITY,
            ShippingLineItem::FIELD_PRODUCT_HOLDER => $this->productHolderMock,
            ShippingLineItem::FIELD_ENTITY_IDENTIFIER => self::TEST_ID
        ]);

        $this->assertEquals($expectedShippingLineItem, $shippingLineItem);
    }
}
