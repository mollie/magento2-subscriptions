<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Test\Integration\Service\Mollie;

use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Subscriptions\Service\Mollie\GetShippingCostForOrderItem;

class GetShippingCostForOrderItemTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testDoesNotAddShippingCostsForVirtualOrders(): void
    {
        $order = $this->loadOrder('100000001');
        $order->setIsVirtual(1);

        $items = $order->getItems();
        /** @var GetShippingCostForOrderItem $instance */
        $instance = $this->objectManager->create(GetShippingCostForOrderItem::class);

        $result = $instance->execute($order, array_shift($items));

        $this->assertSame(0.0, $result);
    }
}
