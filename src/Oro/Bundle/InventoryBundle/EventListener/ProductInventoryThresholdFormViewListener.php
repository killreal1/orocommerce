<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\InventoryBundle\Fallback\AbstractFallbackFieldsFormView;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class ProductInventoryThresholdFormViewListener extends AbstractFallbackFieldsFormView
{
    /**
     * @param BeforeListRenderEvent $event
     */
    public function onProductView(BeforeListRenderEvent $event)
    {
        $product = $this->getEntityFromRequest(Product::class);
        if (!$product) {
            return;
        }

        $this->addBlockToEntityView($event, 'OroInventoryBundle:Product:inventoryThreshold.html.twig', $product);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onProductEdit(BeforeListRenderEvent $event)
    {
        $this->addBlockToEntityEdit($event, 'OroInventoryBundle:Product:inventoryThresholdFormWidget.html.twig');
    }
}
